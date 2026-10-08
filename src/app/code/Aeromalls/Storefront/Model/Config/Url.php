<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model\Config;

class Url extends \Magento\Framework\App\Config\Value
{
    public static function valid(string $url): bool
    {
        if ($url === '') {
            return true;
        }
        if (preg_match('/[\\x00-\\x20\\x7f\\\\\\\\]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }
        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    public function beforeSave()
    {
        $value = trim((string)$this->getValue());
        if (!self::valid($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Use a safe http, https or site-relative URL.'));
        }
        $this->setValue($value);
        return parent::beforeSave();
    }
}
