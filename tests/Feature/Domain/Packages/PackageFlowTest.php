<?php

namespace Tests\Feature\Domain\Packages;

use App\Domain\Customers\Models\Client;
use App\Domain\Packages\Models\Package;
use App\Domain\Packages\Models\PackageSession;
use App\Domain\Packages\Services\PackageService;
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

class PackageFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Professional $professional;
    private Client $client;
    private Service $service;
    private Package $package;
    private AppointmentService $appointmentService;
    private PackageService $packageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageService = new PackageService();
        $this->appointmentService = new AppointmentService(new AvailabilityService(), null, $this->packageService);

        $this->tenant = Tenant::factory()->create();
        TenantContext::setCurrent($this->tenant->id);

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

        $this->service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $this->package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Bronze',
            'price' => 20000,
            'valid_until_days' => 90,
        ]);

        $this->package->services()->attach($this->service->id, ['session_count' => 3]);

        $this->user = User::withoutGlobalScopes()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        TenantContext::setCurrent($this->tenant->id);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_full_package_flow_from_purchase_to_appointment_completion(): void
    {
        $this->actingAs($this->user);

        $this->packageService->purchase($this->tenant->id, $this->client->id, $this->package);

        $sessionBefore = PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service->id)
            ->first();

        $this->assertNotNull($sessionBefore);
        $this->assertEquals(3, $sessionBefore->sessions_remaining);
        $this->assertFalse($sessionBefore->isExpired());

        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointment = $this->appointmentService->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
            'package_id' => $this->package->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals(Appointment::STATUS_SCHEDULED, $appointment->status);

        $appointment = $this->appointmentService->transitionTo($appointment, Appointment::STATUS_CONFIRMED);
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->status);

        $appointment = $this->appointmentService->transitionTo($appointment, Appointment::STATUS_IN_PROGRESS);
        $this->assertEquals(Appointment::STATUS_IN_PROGRESS, $appointment->status);

        $appointment = $this->appointmentService->transitionTo($appointment, Appointment::STATUS_COMPLETED);
        $this->assertEquals(Appointment::STATUS_COMPLETED, $appointment->status);

        $sessionAfter = PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service->id)
            ->first();

        $this->assertNotNull($sessionAfter);
        $this->assertEquals(2, $sessionAfter->sessions_remaining);
        $this->assertNotNull($sessionAfter->used_at);
        $this->assertEquals($appointment->id, $sessionAfter->appointment_id);
    }

    public function test_second_appointment_uses_remaining_session(): void
    {
        $this->actingAs($this->user);

        $this->packageService->purchase($this->tenant->id, $this->client->id, $this->package);

        $start1 = Carbon::tomorrow()->setTime(10, 0);
        $appointment1 = $this->appointmentService->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start1,
            'end_at' => $start1->copy()->addMinutes(60),
        ]);

        $appointment1 = $this->appointmentService->transitionTo($appointment1, Appointment::STATUS_CONFIRMED);
        $appointment1 = $this->appointmentService->transitionTo($appointment1, Appointment::STATUS_IN_PROGRESS);
        $appointment1 = $this->appointmentService->transitionTo($appointment1, Appointment::STATUS_COMPLETED);

        $sessionAfterFirst = PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service->id)
            ->first();

        $this->assertEquals(2, $sessionAfterFirst->sessions_remaining, "First appointment should debit one session. Got {$sessionAfterFirst->sessions_remaining} remaining.");

        $start2 = Carbon::tomorrow()->setTime(14, 0);
        $appointment2 = $this->appointmentService->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start2,
            'end_at' => $start2->copy()->addMinutes(60),
        ]);

        $appointment2 = $this->appointmentService->transitionTo($appointment2, Appointment::STATUS_CONFIRMED);
        $appointment2 = $this->appointmentService->transitionTo($appointment2, Appointment::STATUS_IN_PROGRESS);
        $appointment2 = $this->appointmentService->transitionTo($appointment2, Appointment::STATUS_COMPLETED);

        $sessionAfterSecond = PackageSession::withoutGlobalScopes()
            ->where('client_id', $this->client->id)
            ->where('service_id', $this->service->id)
            ->first();

        $this->assertEquals(1, $sessionAfterSecond->sessions_remaining, "Second appointment should debit another session. Got {$sessionAfterSecond->sessions_remaining} remaining.");
    }

    public function test_cannot_use_expired_package_session(): void
    {
        PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => $this->package->id,
            'service_id' => $this->service->id,
            'sessions_remaining' => 1,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $start = Carbon::tomorrow()->setTime(10, 0);
        $appointment = $this->appointmentService->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $result = $this->appointmentService->debitSessionForAppointment($appointment);

        $this->assertFalse($result);
    }

    public function test_cannot_use_session_when_all_sessions_consumed(): void
    {
        PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => $this->package->id,
            'service_id' => $this->service->id,
            'sessions_remaining' => 0,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $start = Carbon::tomorrow()->setTime(10, 0);
        $appointment = $this->appointmentService->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        $result = $this->appointmentService->debitSessionForAppointment($appointment);

        $this->assertFalse($result);
    }

    public function test_package_list_endpoint_returns_packages(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard/packages');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('packages', function ($packages) {
                return $packages->count() >= 1;
            })
        );
    }

    public function test_can_create_package_with_services(): void
    {
        $this->actingAs($this->user);

        $service2 = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Depilação',
            'duration_minutes' => 45,
            'price' => 3000,
        ]);

        $response = $this->post('/dashboard/packages', [
            'name' => 'Pacote Prata',
            'price' => 35000,
            'valid_until_days' => 180,
            'services' => [
                ['service_id' => $this->service->id, 'session_count' => 5],
                ['service_id' => $service2->id, 'session_count' => 3],
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('packages', ['name' => 'Pacote Prata']);
        $this->assertDatabaseHas('package_service', ['package_id' => Package::latest('id')->first()->id, 'session_count' => 5]);
    }

    public function test_package_sessions_endpoint_returns_sessions(): void
    {
        $this->actingAs($this->user);

        $this->packageService->purchase($this->tenant->id, $this->client->id, $this->package);

        $response = $this->get("/dashboard/packages/{$this->package->id}/sessions");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('sessions', function ($sessions) {
                return $sessions->count() >= 1;
            })
        );
    }

    public function test_can_delete_package(): void
    {
        $this->actingAs($this->user);

        $response = $this->delete("/dashboard/packages/{$this->package->id}");

        $response->assertStatus(302);
        $this->assertNull(Package::withoutGlobalScopes()->find($this->package->id));
    }
}