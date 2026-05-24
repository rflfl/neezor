<?php

namespace Tests\Unit\Domain\Notifications\Jobs;

use App\Domain\Notifications\Jobs\SendReminderJob;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendReminderJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_appointment_creation_dispatches_reminder_job(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);

        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);

        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);

        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointmentService = new AppointmentService(new AvailabilityService());
        $appointment = $appointmentService->create([
            'tenant_id' => $tenant->id,
            'professional_id' => $professional->id,
            'client_id' => $client->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        Queue::assertPushed(SendReminderJob::class, function ($job) use ($appointment) {
            return $job->appointmentId === $appointment->id;
        });
    }

    public function test_appointment_cancellation_dispatches_cancellation_job(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setCurrent($tenant->id);

        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);

        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);

        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $start = Carbon::tomorrow()->setTime(10, 0);

        $appointmentService = new AppointmentService(new AvailabilityService());
        $appointment = $appointmentService->create([
            'tenant_id' => $tenant->id,
            'professional_id' => $professional->id,
            'client_id' => $client->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
        ]);

        Queue::fake()->assertNotPushed(\App\Domain\Notifications\Jobs\SendCancellationJob::class);

        $appointmentService->transitionTo($appointment, 'cancelled');

        Queue::assertPushed(\App\Domain\Notifications\Jobs\SendCancellationJob::class);
    }

    public function test_job_has_correct_queue_and_tries(): void
    {
        $job = new SendReminderJob(1);

        $this->assertEquals('notifications', $job->queue);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 60, 300], $job->backoff);
    }
}