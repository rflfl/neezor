<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_a_cannot_access_tenant_b_data(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Salon A',
            'slug' => 'salon-a',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Salon B',
            'slug' => 'salon-b',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $userA = User::withoutGlobalScopes()->create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'admin',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
        ]);

        TenantContext::setCurrent($tenantA->id);
        $this->actingAs($userA);

        $visibleUsers = User::all();
        $this->assertCount(1, $visibleUsers);
        $this->assertEquals('User A', $visibleUsers->first()->name);
        $this->assertEquals($tenantA->id, $visibleUsers->first()->tenant_id);

        TenantContext::clear();
    }

    public function test_scoped_query_respects_tenant_context(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Salon A',
            'slug' => 'salon-a',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Salon B',
            'slug' => 'salon-b',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'User A1',
            'email' => 'usera1@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'admin',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'User A2',
            'email' => 'usera2@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'professional',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
        ]);

        TenantContext::setCurrent($tenantA->id);
        $tenantAUsers = User::all();
        $this->assertCount(2, $tenantAUsers);
        $this->assertTrue($tenantAUsers->every(fn ($u) => $u->tenant_id === $tenantA->id));

        TenantContext::setCurrent($tenantB->id);
        $tenantBUsers = User::all();
        $this->assertCount(1, $tenantBUsers);
        $this->assertEquals('User B', $tenantBUsers->first()->name);

        TenantContext::clear();
    }
}
