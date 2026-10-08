<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model;

class RateLimiter
{
    private \Magento\Framework\App\CacheInterface $cache;
    private \Magento\Framework\Lock\LockManagerInterface $locks;
    private \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remote;
    public function __construct(\Magento\Framework\App\CacheInterface $cache, \Magento\Framework\Lock\LockManagerInterface $locks, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remote)
    {
        $this->cache = $cache;
        $this->locks = $locks;
        $this->remote = $remote;

    }
    public function allow(int $store): bool
    {
        $key = 'aero_search_'.hash('sha256', $store.'|'.$this->remote->getRemoteAddress().'|'.intdiv(time(), 60));
        if (!$this->locks->lock($key, 0)) {
            return false;
        }
        try {
            $count = (int)$this->cache->load($key);
            if ($count >= 60) {
                return false;
            }$this->cache->save((string)($count + 1), $key, [], 65);
            return true;
        } finally {
            $this->locks->unlock($key);
        }
    }
}
