<?php

namespace Tests\Unit\Tenancy;

use App\Services\TenantContext;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_singleton_can_be_set_and_retrieved(): void
    {
        TenantContext::setCurrent(1);
        $this->assertEquals(1, TenantContext::current());

        TenantContext::setCurrent(42);
        $this->assertEquals(42, TenantContext::current());
    }

    public function test_returns_null_when_not_set(): void
    {
        TenantContext::clear();
        $this->assertNull(TenantContext::current());
    }

    public function test_can_be_cleared(): void
    {
        TenantContext::setCurrent(99);
        $this->assertEquals(99, TenantContext::current());

        TenantContext::clear();
        $this->assertNull(TenantContext::current());
    }
}
