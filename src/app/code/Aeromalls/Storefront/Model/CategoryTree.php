<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Model;

class CategoryTree
{
    public static function build(array $nodes, int $root): array
    {
        $children = [];
        foreach ($nodes as $node) {
            if (isset($node['enabled']) && !$node['enabled']) {
                continue;
            }$children[(int)$node['parent_id']][] = $node;
        }
        $walk = function (int $parent, array $seen = []) use (&$walk, $children): array {
            if (isset($seen[$parent])) {
                return [];
            }$seen[$parent] = true;
            $out = [];
            foreach ($children[$parent] ?? [] as $node) {
                unset($node['parent_id'],$node['enabled']);
                $node['children'] = $walk((int)$node['id'], $seen);
                $out[] = $node;
            }return $out;
        };
        return $walk($root);
    }
}
