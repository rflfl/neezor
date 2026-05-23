<?php

namespace Tests\Unit\Models;

use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalTest extends TestCase
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
        $professional = Professional::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $professional->tenant);
        $this->assertEquals($tenant->id, $professional->tenant->id);
    }

    public function test_auto_sets_tenant_id_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);

        $professional = Professional::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'commission_rate' => 40.00,
            'is_active' => true,
        ]);

        $this->assertEquals($tenant->id, $professional->tenant_id);
        TenantContext::clear();
    }

    public function test_scoped_by_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Professional::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Pro A',
            'email' => 'proa@example.com',
            'commission_rate' => 40.00,
        ]);

        Professional::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Pro B',
            'email' => 'prob@example.com',
            'commission_rate' => 40.00,
        ]);

        TenantContext::setCurrent($tenantA->id);
        $this->assertCount(1, Professional::all());
        $this->assertEquals('Pro A', Professional::first()->name);

        TenantContext::setCurrent($tenantB->id);
        $this->assertCount(1, Professional::all());
        $this->assertEquals('Pro B', Professional::first()->name);

        TenantContext::clear();
    }
}
