<?php

namespace Tests\Unit\Tenancy;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_sets_tenant_id_on_creating_event(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Salon',
            'slug' => 'test-salon',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        TenantContext::setCurrent($tenant->id);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertEquals($tenant->id, $user->tenant_id);

        TenantContext::clear();
    }

    public function test_does_not_override_existing_tenant_id(): void
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

        TenantContext::setCurrent($tenantA->id);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
        ]);

        $this->assertEquals($tenantB->id, $user->tenant_id);

        TenantContext::clear();
    }

    public function test_without_tenant_scope_returns_all_users(): void
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

        TenantContext::setCurrent($tenantA->id);

        User::create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantB->id,
            'role' => 'admin',
        ]);

        $allUsers = User::withoutGlobalScope(TenantScope::class)->get();

        $this->assertCount(2, $allUsers);

        TenantContext::clear();
    }
}
