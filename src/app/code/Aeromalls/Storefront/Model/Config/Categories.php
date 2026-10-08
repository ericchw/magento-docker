<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class Categories implements \Magento\Framework\Data\OptionSourceInterface
{
    private \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $factory;
    private \Magento\Store\Model\StoreManagerInterface $stores;
    private \Magento\Framework\App\RequestInterface $request;
    public function __construct(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $factory, \Magento\Store\Model\StoreManagerInterface $stores, \Magento\Framework\App\RequestInterface $request)
    {
        $this->factory = $factory;
        $this->stores = $stores;
        $this->request = $request;
    }
    public function toOptionArray(): array
    {
        $store = $this->request->getParam('store');
        $website = $this->request->getParam('website');
        $context = $store ? $this->stores->getStore($store) : ($website ? $this->stores->getWebsite($website)->getDefaultStore() : $this->stores->getDefaultStoreView());
        $out = [['value' => '','label' => __('Not configured')]];
        if (!$context) {
            return $out;
        }
        $c = $this->factory->create()->setStoreId($context->getId())->addAttributeToSelect(['name','include_in_menu'])->addAttributeToFilter('is_active', 1)->addAttributeToFilter('include_in_menu', 1)->addFieldToFilter('path', ['like' => '1/'.$context->getRootCategoryId().'/%'])->setOrder('level', 'ASC')->setOrder('position', 'ASC');
        $allowed = [(int)$context->getRootCategoryId() => true];
        foreach ($c as $category) {
            if (!isset($allowed[(int)$category->getParentId()])) {
                continue;
            }$allowed[(int)$category->getId()] = true;
            $out[] = ['value' => $category->getId(),'label' => str_repeat('— ', max(0, (int)$category->getLevel() - 2)).$category->getName()];
        }return $out;
    }
}
