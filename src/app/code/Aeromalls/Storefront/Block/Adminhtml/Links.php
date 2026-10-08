<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Block\Adminhtml;

class Links extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('enabled', ['label' => __('Enabled (1/0)'),'class' => 'validate-digits']);
        $this->addColumn('label', ['label' => __('Label'),'class' => '']);
        $this->addColumn('type', ['label' => __('Type: cms / route / url'),'class' => '']);
        $this->addColumn('destination', ['label' => __('CMS identifier / Magento route'),'class' => '']);
        $this->addColumn('url', ['label' => __('URL (for url type)'),'class' => '']);
        $this->addColumn('sort', ['label' => __('Sort order'),'class' => 'validate-digits']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
