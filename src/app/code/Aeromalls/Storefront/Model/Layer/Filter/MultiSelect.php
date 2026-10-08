<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Framework\App\RequestInterface;

class MultiSelect extends AbstractFilter
{
    private array $selectedValues = [];
    private ?array $options = null;

    public function getSelectedValues(): array
    {
        return $this->selectedValues;
    }

    public function apply(RequestInterface $request)
    {
        $raw = $request->getParam($this->getRequestVar());
        $values = is_array($raw) ? $raw : (is_string($raw) ? explode(',', $raw) : []);
        $options = $this->getOptions();
        foreach (array_slice($values, 0, 100) as $value) {
            if (!is_scalar($value) || !ctype_digit(trim((string)$value))) {
                continue;
            }
            $value = (string)(int)$value;
            if (isset($options[$value])) {
                $this->selectedValues[] = $value;
            }
        }
        $this->selectedValues = array_values(array_unique($this->selectedValues));
        if (!$this->selectedValues) {
            return $this;
        }
        // One terms filter implements OR within this attribute; other attributes remain AND.
        $this->getLayer()->getProductCollection()->addFieldToFilter(
            $this->getAttributeModel()->getAttributeCode(),
            array_map('intval', $this->selectedValues)
        );
        $labels = array_map(fn (string $value): string => $options[$value], $this->selectedValues);
        $this->getLayer()->getState()->addFilter(
            $this->_createItem(implode(' / ', $labels), implode(',', $this->selectedValues))
        );
        return $this;
    }

    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $facets = $this->getLayer()->getProductCollection()->getFacetedData($attribute->getAttributeCode());
        $onlyWithResults = $this->getAttributeIsFilterable($attribute) === self::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS;
        $items = [];
        foreach ($this->getOptions() as $value => $label) {
            $count = (int)($facets[$value]['count'] ?? 0);
            if ($onlyWithResults && $count === 0 && !in_array((string)$value, $this->selectedValues, true)) {
                continue;
            }
            $items[] = ['label' => $label, 'value' => (string)$value, 'count' => $count];
        }
        return $items;
    }

    private function getOptions(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->options = [];
        $pending = $this->getAttributeModel()->getFrontend()->getSelectOptions();
        while ($pending) {
            $option = array_shift($pending);
            $value = $option['value'] ?? null;
            if (is_array($value)) {
                $pending = array_merge($pending, $value);
            } elseif (is_scalar($value) && ctype_digit((string)$value)) {
                $this->options[(string)(int)$value] = strip_tags((string)$option['label']);
            }
        }
        return $this->options;
    }
}
