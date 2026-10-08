<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Observer;

class CleanCache implements \Magento\Framework\Event\ObserverInterface
{
    private \Magento\Framework\App\Cache\TypeListInterface $cache;
    public function __construct(\Magento\Framework\App\Cache\TypeListInterface $cache)
    {
        $this->cache = $cache;

    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->cache->cleanType('full_page');
        $this->cache->cleanType('block_html');
    }
}
