<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalCrudTest extends TestCase
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

    public function test_can_list_professionals(): void
    {
        Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John',
            'email' => 'john@example.com',
            'commission_rate' => 40.00,
        ]);

        $response = $this->get('/dashboard/professionals');
        $response->assertStatus(200);
    }

    public function test_can_create_professional(): void
    {
        $response = $this->post('/dashboard/professionals', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'commission_rate' => 45.00,
            'is_active' => true,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('professionals', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_can_update_professional(): void
    {
        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'commission_rate' => 40.00,
        ]);

        $response = $this->put("/dashboard/professionals/{$professional->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'commission_rate' => 50.00,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_professional(): void
    {
        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'To Delete',
            'email' => 'delete@example.com',
            'commission_rate' => 40.00,
        ]);

        $response = $this->delete("/dashboard/professionals/{$professional->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('professionals', [
            'id' => $professional->id,
        ]);
    }
}
