<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Block\Adminhtml;

class Partners extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('enabled', ['label' => __('Enabled (1/0)'),'class' => 'validate-digits']);
        $this->addColumn('name', ['label' => __('Partner name'),'class' => '']);
        $this->addColumn('image', ['label' => __('Logo'),'renderer' => $this->getLayout()->createBlock(\Aeromalls\Storefront\Block\Adminhtml\LogoUpload::class)]);
        $this->addColumn('alt', ['label' => __('Alt text'),'class' => '']);
        $this->addColumn('url', ['label' => __('Destination URL'),'class' => '']);
        $this->addColumn('new_tab', ['label' => __('New tab (1/0)'),'class' => 'validate-digits']);
        $this->addColumn('sort', ['label' => __('Sort order'),'class' => 'validate-digits']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
