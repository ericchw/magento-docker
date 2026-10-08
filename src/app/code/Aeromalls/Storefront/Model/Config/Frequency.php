<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class Frequency implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [['value' => 'session','label' => __('Once per browser session')],['value' => '24h','label' => __('Once every 24 hours')]];
    }
}
