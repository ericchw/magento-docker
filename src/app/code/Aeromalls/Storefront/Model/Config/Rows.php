<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class Rows extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    public function beforeSave()
    {
        $rows = $this->getValue();
        if (is_array($rows)) {
            foreach ($rows as $key => $row) {
                if ($key === '__empty') {
                    continue;
                }
                if (!Url::valid(trim((string)($row['url'] ?? '')))) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Invalid link URL.'));
                }
                if (isset($row['image']) && $row['image'] !== '' && !preg_match('#^aeromalls/[a-zA-Z0-9_./-]+\\.(png|jpe?g|gif|webp)$#i', $row['image'])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Logo must be an uploaded raster image in aeromalls/.'));
                }
                if (str_contains((string)($row['image'] ?? ''), '..')) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Invalid media path.'));
                }
                if (isset($row['type']) && !in_array($row['type'], ['cms','route','url'], true)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Link type must be cms, route or url.'));
                }
            }
        }
        return parent::beforeSave();
    }
}
