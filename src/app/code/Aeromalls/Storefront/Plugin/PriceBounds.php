<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\Dynamic;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Request\BucketInterface;

class PriceBounds
{
    public function aroundBuild(
        Dynamic $subject,
        callable $proceed,
        BucketInterface $bucket,
        array $dimensions,
        array $queryResult,
        DataProviderInterface $dataProvider
    ): array {
        if ($bucket->getName() !== 'aeromalls_price_bounds_bucket') {
            return $proceed($bucket, $dimensions, $queryResult, $dataProvider);
        }
        $stats = $queryResult['aggregations'][$bucket->getName()] ?? [];
        return ['bounds' => [
            'value' => 'bounds',
            'count' => (int)($stats['count'] ?? 0),
            'min' => $stats['min'] ?? null,
            'max' => $stats['max'] ?? null,
        ]];
    }
}
