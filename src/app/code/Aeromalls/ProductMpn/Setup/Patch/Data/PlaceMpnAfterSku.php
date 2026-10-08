<?php

declare(strict_types=1);

namespace Aeromalls\ProductMpn\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class PlaceMpnAfterSku implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $mpnAttributeId = $eavSetup->getAttributeId($entityTypeId, 'mpn');
        $skuAttributeId = $eavSetup->getAttributeId($entityTypeId, 'sku');
        $attributeTable = $this->moduleDataSetup->getTable('eav_entity_attribute');

        $select = $connection->select()
            ->from(['mpn' => $attributeTable], ['attribute_set_id', 'attribute_group_id'])
            ->join(
                ['sku' => $attributeTable],
                'sku.attribute_set_id = mpn.attribute_set_id'
                    . ' AND sku.attribute_group_id = mpn.attribute_group_id',
                ['sku_sort_order' => 'sort_order']
            )
            ->where('mpn.attribute_id = ?', $mpnAttributeId)
            ->where('sku.attribute_id = ?', $skuAttributeId);

        foreach ($connection->fetchAll($select) as $row) {
            $connection->update(
                $attributeTable,
                ['sort_order' => (int)$row['sku_sort_order'] + 1],
                [
                    'attribute_id = ?' => $mpnAttributeId,
                    'attribute_set_id = ?' => $row['attribute_set_id'],
                ]
            );
        }

        $connection->endSetup();
    }

    public static function getDependencies(): array
    {
        return [AddMpnProductAttribute::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
