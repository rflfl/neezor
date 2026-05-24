<?php

namespace Tests\Unit\Domain\Commission;

use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Commission\Models\ProfessionalServiceCommission;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private \App\Domain\Commission\Services\CommissionService $service;
    private Tenant $tenant;
    private Professional $professional;
    private Service $serviceModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new \App\Domain\Commission\Services\CommissionService();
        $this->tenant = Tenant::factory()->create();
        $this->professional = Professional::factory()->create([
            'tenant_id' => $this->tenant->id,
            'commission_rate' => 0.40,
        ]);
        $this->serviceModel = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10000,
        ]);
        \App\Services\TenantContext::setCurrent($this->tenant->id);
    }

    public function test_calculate_for_appointment_uses_default_rate(): void
    {
        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commission = $this->service->calculateForAppointment($appointment);

        $this->assertEquals(4000, $commission);
    }

    public function test_calculate_for_appointment_uses_service_specific_rate(): void
    {
        $otherService = Service::factory()->create(['tenant_id' => $this->tenant->id]);
        $specificCommission = new \App\Domain\Commission\Models\ProfessionalServiceCommission();
        $specificCommission->forceFill([
            'professional_id' => $this->professional->id,
            'service_id' => $otherService->id,
            'commission_rate' => 0.50,
        ]);
        $specificCommission->save();

        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $otherService->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commission = $this->service->calculateForAppointment($appointment);

        $this->assertEquals(5000, $commission);
    }

    public function test_calculate_for_appointment_zero_when_price_is_zero(): void
    {
        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 0,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commission = $this->service->calculateForAppointment($appointment);

        $this->assertEquals(0, $commission);
    }

    public function test_calculate_for_appointment_zero_when_price_is_null(): void
    {
        $appointment = Appointment::factory()->make([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => null,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commission = $this->service->calculateForAppointment($appointment);

        $this->assertEquals(0, $commission);
    }

    public function test_calculate_for_appointment_zero_when_professional_not_found(): void
    {
        $appointment = Appointment::factory()->make([
            'tenant_id' => $this->tenant->id,
            'professional_id' => 999999,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commission = $this->service->calculateForAppointment($appointment);

        $this->assertEquals(0, $commission);
    }

    public function test_calculate_for_period_aggregates_appointments(): void
    {
        $periodStart = Carbon::parse('2026-01-01');
        $periodEnd = Carbon::parse('2026-01-31');

        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);
        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 15000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(10),
        ]);

        $result = $this->service->calculateForPeriod(
            $this->tenant->id,
            $this->professional->id,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals(25000, $result['total_gross']);
        $this->assertEquals(10000, $result['total_commission']);
        $this->assertEquals(2, $result['appointments']->count());
        $this->assertInstanceOf(CommissionRun::class, $result['commission_run']);
        $this->assertEquals('calculated', $result['commission_run']->status->value);
    }

    public function test_calculate_for_period_ignores_non_completed_appointments(): void
    {
        $periodStart = Carbon::parse('2026-01-01');
        $periodEnd = Carbon::parse('2026-01-31');

        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);
        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_SCHEDULED,
            'start_at' => $periodStart->copy()->addDays(6),
        ]);

        $result = $this->service->calculateForPeriod(
            $this->tenant->id,
            $this->professional->id,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals(1, $result['appointments']->count());
        $this->assertEquals(10000, $result['total_gross']);
    }

    public function test_calculate_for_period_creates_new_commission_run_if_not_exists(): void
    {
        $periodStart = Carbon::create(2026, 1, 1, 0, 0, 0);
        $periodEnd = Carbon::create(2026, 1, 31, 23, 59, 59);

        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);

        $result = $this->service->calculateForPeriod(
            $this->tenant->id,
            $this->professional->id,
            $periodStart,
            $periodEnd
        );

        $this->assertDatabaseHas('commission_runs', [
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
        ]);
    }

    public function test_calculate_for_period_updates_existing_commission_run(): void
    {
        $periodStart = Carbon::create(2026, 3, 1, 0, 0, 0);
        $periodEnd = Carbon::create(2026, 3, 31, 23, 59, 59);
        $existingRunId = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'total_gross' => 0,
            'total_commission' => 0,
            'status' => 'draft',
        ])->id;

        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);

        $result = $this->service->calculateForPeriod(
            $this->tenant->id,
            $this->professional->id,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals(10000, $result['commission_run']->fresh()->total_gross);
        $this->assertEquals('calculated', $result['commission_run']->status->value);
    }

    public function test_record_payment_creates_payment_record(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $payment = $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            5000,
            now(),
            'First installment',
            null
        );

        $this->assertDatabaseHas('commission_payments', [
            'commission_run_id' => $run->id,
            'amount' => 5000,
        ]);
        $this->assertEquals('First installment', $payment->note);
    }

    public function test_record_payment_throws_for_zero_amount(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            0,
            now(),
            null,
            null
        );
    }

    public function test_record_payment_throws_for_negative_amount(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            -100,
            now(),
            null,
            null
        );
    }

    public function test_record_payment_throws_when_amount_exceeds_pending(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            6000,
            now(),
            null,
            null
        );
    }

    public function test_record_payment_marks_run_as_paid_when_full(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            5000,
            now(),
            null,
            null
        );

        $this->assertEquals('paid', $run->fresh()->status->value);
    }

    public function test_record_payment_does_not_mark_run_paid_when_partial(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->service->recordPayment(
            $this->tenant->id,
            $run->id,
            2500,
            now(),
            'First installment',
            null
        );

        $this->assertEquals('calculated', $run->fresh()->status->value);
    }

    public function test_record_adjustment_with_mandatory_reason(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $updatedRun = $this->service->recordAdjustment(
            $this->tenant->id,
            $run->id,
            500,
            'Bonus for exceptional service',
            null
        );

        $this->assertEquals(5500, $updatedRun->total_commission);
    }

    public function test_record_adjustment_throws_for_empty_reason(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->recordAdjustment(
            $this->tenant->id,
            $run->id,
            500,
            '   ',
            null
        );
    }

    public function test_record_adjustment_caps_at_zero(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 1000,
        ]);

        $updatedRun = $this->service->recordAdjustment(
            $this->tenant->id,
            $run->id,
            -2000,
            'Deduction for no-show',
            null
        );

        $this->assertEquals(0, $updatedRun->total_commission);
    }

    public function test_calculate_for_period_respects_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherProfessional = Professional::factory()->create([
            'tenant_id' => $otherTenant->id,
            'commission_rate' => 0.40,
        ]);
        $periodStart = Carbon::parse('2026-01-01');
        $periodEnd = Carbon::parse('2026-01-31');

        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);
        Appointment::factory()->create([
            'tenant_id' => $otherTenant->id,
            'professional_id' => $otherProfessional->id,
            'service_id' => $this->serviceModel->id,
            'price' => 20000,
            'status' => Appointment::STATUS_COMPLETED,
            'start_at' => $periodStart->copy()->addDays(5),
        ]);

        $result = $this->service->calculateForPeriod(
            $this->tenant->id,
            $this->professional->id,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals(10000, $result['total_gross']);
        $this->assertEquals(1, $result['appointments']->count());
    }
}