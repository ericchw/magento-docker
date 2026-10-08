<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

use Aeromalls\Storefront\Model\Layer\Filter\Availability;
use Aeromalls\Storefront\Model\Layer\Filter\PriceRange;
use Aeromalls\Storefront\Model\Layer\Filter\MultiSelect;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer as Renderer;

class FilterRenderer
{
    public function aroundRender(Renderer $subject, callable $proceed, FilterInterface $filter): string
    {
        $template = match (true) {
            $filter instanceof PriceRange => 'Aeromalls_Storefront::layer/price.phtml',
            $filter instanceof Availability => 'Aeromalls_Storefront::layer/availability.phtml',
            $filter instanceof MultiSelect => $filter->getAttributeModel()->getFrontendInput() === 'boolean'
                ? 'Aeromalls_Storefront::layer/boolean.phtml'
                : 'Aeromalls_Storefront::layer/multiselect.phtml',
            default => null,
        };
        if ($template === null) {
            return $proceed($filter);
        }
        $original = $subject->getTemplate();
        $subject->setTemplate($template)->setData('aeromalls_filter', $filter);
        try {
            if ($filter instanceof MultiSelect) {
                // Swatch attributes also use the multi-select checkbox UI.
                $subject->assign('filterItems', $filter->getItems());
                return $subject->toHtml();
            }
            return $proceed($filter);
        } finally {
            $subject->assign('filterItems', []);
            $subject->setTemplate($original)->unsetData('aeromalls_filter');
        }
    }
}
