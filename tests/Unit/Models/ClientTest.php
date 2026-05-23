<?php

namespace Tests\Unit\Models;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
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
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->assertInstanceOf(Tenant::class, $client->tenant);
        $this->assertEquals($tenant->id, $client->tenant->id);
    }

    public function test_auto_sets_tenant_id_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);

        $client = Client::create([
            'name' => 'Maria Silva',
            'phone' => '11999999999',
            'email' => 'maria@example.com',
        ]);

        $this->assertEquals($tenant->id, $client->tenant_id);
        TenantContext::clear();
    }

    public function test_inactive_scope_returns_clients_with_no_recent_appointments(): void
    {
        $tenant = Tenant::factory()->create();

        $activeClient = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Active Client',
            'phone' => '11999999999',
        ]);

        $inactiveClient = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Inactive Client',
            'phone' => '11888888888',
        ]);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $activeClient->id,
            'start_at' => Carbon::now()->subDays(10),
            'end_at' => Carbon::now()->subDays(10)->addHours(1),
            'status' => 'completed',
        ]);

        TenantContext::setCurrent($tenant->id);

        $inactiveClients = Client::inactive()->get();
        $this->assertCount(1, $inactiveClients);
        $this->assertEquals('Inactive Client', $inactiveClients->first()->name);

        TenantContext::clear();
    }

    public function test_active_recently_scope_returns_clients_with_recent_appointments(): void
    {
        $tenant = Tenant::factory()->create();

        $activeClient = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Active Client',
            'phone' => '11999999999',
        ]);

        $inactiveClient = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Inactive Client',
            'phone' => '11888888888',
        ]);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $activeClient->id,
            'start_at' => Carbon::now()->subDays(10),
            'end_at' => Carbon::now()->subDays(10)->addHours(1),
            'status' => 'completed',
        ]);

        TenantContext::setCurrent($tenant->id);

        $activeClients = Client::activeRecently()->get();
        $this->assertCount(1, $activeClients);
        $this->assertEquals('Active Client', $activeClients->first()->name);

        TenantContext::clear();
    }
}
