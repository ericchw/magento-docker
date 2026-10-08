<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

class ProductTags
{
    private \Aeromalls\Storefront\Model\CacheTags $tags;
    public function __construct(\Aeromalls\Storefront\Model\CacheTags $tags)
    {
        $this->tags = $tags;

    }
    public function afterGetItems(\Hyva\Theme\ViewModel\ProductList $subject, array $items): array
    {
        foreach ($items as $item) {
            $this->tags->tags = array_merge($this->tags->tags, $item->getIdentities());
        }return $items;
    }
}
