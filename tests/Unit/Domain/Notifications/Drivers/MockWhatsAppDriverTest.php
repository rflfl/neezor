<?php

namespace Tests\Unit\Domain\Notifications\Drivers;

use App\Domain\Customers\Models\Client;
use App\Domain\Notifications\Drivers\MockWhatsAppDriver;
use App\Domain\Packages\Models\Package;
use App\Domain\Packages\Models\PackageSession;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MockWhatsAppDriverTest extends TestCase
{
    use RefreshDatabase;

    private MockWhatsAppDriver $driver;
    private Tenant $tenant;
    private Client $client;
    private Professional $professional;
    private Service $service;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new MockWhatsAppDriver();
        $this->tenant = Tenant::factory()->create();
        TenantContext::setCurrent($this->tenant->id);

        $this->client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria Silva',
            'phone' => '11999999999',
            'email' => 'maria@example.com',
        ]);

        $this->professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
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

        $this->appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'start_at' => Carbon::tomorrow()->setTime(10, 0),
            'end_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => Appointment::STATUS_SCHEDULED,
            'price' => 5000,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_send_reminder_logs_to_laravel_log(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WhatsApp notification'
                    && isset($context['type'])
                    && $context['type'] === 'reminder'
                    && isset($context['appointment_id']);
            });

        $this->driver->sendReminder($this->appointment->id);
    }

    public function test_send_confirmation_logs_to_laravel_log(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WhatsApp notification'
                    && isset($context['type'])
                    && $context['type'] === 'confirmation';
            });

        $this->driver->sendConfirmation($this->appointment->id);
    }

    public function test_send_cancellation_logs_with_reason(): void
    {
        $reason = 'Cliente pediu cancelamento';

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($reason) {
                return $message === 'WhatsApp notification'
                    && isset($context['type'])
                    && $context['type'] === 'cancellation'
                    && isset($context['reason'])
                    && $context['reason'] === $reason;
            });

        $this->driver->sendCancellation($this->appointment->id, $reason);
    }

    public function test_send_reminder_does_nothing_for_missing_appointment(): void
    {
        Log::shouldReceive('info')->never();
        $this->driver->sendReminder(99999);
    }

    public function test_send_package_alert_logs_to_laravel_log(): void
    {
        $package = Package::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote Corte',
            'price' => 30000,
            'valid_until_days' => 180,
        ]);

        $session = PackageSession::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'package_id' => $package->id,
            'service_id' => $this->service->id,
            'sessions_remaining' => 2,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'WhatsApp notification'
                    && isset($context['type'])
                    && $context['type'] === 'package_alert';
            });

        $this->driver->sendPackageAlert($this->client->id, $package->id);
    }
}