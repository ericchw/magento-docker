<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Block;

class Content extends \Magento\Framework\View\Element\Template implements \Magento\Framework\DataObject\IdentityInterface
{
    private \Aeromalls\Storefront\ViewModel\Storefront $storefront;
    private \Aeromalls\Storefront\Model\CacheTags $cacheTags;
    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Aeromalls\Storefront\ViewModel\Storefront $storefront, \Aeromalls\Storefront\Model\CacheTags $cacheTags, array $data = [])
    {
        $this->storefront = $storefront;
        $this->cacheTags = $cacheTags;
        parent::__construct($context, $data);
    }
    public function vm(): \Aeromalls\Storefront\ViewModel\Storefront
    {
        return $this->storefront;
    }
    public function cms(string $key): string
    {
        $id = $this->storefront->value($key);
        if (!$id) {
            return '';
        }
        $cms = $this->getLayout()->createBlock(\Magento\Cms\Block\Block::class)->setBlockId($id);
        $html = $cms->toHtml();
        $this->cacheTags->tags = array_merge($this->cacheTags->tags, $cms->getIdentities());
        return $html;
    }
    public function deals(): string
    {
        $vm = $this->storefront;
        if (!$vm->enabled('deals/enabled')) {
            return '';
        }
        $cat = $vm->category((int)$vm->value('deals/category'));
        if (!$cat) {
            return '';
        }
        $slider = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class)->setTemplate('Magento_Catalog::product/slider/product-slider.phtml')->setData(['title' => $vm->value('deals/title'),'category_ids' => (string)$cat['id'],'page_size' => max(1, min(24, (int)$vm->value('deals/limit', '12'))),'sort_attribute' => 'position','sort_direction' => 'ASC','column_count' => 5,'slider_name' => 'aeromalls-weekly-deals']);
        return $slider->toHtml();
    }
    public function getIdentities(): array
    {
        return array_values(array_unique(array_merge($this->storefront->getIdentities(), $this->cacheTags->tags)));
    }
}
