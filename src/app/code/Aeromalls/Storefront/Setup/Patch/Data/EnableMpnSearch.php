<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Setup\Patch\Data;

class EnableMpnSearch implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private \Magento\Eav\Setup\EavSetupFactory $eav;
    private \Magento\Framework\Setup\ModuleDataSetupInterface $setup;
    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eav, \Magento\Framework\Setup\ModuleDataSetupInterface $setup)
    {
        $this->eav = $eav;
        $this->setup = $setup;

    }
    public function apply()
    {
        $eav = $this->eav->create(['setup' => $this->setup]);
        if ($eav->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'mpn')) {
            $eav->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'mpn', 'is_searchable', 1);
            $eav->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'mpn', 'used_in_product_listing', 1);
        }return $this;
    }
    public static function getDependencies(): array
    {
        return [\Aeromalls\ProductMpn\Setup\Patch\Data\AddMpnProductAttribute::class];
    }
    public function getAliases(): array
    {
        return [];
    }
}
