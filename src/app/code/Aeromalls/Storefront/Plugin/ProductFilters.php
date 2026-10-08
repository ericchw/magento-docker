<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

use Aeromalls\Storefront\Model\Layer\Filter\AvailabilityFactory;
use Aeromalls\Storefront\Model\Layer\Filter\PriceRangeFactory;
use Aeromalls\Storefront\Model\Layer\Filter\MultiSelectFactory;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Eav\Model\Config;

class ProductFilters
{
    private array $filters = [];

    public function __construct(
        private AvailabilityFactory $availabilityFactory,
        private PriceRangeFactory $priceRangeFactory,
        private Config $eavConfig,
        private MultiSelectFactory $multiSelectFactory
    ) {
    }

    public function afterGetFilters(FilterList $subject, array $result, Layer $layer): array
    {
        $key = spl_object_id($layer);
        if (isset($this->filters[$key])) {
            return $this->filters[$key];
        }

        $price = $this->priceRangeFactory->create([
            'layer' => $layer,
            'data' => ['attribute_model' => $this->eavConfig->getAttribute('catalog_product', 'price')],
        ]);
        $attributes = [];
        foreach ($result as $filter) {
            if ($filter->getRequestVar() === 'price') {
                continue;
            }
            if ($filter->hasAttributeModel()
                && in_array($filter->getAttributeModel()->getFrontendInput(), ['select', 'multiselect', 'boolean'], true)
            ) {
                $filter = $this->multiSelectFactory->create([
                    'layer' => $layer,
                    'data' => ['attribute_model' => $filter->getAttributeModel()],
                ]);
            }
            $attributes[] = $filter;
        }
        return $this->filters[$key] = array_merge(
            [$this->availabilityFactory->create(['layer' => $layer]), $price],
            $attributes
        );
    }
}
