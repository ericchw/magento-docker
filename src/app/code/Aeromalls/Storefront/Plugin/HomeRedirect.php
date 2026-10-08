<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

use Aeromalls\Storefront\Controller\Index\Index;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;

class HomeRedirect
{
    public function __construct(
        private RequestInterface $request,
        private ForwardFactory $forwards
    ) {
    }

    public function aroundExecute(Index $subject, callable $proceed)
    {
        if ($this->request->getRouteName() !== 'aeromalls') {
            return $proceed();
        }

        return $this->forwards->create()
            ->setModule('cms')
            ->setController('noroute')
            ->forward('index');
    }
}
