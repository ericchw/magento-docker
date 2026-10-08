<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Controller\Suggest;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Framework\Controller\Result\JsonFactory $json;
    private \Aeromalls\Storefront\Model\Search $search;
    private \Aeromalls\Storefront\Model\RateLimiter $rate;
    private \Aeromalls\Storefront\ViewModel\Storefront $vm;
    private \Psr\Log\LoggerInterface $logger;
    public function __construct(\Magento\Framework\App\RequestInterface $request, \Magento\Framework\Controller\Result\JsonFactory $json, \Aeromalls\Storefront\Model\Search $search, \Aeromalls\Storefront\Model\RateLimiter $rate, \Aeromalls\Storefront\ViewModel\Storefront $vm, \Psr\Log\LoggerInterface $logger)
    {
        $this->request = $request;
        $this->json = $json;
        $this->search = $search;
        $this->rate = $rate;
        $this->vm = $vm;
        $this->logger = $logger;

    }
    public function execute()
    {
        $result = $this->json->create()->setHeader('Cache-Control', 'private, no-store, max-age=0', true)->setHeader('Pragma', 'no-cache', true)->setHeader('X-Content-Type-Options', 'nosniff', true);
        if (!$this->rate->allow($this->vm->storeId())) {
            return $result->setHttpResponseCode(429)->setData(['error' => 'Too many requests']);
        }
        try {
            $q = \Aeromalls\Storefront\Model\Query::normalize($this->request->getParam('q'));
            $limit = \Aeromalls\Storefront\Model\Query::limit($this->request->getParam('limit'), (int)$this->vm->value('search/limit', '6'));
            return $result->setData($this->search->suggest($q, $limit));
        } catch (\Throwable $e) {
            $this->logger->error('Aeromalls search preview failed', ['exception' => $e]);
            return $result->setHttpResponseCode(503)->setData(['error' => 'Search preview unavailable']);
        }
    }
}
