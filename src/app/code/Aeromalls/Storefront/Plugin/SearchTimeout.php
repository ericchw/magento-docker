<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

class SearchTimeout
{
    private \Magento\Framework\App\RequestInterface $request;
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;

    }
    public function beforeQuery(\Magento\OpenSearch\Model\OpenSearch $subject, array $query): array
    {
        if ($this->request->getFullActionName() === 'aeromalls_search_suggest_index') {
            $query['body']['timeout'] = '2s';
            $query['client'] = ['timeout' => 3,'connect_timeout' => 1];
        }
        return [$query];
    }
}
