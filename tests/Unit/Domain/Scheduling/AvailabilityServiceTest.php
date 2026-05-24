<?php

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;
    private Tenant $tenant;
    private Professional $professional;
    private Client $client;
    private Service $service30min;
    private Service $service60min;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AvailabilityService();
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
        $this->service30min = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Manicure',
            'duration_minutes' => 30,
            'price' => 3000,
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

    public function test_returns_available_slots_for_service_with_30_min_duration(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $date = Carbon::tomorrow()->setTime(9, 0);

        $slots = $this->service->getAvailableSlots(
            $this->tenant->id,
            $this->professional->id,
            $this->service30min->id,
            $date
        );

        $this->assertGreaterThan(0, $slots->count());
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('start', $slot);
            $this->assertArrayHasKey('end', $slot);
            $this->assertEquals(30, $slot['start']->diffInMinutes($slot['end']));
        }
    }

    public function test_returns_available_slots_for_service_with_60_min_duration(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $date = Carbon::tomorrow()->setTime(9, 0);

        $slots = $this->service->getAvailableSlots(
            $this->tenant->id,
            $this->professional->id,
            $this->service60min->id,
            $date
        );

        $this->assertGreaterThan(0, $slots->count());
        foreach ($slots as $slot) {
            $this->assertEquals(60, $slot['start']->diffInMinutes($slot['end']));
        }
    }

    public function test_excludes_slots_conflicting_with_existing_appointments(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $date = Carbon::tomorrow()->setTime(9, 0);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service30min->id,
            'start_at' => $date->copy()->setTime(10, 0),
            'end_at' => $date->copy()->setTime(10, 30),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $slots = $this->service->getAvailableSlots(
            $this->tenant->id,
            $this->professional->id,
            $this->service30min->id,
            $date
        );

        $has10AM = $slots->contains(fn ($s) => $s['start']->format('H:i') === '10:00');
        $this->assertFalse($has10AM, '10:00 slot should be excluded due to conflict');
    }

    public function test_does_not_exclude_slots_for_cancelled_appointments(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $date = Carbon::tomorrow()->setTime(9, 0);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service30min->id,
            'start_at' => $date->copy()->setTime(10, 0),
            'end_at' => $date->copy()->setTime(10, 30),
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        $slots = $this->service->getAvailableSlots(
            $this->tenant->id,
            $this->professional->id,
            $this->service30min->id,
            $date
        );

        $has10AM = $slots->contains(fn ($s) => $s['start']->format('H:i') === '10:00');
        $this->assertTrue($has10AM, '10:00 slot should be available because appointment is cancelled');
    }

    public function test_is_slot_available_returns_true_when_no_conflict(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        $available = $this->service->isSlotAvailable(
            $this->tenant->id,
            $this->professional->id,
            $start,
            $end
        );

        $this->assertTrue($available);
    }

    public function test_is_slot_available_returns_false_when_conflict(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $available = $this->service->isSlotAvailable(
            $this->tenant->id,
            $this->professional->id,
            $start,
            $end
        );

        $this->assertFalse($available);
    }

    public function test_has_conflict_detects_overlapping_appointments(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->assertTrue(
            $this->service->hasConflict($this->tenant->id, $this->professional->id, $start, $end)
        );
    }

    public function test_has_conflict_ignores_cancelled_appointments(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        $this->assertFalse(
            $this->service->hasConflict($this->tenant->id, $this->professional->id, $start, $end)
        );
    }

    public function test_has_conflict_returns_false_when_excluding_own_appointment(): void
    {
        TenantContext::setCurrent($this->tenant->id);
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->assertFalse(
            $this->service->hasConflict(
                $this->tenant->id,
                $this->professional->id,
                $start,
                $end,
                $appointment->id
            )
        );
    }

    public function test_has_conflict_returns_false_for_completely_different_time(): void
    {
        TenantContext::setCurrent($this->tenant->id);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => Carbon::tomorrow()->setTime(9, 0),
            'end_at' => Carbon::tomorrow()->setTime(10, 0),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $newStart = Carbon::tomorrow()->setTime(14, 0);
        $newEnd = $newStart->copy()->addMinutes(60);

        $this->assertFalse(
            $this->service->hasConflict($this->tenant->id, $this->professional->id, $newStart, $newEnd)
        );
    }

    public function test_has_conflict_returns_true_for_partial_overlap(): void
    {
        TenantContext::setCurrent($this->tenant->id);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service60min->id,
            'start_at' => Carbon::tomorrow()->setTime(10, 0),
            'end_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $newStart = Carbon::tomorrow()->setTime(10, 30);
        $newEnd = $newStart->copy()->addMinutes(60);

        $this->assertTrue(
            $this->service->hasConflict($this->tenant->id, $this->professional->id, $newStart, $newEnd)
        );
    }
}