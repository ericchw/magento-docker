<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Test\Unit;

class CategoryTreeTest extends \PHPUnit\Framework\TestCase
{
    public function testThreeLevelsAndHiddenParents(): void
    {
        $tree = \Aeromalls\Storefront\Model\CategoryTree::build([
         ['id' => 10,'parent_id' => 2,'name' => 'Components','url' => '/components'],
         ['id' => 11,'parent_id' => 10,'name' => 'CPU','url' => '/components/cpu'],
         ['id' => 12,'parent_id' => 11,'name' => 'AMD','url' => '/amd'],
         ['id' => 20,'parent_id' => 2,'name' => 'Hidden','url' => '/hidden','enabled' => false],
         ['id' => 21,'parent_id' => 20,'name' => 'Hidden child','url' => '/hidden-child'],
         ['id' => 30,'parent_id' => 90,'name' => 'Other store','url' => '/other'],
         ['id' => 40,'parent_id' => 99,'name' => 'Missing parent','url' => '/orphan']
        ], 2);
        $this->assertCount(1, $tree);
        $this->assertSame('/amd', $tree[0]['children'][0]['children'][0]['url']);
        $this->assertSame('CPU', $tree[0]['children'][0]['name']);
    }
    public function testEmptyTreeAndPositionOrder(): void
    {
        $this->assertSame([], \Aeromalls\Storefront\Model\CategoryTree::build([], 2));
        $tree = \Aeromalls\Storefront\Model\CategoryTree::build([['id' => 3,'parent_id' => 2],['id' => 4,'parent_id' => 2]], 2);
        $this->assertSame([3,4], array_column($tree, 'id'));
    }
}
