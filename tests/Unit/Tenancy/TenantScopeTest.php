<?php

namespace Tests\Unit\Tenancy;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_query_by_current_tenant_id(): void
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

        User::withoutGlobalScope(TenantScope::class)->create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'admin',
        ]);

        User::withoutGlobalScope(TenantScope::class)->create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
        ]);

        TenantContext::setCurrent($tenantA->id);
        $scopedUsers = User::all();
        $this->assertCount(1, $scopedUsers);
        $this->assertEquals('User A', $scopedUsers->first()->name);

        TenantContext::setCurrent($tenantB->id);
        $scopedUsers = User::all();
        $this->assertCount(1, $scopedUsers);
        $this->assertEquals('User B', $scopedUsers->first()->name);

        TenantContext::clear();
    }

    public function test_returns_empty_when_no_tenant_context(): void
    {
        $tenant = Tenant::create([
            'name' => 'Salon A',
            'slug' => 'salon-a',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        User::withoutGlobalScope(TenantScope::class)->create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        TenantContext::clear();
        $users = User::all();
        $this->assertCount(0, $users);
    }
}
