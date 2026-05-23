<?php

namespace Tests\Feature;

use App\Domain\Customers\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::withoutGlobalScopes()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        TenantContext::setCurrent($this->tenant->id);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_can_list_clients(): void
    {
        Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Silva',
            'phone' => '11999999999',
        ]);

        $response = $this->get('/dashboard/clients');
        $response->assertStatus(200);
    }

    public function test_can_create_client(): void
    {
        $response = $this->post('/dashboard/clients', [
            'name' => 'João Santos',
            'phone' => '11888888888',
            'email' => 'joao@example.com',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('clients', [
            'name' => 'João Santos',
        ]);
    }

    public function test_can_update_client(): void
    {
        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
            'phone' => '11999999999',
        ]);

        $response = $this->put("/dashboard/clients/{$client->id}", [
            'name' => 'Updated Name',
            'phone' => '11888888888',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_client(): void
    {
        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'To Delete',
            'phone' => '11777777777',
        ]);

        $response = $this->delete("/dashboard/clients/{$client->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }
}
