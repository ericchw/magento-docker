<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\ViewModel;

use Magento\Store\Model\ScopeInterface;

class Storefront implements \Magento\Framework\View\Element\Block\ArgumentInterface, \Magento\Framework\DataObject\IdentityInterface
{
    private ?array $tree = null;
    private array $tags = ['cat_c','cms_b','cms_p'];
    private \Magento\Framework\App\Config\ScopeConfigInterface $config;
    private \Magento\Store\Model\StoreManagerInterface $stores;
    private \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categories;
    private \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pages;
    private \Magento\Framework\Serialize\Serializer\Json $json;
    private \Magento\Framework\UrlInterface $url;
    private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone;
    private \Magento\Search\Helper\Data $search;
    private \Magento\Framework\App\State $state;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Store\Model\StoreManagerInterface $stores,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categories,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pages,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Search\Helper\Data $search,
        \Magento\Framework\App\State $state
    ) {
        $this->config = $config;
        $this->stores = $stores;
        $this->categories = $categories;
        $this->pages = $pages;
        $this->json = $json;
        $this->url = $url;
        $this->timezone = $timezone;
        $this->search = $search;
        $this->state = $state;

    }
    public function value(string $key, string $fallback = ''): string
    {
        return (string)($this->config->getValue('aeromalls_storefront/'.$key, ScopeInterface::SCOPE_STORE) ?? $fallback);
    }
    public function enabled(string $key): bool
    {
        return $this->value('general/enabled') === '1' && $this->value($key) === '1';
    }
    public function storeId(): int
    {
        return (int)$this->stores->getStore()->getId();
    }
    public function route(string $route): string
    {
        return $this->url->getUrl($route);
    }
    public function safeUrl(string $url): string
    {
        if (!\Aeromalls\Storefront\Model\Config\Url::valid($url) || $url === '') {
            return '';
        }
        return str_starts_with($url, '/') ? rtrim($this->stores->getStore()->getBaseUrl(), '/').$url : $url;
    }
    public function media(string $path): string
    {
        if (!preg_match('#^aeromalls/[a-zA-Z0-9_./-]+\\.(png|jpe?g|gif|webp)$#i', $path) || str_contains($path, '..')) {
            return '';
        }
        return $this->stores->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$path;
    }
    public function image(string $key): string
    {
        $p = $this->value($key);
        return $p ? $this->media('aeromalls/'.$p) : '';
    }
    public function rows(string $key): array
    {
        try {
            $rows = $this->json->unserialize($this->value($key, '[]'));
        } catch (\InvalidArgumentException) {
            return [];
        }
        if (!is_array($rows)) {
            return [];
        }
        $rows = array_filter($rows, fn ($r) => is_array($r) && (string)($r['enabled'] ?? '0') === '1');
        usort($rows, fn ($a, $b) => (int)($a['sort'] ?? 0) <=> (int)($b['sort'] ?? 0));
        return $rows;
    }
    public function links(): array
    {
        $out = [];
        $pages = $this->pages->create()->addStoreFilter($this->storeId())->addFieldToFilter('is_active', 1);
        $map = [];
        foreach ($pages as $page) {
            $map[$page->getIdentifier()] = $page;
            $this->tags[] = 'cms_p_'.$page->getId();
        }
        foreach ($this->rows('utility/links') as $row) {
            $dest = trim((string)($row['destination'] ?? ''));
            $type = $row['type'] ?? '';
            $url = '';
            if ($type === 'cms' && isset($map[$dest])) {
                $url = $this->route($dest);
            }
            if ($type === 'route' && preg_match('#^[a-zA-Z0-9_/-]+$#', $dest)) {
                $url = $this->route($dest);
            }
            if ($type === 'url') {
                $url = $this->safeUrl(trim((string)($row['url'] ?? '')));
            }
            if ($url && !empty($row['label'])) {
                $out[] = ['label' => $row['label'],'url' => $url];
            }
        }return $out;
    }
    public function navigation(): array
    {
        if ($this->tree !== null) {
            return $this->tree;
        }
        $root = (int)$this->stores->getStore()->getRootCategoryId();
        $this->tags[] = 'cat_c_'.$root;
        $depth = max(3, min(8, (int)$this->value('navigation/depth', '5')));
        $c = $this->categories->create()->setStoreId($this->storeId())->addAttributeToSelect(['name','is_active','include_in_menu'])->addFieldToFilter('path', ['like' => '1/'.$root.'/%'])->addAttributeToFilter('is_active', 1)->addAttributeToFilter('include_in_menu', 1)->addFieldToFilter('level', ['lteq' => $depth + 1])->addUrlRewriteToResult()->setOrder('level', 'ASC')->setOrder('position', 'ASC');
        $nodes = [];
        foreach ($c as $cat) {
            $id = (int)$cat->getId();
            $nodes[] = ['id' => $id,'parent_id' => (int)$cat->getParentId(),'name' => $cat->getName(),'url' => $cat->getUrl()];
            $this->tags[] = 'cat_c_'.$id;
        }
        return $this->tree = \Aeromalls\Storefront\Model\CategoryTree::build($nodes, $root);
    }
    public function category(int $id): ?array
    {
        $find = function (array $nodes) use (&$find, $id): ?array {
            foreach ($nodes as $node) {
                if ($node['id'] === $id) {
                    return $node;
                }if ($found = $find($node['children'])) {
                    return $found;
                }
            }return null;
        };
        return $find($this->navigation());
    }
    public function builderCategories(): array
    {
        $out = [];
        foreach (['cpu','motherboard','ram','gpu','storage','psu','case','cooling','os'] as $key) {
            if ($cat = $this->category((int)$this->value('builder/'.$key))) {
                $out[] = ['label' => strtoupper($key),'category' => $cat];
            }
        }return $out;
    }
    public function partners(): array
    {
        $out = [];
        foreach ($this->rows('partners/rows') as $row) {
            $image = $this->media((string)($row['image'] ?? ''));
            if (!$image || empty($row['name'])) {
                continue;
            }$row['image_url'] = $image;
            $row['url'] = $this->safeUrl((string)($row['url'] ?? ''));
            $out[] = $row;
        }return $out;
    }
    public function minLength(): int
    {
        return max(2, (int)$this->value('search/min_length', '2'), (int)$this->search->getMinQueryLength());
    }
    public function promo(): array
    {
        if (!$this->enabled('promo/enabled') || !$this->value('promo/campaign_id') || !$this->value('promo/title') || !$this->image('promo/image')) {
            return [];
        }
        $parse = function (string $value): ?int {
            if (!$value) {
                return null;
            }try {
                return (new \DateTimeImmutable($value, new \DateTimeZone($this->timezone->getConfigTimezone())))->getTimestamp();
            } catch (\Exception) {
                return null;
            }
        };
        $start = $parse($this->value('promo/start'));
        $end = $parse($this->value('promo/end'));
        if (($this->value('promo/start') && $start === null) || ($this->value('promo/end') && $end === null) || ($start && $end && $end <= $start)) {
            return [];
        }
        return ['id' => $this->value('promo/campaign_id'),'store' => $this->storeId(),'start' => $start,'end' => $end,'delay' => max(0, min(60000, (int)$this->value('promo/delay', '1500'))),'frequency' => $this->value('promo/frequency', 'session')];
    }
    public function assistant(): bool
    {
        return $this->enabled('assistant/enabled') && ($this->value('assistant/mode') === 'link' || ($this->value('assistant/mode') === 'preview' && $this->state->getMode() === \Magento\Framework\App\State::MODE_DEVELOPER));
    }
    public function contact(string $key): string
    {
        return (string)$this->config->getValue('general/store_information/'.$key, ScopeInterface::SCOPE_STORE);
    }
    public function getIdentities(): array
    {
        return array_values(array_unique($this->tags));
    }
}
