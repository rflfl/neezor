<?php

namespace Tests\Feature;

use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['has_completed_onboarding' => true]);
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

    public function test_can_list_services(): void
    {
        Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $response = $this->get('/dashboard/services');
        $response->assertStatus(200);
    }

    public function test_can_create_service(): void
    {
        $response = $this->post('/dashboard/services', [
            'name' => 'Barbear',
            'duration_minutes' => 45,
            'price' => 3500,
            'is_active' => true,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', [
            'name' => 'Barbear',
        ]);
    }

    public function test_can_update_service(): void
    {
        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Service',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $response = $this->put("/dashboard/services/{$service->id}", [
            'name' => 'Updated Service',
            'duration_minutes' => 90,
            'price' => 7500,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Service',
        ]);
    }

    public function test_can_delete_service(): void
    {
        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'To Delete',
            'duration_minutes' => 30,
            'price' => 2000,
        ]);

        $response = $this->delete("/dashboard/services/{$service->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }
}
