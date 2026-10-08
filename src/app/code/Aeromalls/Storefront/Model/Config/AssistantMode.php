<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class AssistantMode implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [['value' => 'link','label' => __('Link to PC Builder')],['value' => 'preview','label' => __('Developer UI preview')]];
    }
}
