<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Test\Unit;

use PHPUnit\Framework\TestCase;
use Aeromalls\Storefront\Model\Query;
use Aeromalls\Storefront\Model\Config\Url;

class QueryTest extends TestCase
{
    public function testUnicodeAndBounds(): void
    {
        $this->assertSame('顯示卡', Query::normalize('  顯示卡  '));
        $this->assertSame(128, mb_strlen(Query::normalize(str_repeat('卡', 150))));
        $this->assertSame('', Query::normalize(['bad']));
        $this->assertSame('ab', Query::normalize("a\0b"));
    }
    public function testLimits(): void
    {
        $this->assertSame(10, Query::limit(900));
        $this->assertSame(1, Query::limit(-1));
        $this->assertSame(6, Query::limit([]));
    }
    public function testSafeDestinations(): void
    {
        foreach (['https://example.com/path','http://example.com','/pcbuilder',''] as $url) {
            $this->assertTrue(Url::valid($url), $url);
        }foreach (['javascript:alert(1)','data:text/html,bad','//example.com','/\\example.com',"https://example.com\n",'https://example.com bad'] as $url) {
            $this->assertFalse(Url::valid($url), $url);
        }
    }
}
