<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Block\Adminhtml;

class LogoUpload extends \Magento\Framework\View\Element\AbstractBlock
{
    protected function _toHtml()
    {
        $name = str_replace('&lt;%- _id %&gt;', '<%- _id %>', $this->escapeHtmlAttr($this->getInputName()));
        $id = str_replace('&lt;%- _id %&gt;', '<%- _id %>', $this->escapeHtmlAttr($this->getInputId()));
        $url = $this->escapeUrl($this->getUrl('aeromalls_storefront/image/upload'));
        return '<input class="input-text" type="text" name="'.$name.'" id="'.$id.'" value="<%- image %>"><input type="file" accept="image/png,image/jpeg,image/gif,image/webp" data-aero-logo-upload data-target="'.$id.'" data-url="'.$url.'"><span data-aero-upload-status role="status"></span>';
    }
}
