<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Controller\Categories;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    private \Magento\Framework\View\Result\PageFactory $pages;
    private \Magento\Framework\App\RequestInterface $request;
    public function __construct(\Magento\Framework\View\Result\PageFactory $pages, \Magento\Framework\App\RequestInterface $request)
    {
        $this->pages = $pages;
        $this->request = $request;

    }
    public function execute()
    {
        $page = $this->pages->create();
        $page->getConfig()->getTitle()->set($this->request->getRouteName() === 'pcbuilder' ? __('PC Builder / Components') : __('Product Categories'));
        if ($crumb = $page->getLayout()->getBlock('breadcrumbs')) {
            $crumb->addCrumb('home', ['label' => __('Home'),'link' => '/']);
            $crumb->addCrumb('current', ['label' => __('Product Categories')]);
        }return $page;
    }
}
