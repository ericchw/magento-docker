<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin;

use Magento\OpenSearch\Model\OpenSearch;

class FilterAggregations
{
    public function aroundQuery(OpenSearch $subject, callable $proceed, array $query): array
    {
        $wrapped = [];
        $facets = ['aeromalls_price_bounds_bucket' => 'price'];
        foreach ($query['body']['aggregations'] ?? [] as $name => $aggregation) {
            if ($name !== 'category_bucket' && isset($aggregation['terms']['field'])) {
                $facets[$name] = $aggregation['terms']['field'];
            }
        }
        foreach ($facets as $name => $field) {
            if (!isset($query['body']['aggregations'][$name])) {
                continue;
            }
            $aggregation = $query['body']['aggregations'][$name];
            // Keep category, visibility, search, and other selected filters, but omit this facet's own filter.
            $filter = $this->withoutField($query['body']['query'] ?? ['match_all' => new \stdClass()], $field);
            $query['body']['aggregations'][$name] = [
                'global' => new \stdClass(),
                'aggs' => ['filtered' => [
                    'filter' => $filter,
                    'aggs' => ['values' => $aggregation],
                ]],
            ];
            $wrapped[] = $name;
        }

        $result = $proceed($query);
        foreach ($wrapped as $name) {
            if (isset($result['aggregations'][$name]['filtered']['values'])) {
                $result['aggregations'][$name] = $result['aggregations'][$name]['filtered']['values'];
            }
        }
        return $result;
    }

    private function withoutField(array $query, string $field): array
    {
        foreach (['range', 'term', 'terms'] as $operator) {
            foreach (array_keys($query[$operator] ?? []) as $name) {
                if ($name === $field || ($field === 'price' && str_starts_with((string)$name, 'price_'))) {
                    return ['match_all' => new \stdClass()];
                }
            }
        }
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                $query[$key] = $this->withoutField($value, $field);
            }
        }
        return $query;
    }
}
