<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Framework\App\RequestInterface;

class Availability extends AbstractFilter
{
    protected $_requestVar = 'availability';
    private ?string $selectedValue = null;

    public function getName()
    {
        return __('In stock');
    }

    public function getSelectedValue(): ?string
    {
        return $this->selectedValue;
    }

    public function apply(RequestInterface $request)
    {
        $value = $request->getParam($this->_requestVar);
        if ($value !== 'in_stock') {
            return $this;
        }

        $this->selectedValue = $value;
        // Use the website's indexed MSI salability, including composite products.
        $this->getLayer()->getProductCollection()->addFieldToFilter(
            'is_out_of_stock',
            [0]
        );
        $label = __('In stock');
        $this->getLayer()->getState()->addFilter($this->_createItem($label, $value));
        return $this;
    }

    protected function _getItemsData()
    {
        $facets = $this->getLayer()->getProductCollection()->getFacetedData('is_out_of_stock');
        return [
            ['label' => __('In Stock'), 'value' => 'in_stock', 'count' => (int)($facets[0]['count'] ?? 0)],
        ];
    }
}
