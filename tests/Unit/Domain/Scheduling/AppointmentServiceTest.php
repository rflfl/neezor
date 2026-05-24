<?php

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;
    private Tenant $tenant;
    private Professional $professional;
    private Client $client;
    private Service $service60min;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentService(new AvailabilityService());
        $this->tenant = Tenant::factory()->create();
        $this->professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);
        $this->client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);
        $this->service60min = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_create_appointment_succeeds_with_valid_data(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointment = $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($this->tenant->id, $appointment->tenant_id);
        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertEquals(5000, $appointment->price);
    }

    public function test_create_appointment_throws_on_conflict(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(10, 0);

        $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);
    }

    public function test_transition_to_updates_status(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointment = $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $updated = $this->service->transitionTo($appointment, Appointment::STATUS_CONFIRMED);

        $this->assertEquals(Appointment::STATUS_CONFIRMED, $updated->status);
    }

    public function test_transition_to_throws_on_invalid_transition(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointment = $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->transitionTo($appointment, Appointment::STATUS_COMPLETED);
    }

    public function test_delete_removes_appointment(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointment = $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $this->service->delete($appointment);

        $this->assertNull(Appointment::withoutGlobalScopes()->find($appointment->id));
    }

    public function test_get_by_date_returns_only_appointments_for_that_date(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $tomorrow = Carbon::tomorrow()->setTime(10, 0);
        $nextWeek = Carbon::tomorrow()->addWeek()->setTime(10, 0);

        $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $tomorrow,
            'end_at' => $tomorrow->copy()->addMinutes(60),
        ]);
        $this->service->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $nextWeek,
            'end_at' => $nextWeek->copy()->addMinutes(60),
        ]);

        $appointments = $this->service->getByDate($this->tenant->id, Carbon::tomorrow());

        $this->assertEquals(1, $appointments->count());
    }
}