<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Setup\Patch\Data;

class SeedPreview implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pages;
    private \Magento\Cms\Model\PageFactory $factory;
    private \Magento\Cms\Api\PageRepositoryInterface $repository;
    public function __construct(\Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pages, \Magento\Cms\Model\PageFactory $factory, \Magento\Cms\Api\PageRepositoryInterface $repository)
    {
        $this->pages = $pages;
        $this->factory = $factory;
        $this->repository = $repository;

    }
    public function apply()
    {
        if (!$this->pages->create()->addFieldToFilter('identifier', 'aeromalls-home')->getSize()) {
            $p = $this->factory->create()->setTitle('Aeromalls Home')->setIdentifier('aeromalls-home')->setStores([0])->setIsActive(false)->setContent('{{block class="Aeromalls\\Storefront\\Block\\Content" template="Aeromalls_Storefront::home.phtml"}}')->setPageLayout('1column');
            $this->repository->save($p);
        }return $this;
    }
    public static function getDependencies(): array
    {
        return [];
    }
    public function getAliases(): array
    {
        return [];
    }
}
