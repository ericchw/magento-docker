<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model;

class Query
{
    public static function normalize($value): string
    {
        if (!is_string($value)) {
            return '';
        }return mb_substr(trim(preg_replace('/[\p{C}]/u', '', $value) ?? ''), 0, 128);
    }
    public static function limit($value, int $default = 6): int
    {
        return max(1, min(10, is_scalar($value) ? (int)$value : $default));
    }
}
