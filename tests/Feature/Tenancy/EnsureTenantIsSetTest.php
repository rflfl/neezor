<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\EnsureTenantIsSet;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureTenantIsSetTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_authenticated_user_with_tenant_id(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Salon',
            'slug' => 'test-salon',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $middleware = new EnsureTenantIsSet;
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, TenantContext::current());
        TenantContext::clear();
    }

    public function test_allows_request_when_tenant_matches_user(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Salon',
            'slug' => 'test-salon',
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $middleware = new EnsureTenantIsSet;
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session.store']);

        TenantContext::setCurrent($tenant->id);

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());

        TenantContext::clear();
    }

    public function test_returns_403_when_tenant_mismatch(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Salon A',
            'slug' => 'salon-a-' . uniqid(),
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Salon B',
            'slug' => 'salon-b-' . uniqid(),
            'subscription_plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantA->id,
            'role' => 'admin',
        ]);

        $middleware = new EnsureTenantIsSet;
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        TenantContext::setCurrent($tenantB->id);

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
