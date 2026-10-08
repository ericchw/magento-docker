<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Layer\Filter;

class PriceRange extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    public function getSliderData(): ?array
    {
        $facets = $this->getLayer()->getProductCollection()->getFacetedData('aeromalls_price_bounds');
        $bounds = $facets['bounds'] ?? [];
        if (empty($bounds['count']) || !isset($bounds['min'], $bounds['max'])) {
            return null;
        }

        $rate = (float)$this->getCurrencyRate();
        $minimum = floor((float)$bounds['min'] * $rate);
        $maximum = ceil((float)$bounds['max'] * $rate);
        $selectedMinimum = $minimum;
        $selectedMaximum = $maximum;
        foreach ($this->getLayer()->getState()->getFilters() as $item) {
            if ($item->getFilter()->getRequestVar() !== 'price') {
                continue;
            }
            $interval = $item->getValue();
            if (is_array($interval) && count($interval) === 2) {
                $selectedMinimum = $interval[0] === '' ? $minimum : (float)$interval[0] * $rate;
                // The UI uses inclusive cents; Magento's native upper bound is exclusive.
                $selectedMaximum = $interval[1] === '' ? $maximum : (float)$interval[1] * $rate - .01;
                $minimum = min($minimum, floor($selectedMinimum));
                $maximum = max($maximum, ceil($selectedMaximum));
            }
        }

        return [
            'min' => $minimum,
            'max' => $maximum,
            'from' => max($minimum, min($maximum, round($selectedMinimum, 2))),
            'to' => max($minimum, min($maximum, round($selectedMaximum, 2))),
            'rate' => $rate,
            'currency' => $this->_storeManager->getStore($this->getStoreId())->getCurrentCurrencyCode(),
        ];
    }

    public function getItemsCount()
    {
        // A slider is useful even when Magento offers only one price bucket.
        return $this->getSliderData() !== null ? max(1, parent::getItemsCount()) : parent::getItemsCount();
    }
}
