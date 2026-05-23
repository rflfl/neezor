<?php

namespace Tests\Unit\Models;

use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->assertInstanceOf(Tenant::class, $service->tenant);
        $this->assertEquals($tenant->id, $service->tenant->id);
    }

    public function test_auto_sets_tenant_id_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);

        $service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->assertEquals($tenant->id, $service->tenant_id);
        TenantContext::clear();
    }

    public function test_scoped_by_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Service::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Service A',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        Service::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Service B',
            'duration_minutes' => 45,
            'price' => 3000,
        ]);

        TenantContext::setCurrent($tenantA->id);
        $this->assertCount(1, Service::all());
        $this->assertEquals('Service A', Service::first()->name);

        TenantContext::clear();
    }
}
