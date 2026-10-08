<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class Image extends \Magento\Config\Model\Config\Backend\Image
{
    public function beforeSave()
    {
        $file = $this->getFileData();
        if (!empty($file['tmp_name'])) {
            $info = @getimagesize($file['tmp_name']);
            if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg','image/png','image/gif','image/webp'], true)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Upload a valid PNG, JPEG, GIF or WebP image.'));
            }
            if (filesize($file['tmp_name']) > 5 * 1024 * 1024) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Image must be smaller than 5 MB.'));
            }
        }
        return parent::beforeSave();
    }
    protected function _getAllowedExtensions()
    {
        return ['jpg','jpeg','png','gif','webp'];
    }
}
