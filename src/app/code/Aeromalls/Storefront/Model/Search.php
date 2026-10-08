<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model;

class Search
{
    private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $products;
    private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $exactProducts;
    private \Magento\Store\Model\StoreManagerInterface $stores;
    private \Magento\Customer\Model\Session $customer;
    private \Magento\CatalogInventory\Helper\Stock $stock;
    private \Magento\Catalog\Helper\Image $image;
    private \Magento\Catalog\Helper\Data $catalog;
    private \Magento\Tax\Helper\Data $tax;
    private \Magento\Framework\Pricing\PriceCurrencyInterface $money;
    private \Aeromalls\Storefront\ViewModel\Storefront $vm;
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $products,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $exactProducts,
        \Magento\Store\Model\StoreManagerInterface $stores,
        \Magento\Customer\Model\Session $customer,
        \Magento\CatalogInventory\Helper\Stock $stock,
        \Magento\Catalog\Helper\Image $image,
        \Magento\Catalog\Helper\Data $catalog,
        \Magento\Tax\Helper\Data $tax,
        \Magento\Framework\Pricing\PriceCurrencyInterface $money,
        \Aeromalls\Storefront\ViewModel\Storefront $vm
    ) {
        $this->products = $products;
        $this->exactProducts = $exactProducts;
        $this->stores = $stores;
        $this->customer = $customer;
        $this->stock = $stock;
        $this->image = $image;
        $this->catalog = $catalog;
        $this->tax = $tax;
        $this->money = $money;
        $this->vm = $vm;

    }
    private function filter($c)
    {
        $s = $this->stores->getStore();
        $c->setStoreId($s->getId())->addStoreFilter($s)->addWebsiteFilter($s->getWebsiteId())->addAttributeToSelect(['name','sku','mpn','small_image','price','special_price','special_from_date','special_to_date','tax_class_id'])->addAttributeToFilter('status', 1)->setVisibility([3,4])->addPriceData($this->customer->getCustomerGroupId(), $s->getWebsiteId())->addUrlRewrite();
        $this->stock->addIsInStockFilterToCollection($c);
        return $c;
    }
    public function suggest(string $q, int $limit): array
    {
        $store = $this->stores->getStore();
        $url = $this->vm->route('catalogsearch/result').'?'.http_build_query(['q' => $q]);
        $out = ['query' => $q,'suggestions' => [],'items' => [],'total_count' => 0,'all_results_url' => $url];
        if (!$this->vm->enabled('search/enabled') || mb_strlen($q) < $this->vm->minLength()) {
            return $out;
        }
        foreach (preg_split('/\R/u', $this->vm->value('search/keywords')) ?: [] as $word) {
            $word = Query::normalize($word);
            if ($word && mb_stripos($word, $q) === 0) {
                $out['suggestions'][] = ['label' => $word,'url' => $this->vm->route('catalogsearch/result').'?'.http_build_query(['q' => $word])];
            }if (count($out['suggestions']) >= 5) {
                break;
            }
        }
        $c = $this->filter($this->products->create(['searchRequestName' => 'aeromalls_autocomplete']));
        $c->addSearchFilter($q)->setPageSize($limit)->setCurPage(1);
        $items = [];
        $exact = $this->filter($this->exactProducts->create())->addAttributeToFilter([['attribute' => 'sku','eq' => $q],['attribute' => 'mpn','eq' => $q]])->setPageSize($limit);
        foreach ($exact as $p) {
            $items[$p->getId()] = $p;
        }
        $out['total_count'] = (int)$c->getSize();
        foreach ($c as $p) {
            $items[$p->getId()] = $p;
        }
        $including = (int)$this->tax->getPriceDisplayType() !== 1;
        foreach (array_slice(array_values($items), 0, $limit) as $p) {
            $price = $p->getPriceInfo()->getPrice('final_price');
            $regular = $p->getPriceInfo()->getPrice('regular_price');
            $type = $p->getTypeId();
            $amountObject = in_array($type, ['configurable','bundle','grouped'], true) ? $price->getMinimalPrice() : $price->getAmount();
            $amount = (float)$amountObject->getValue($including ? null : 'tax');
            $reg = (float)$regular->getAmount()->getValue($including ? null : 'tax');
            $excl = (float)$amountObject->getValue('tax');
            $kind = in_array($type, ['configurable','bundle','grouped'], true) ? 'from' : 'fixed';
            $maximum = null;
            if ($type === 'bundle') {
                $candidate = (float)$price->getMaximalPrice()->getValue($including ? null : 'tax');
                if ($candidate > $amount) {
                    $maximum = $candidate;
                    $kind = 'range';
                }
            }
            $formatted = $this->money->format($amount, false);
            if ($kind === 'from') {
                $formatted = (string)__('From %1', $formatted);
            }
            if ($kind === 'range') {
                $formatted = (string)__('%1 – %2', $formatted, $this->money->format($maximum, false));
            }
            $out['items'][] = ['id' => (int)$p->getId(),'sku' => $p->getSku(),'mpn' => $this->vm->enabled('search/show_mpn') ? $p->getData('mpn') : null,'name' => $p->getName(),'url' => $p->getProductUrl(),'image' => ['url' => $this->image->init($p, 'product_page_image_small')->getUrl(),'alt' => $p->getName()],'price' => ['currency' => $store->getCurrentCurrencyCode(),'display_type' => $kind,'final' => $amount,'min' => $amount,'max' => $maximum,'regular' => $reg,'formatted_final' => $formatted,'formatted_regular' => $this->money->format($reg, false),'tax_display' => $this->tax->displayBothPrices() ? 'both' : ($including ? 'including' : 'excluding'),'excluding_tax' => $excl,'formatted_excluding_tax' => $this->money->format($excl, false)],'is_salable' => (bool)$p->isSalable()];
        }
        return $out;
    }
}
