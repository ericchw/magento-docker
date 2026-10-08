<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

class PreviewTheme
{
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themes;
    public function __construct(\Magento\Framework\App\RequestInterface $request, \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themes)
    {
        $this->request = $request;
        $this->themes = $themes;

    }
    public function afterGetConfigurationDesignTheme(\Magento\Theme\Model\View\Design $subject, $result, $area = null, $params = [])
    {
        if (($area === null || $area === 'frontend') && in_array($this->request->getFullActionName(), ['aeromalls_index_index','aeromalls_categories_index','pcbuilder_index_index'], true)) {
            $theme = $this->themes->create()->addFieldToFilter('theme_path', 'Aeromalls/aeromalls')->addFieldToFilter('area', 'frontend')->getFirstItem();
            if ($theme->getId()) {
                return $theme->getId();
            }
        }return $result;
    }
}
