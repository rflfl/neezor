<?php

namespace Tests\Feature\Domain\Booking;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Services\Models\Service;
use App\Models\BookingToken;
use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Professional $professional;
    protected Service $service;
    protected BookingToken $bookingToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Salon Test',
            'slug' => 'salon-test-' . uniqid(),
            'subscription_plan' => 'basic',
            'status' => 'active',
            'has_completed_onboarding' => true,
        ]);

        $this->professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'commission_rate' => 40.00,
            'is_active' => true,
        ]);

        $this->service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->bookingToken = BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'test-token-' . uniqid(),
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }

    public function test_booking_portal_requires_token(): void
    {
        $response = $this->get('/booking/' . $this->tenant->slug);
        $response->assertStatus(403);
    }

    public function test_booking_portal_rejects_invalid_token(): void
    {
        $response = $this->get('/booking/' . $this->tenant->slug . '?token=invalid-token');
        $response->assertStatus(403);
    }

    public function test_booking_portal_rejects_expired_token(): void
    {
        $expiredToken = BookingToken::create([
            'tenant_id' => $this->tenant->id,
            'token' => 'expired-token',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->get('/booking/' . $this->tenant->slug . '?token=' . $expiredToken->token);
        $response->assertStatus(403);
    }

    public function test_booking_portal_landing_page_loads(): void
    {
        $response = $this->get('/booking/' . $this->tenant->slug . '?token=' . $this->bookingToken->token);
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Booking/Index')
        );

        $page = $response->inertiaPage();
        $this->assertEquals('Booking/Index', $page['component']);
    }

    public function test_services_endpoint_returns_active_services(): void
    {
        Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive Service',
            'duration_minutes' => 30,
            'price' => 2500,
            'is_active' => false,
        ]);

        $response = $this->get(
            '/booking/' . $this->tenant->slug . '/services?token=' . $this->bookingToken->token
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['services']);
        $this->assertEquals('Corte', $data['services'][0]['name']);
    }

    public function test_professionals_endpoint_returns_active_professionals(): void
    {
        $response = $this->get(
            '/booking/' . $this->tenant->slug . '/professionals?service_id=' . $this->service->id . '&token=' . $this->bookingToken->token
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['professionals']);
        $this->assertEquals('Ana', $data['professionals'][0]['name']);
    }

    public function test_slots_endpoint_returns_available_slots(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        $response = $this->get(
            '/booking/' . $this->tenant->slug . '/slots?service_id=' . $this->service->id . '&professional_id=' . $this->professional->id . '&date=' . $date . '&token=' . $this->bookingToken->token
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['slots']);
        $this->assertNotEmpty($data['slots']);
    }

    public function test_slots_endpoint_returns_slots_for_all_professionals_when_none_selected(): void
    {
        Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bruno',
            'email' => 'bruno@test.com',
            'commission_rate' => 40.00,
            'is_active' => true,
        ]);

        $date = Carbon::tomorrow()->toDateString();

        $response = $this->get(
            '/booking/' . $this->tenant->slug . '/slots?service_id=' . $this->service->id . '&date=' . $date . '&token=' . $this->bookingToken->token
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['slots']);
    }

    public function test_can_create_appointment_via_booking_portal(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = $start->copy()->addMinutes(60);

        $response = $this->post(
            '/booking/' . $this->tenant->slug . '/appointments?token=' . $this->bookingToken->token,
            [
                'professional_id' => $this->professional->id,
                'service_id' => $this->service->id,
                'start_at' => $start->toIso8601String(),
                'end_at' => $end->toIso8601String(),
                'client_name' => 'Maria Silva',
                'client_phone' => '11999999999',
            ]
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('appointment', $data);

        $this->assertDatabaseHas('appointments', [
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'phone' => '11999999999',
            'name' => 'Maria Silva',
        ]);
    }

    public function test_booking_portal_prevents_double_booking(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = $start->copy()->addMinutes(60);

        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => Client::withoutGlobalScopes()->create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Existing Client',
                'phone' => '11888888888',
            ])->id,
            'service_id' => $this->service->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->post(
            '/booking/' . $this->tenant->slug . '/appointments?token=' . $this->bookingToken->token,
            [
                'professional_id' => $this->professional->id,
                'service_id' => $this->service->id,
                'start_at' => $start->toIso8601String(),
                'end_at' => $end->toIso8601String(),
                'client_name' => 'New Client',
                'client_phone' => '11999999999',
            ]
        );

        $response->assertStatus(409);
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('conflict', $data['error']);
    }

    public function test_booking_appointment_appears_in_dashboard_calendar(): void
    {
        TenantContext::setCurrent($this->tenant->id);

        $user = \App\Models\User::withoutGlobalScopes()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = $start->copy()->addMinutes(60);

        $this->post(
            '/booking/' . $this->tenant->slug . '/appointments?token=' . $this->bookingToken->token,
            [
                'professional_id' => $this->professional->id,
                'service_id' => $this->service->id,
                'start_at' => $start->toIso8601String(),
                'end_at' => $end->toIso8601String(),
                'client_name' => 'Maria Silva',
                'client_phone' => '11999999999',
            ]
        );

        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $appointment = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('professional_id', $this->professional->id)
            ->first();

        $this->assertNotNull($appointment, 'Appointment was created');
    }

    public function test_token_generation_returns_valid_url(): void
    {
        $response = $this->actingAs(
            \App\Models\User::withoutGlobalScopes()->create([
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
                'role' => 'admin',
            ])
        )->post('/booking/' . $this->tenant->slug . '/token');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('url', $data);
        $this->assertStringContainsString('/booking/' . $this->tenant->slug, $data['url']);
        $this->assertStringContainsString('token=', $data['url']);
    }
}