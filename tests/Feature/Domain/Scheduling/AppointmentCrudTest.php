<?php

namespace Tests\Feature\Domain\Scheduling;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Professional $professional;
    protected Client $client;
    protected Service $service60min;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();

        $this->professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);

        $this->client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Silva',
            'phone' => '11999999999',
        ]);

        $this->service60min = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

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

    public function test_can_list_appointments(): void
    {
        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => Carbon::tomorrow()->setTime(10, 0),
            'end_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->get('/dashboard/calendar');
        $response->assertStatus(200);
    }

    public function test_can_create_appointment(): void
    {
        $start = Carbon::tomorrow()->setTime(14, 0);

        $response = $this->post('/dashboard/calendar', [
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start->toDateTimeString(),
            'end_at' => $start->copy()->addMinutes(60)->toDateTimeString(),
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
        ]);
    }

    public function test_cannot_create_double_booking(): void
    {
        $start = Carbon::tomorrow()->setTime(14, 0);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->post('/dashboard/calendar', [
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start->toDateTimeString(),
            'end_at' => $start->copy()->addMinutes(60)->toDateTimeString(),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_can_update_appointment(): void
    {
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => Carbon::tomorrow()->setTime(10, 0),
            'end_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->put("/dashboard/calendar/{$appointment->id}", [
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    public function test_can_delete_appointment(): void
    {
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => Carbon::tomorrow()->setTime(10, 0),
            'end_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->delete("/dashboard/calendar/{$appointment->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }
}