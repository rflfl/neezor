<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;

trait PerformsTenantTests
{
    protected ?Tenant $tenantA = null;

    protected ?Tenant $tenantB = null;

    protected ?User $userA = null;

    protected ?User $userB = null;

    protected function setUpTenantEnvironment(): void
    {
        $this->tenantA = Tenant::create([
            'name' => 'Salon A',
            'slug' => 'salon-a-'.uniqid(),
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Salon B',
            'slug' => 'salon-b-'.uniqid(),
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $this->tenantA->update(['has_completed_onboarding' => true]);
        $this->tenantB->update(['has_completed_onboarding' => true]);

        $this->userA = User::withoutGlobalScopes()->create([
            'name' => 'User A',
            'email' => 'usera'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenantA->id,
            'role' => 'admin',
        ]);

        $this->userB = User::withoutGlobalScopes()->create([
            'name' => 'User B',
            'email' => 'userb'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenantB->id,
            'role' => 'admin',
        ]);
    }

    protected function actingAsTenant(User $user): static
    {
        TenantContext::setCurrent($user->tenant_id);

        return $this->actingAs($user);
    }

    protected function setTenantContext(?int $tenantId): void
    {
        TenantContext::setCurrent($tenantId);
    }

    protected function clearTenantContext(): void
    {
        TenantContext::clear();
    }

    protected function tearDownTenantEnvironment(): void
    {
        $this->clearTenantContext();
        $this->tenantA = null;
        $this->tenantB = null;
        $this->userA = null;
        $this->userB = null;
    }
}
