<?php

namespace Tests\Feature\Domain\Commission;

use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Scheduling\Models\Appointment;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CommissionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Professional $professional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['has_completed_onboarding' => true]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->professional = Professional::factory()->create([
            'tenant_id' => $this->tenant->id,
            'commission_rate' => 0.40,
        ]);
        \App\Services\TenantContext::setCurrent($this->tenant->id);
    }

    public function test_appointment_completion_triggers_commission_calculation(): void
    {
        $appointment = Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'price' => 10000,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        $commissionService = app(\App\Domain\Commission\Services\CommissionService::class);
        $commission = $commissionService->calculateForAppointment($appointment);

        $this->assertEquals(4000, $commission);
    }

    public function test_pay_commission_records_payment(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', [
            'commission_run_id' => $run->id,
            'amount' => 5000,
            'paid_at' => now()->toDateTimeString(),
            'note' => 'Full payment for the month',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commission_payments', [
            'commission_run_id' => $run->id,
            'amount' => 5000,
            'note' => 'Full payment for the month',
        ]);

        $this->assertEquals('paid', $run->fresh()->status->value);
    }

    public function test_pay_commission_partial(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', [
            'commission_run_id' => $run->id,
            'amount' => 2500,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(201);
        $this->assertEquals('calculated', $run->fresh()->status->value);
    }

    public function test_pay_commission_validates_required_fields(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['commission_run_id', 'amount', 'paid_at']);
    }

    public function test_commissions_list_returns_runs(): void
    {
        CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_gross' => 10000,
            'total_commission' => 4000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/dashboard/commissions');

        $response->assertStatus(200);
        $response->assertJsonStructure(['commission_runs']);
    }

    public function test_commissions_by_professional_returns_run_with_appointments(): void
    {
        CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_gross' => 10000,
            'total_commission' => 4000,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/dashboard/commissions/professional/' . $this->professional->id
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'commission_run',
            'total_gross',
            'total_commission',
        ]);
    }

    public function test_commission_adjustment_requires_reason(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/adjust', [
            'commission_run_id' => $run->id,
            'adjustment' => 500,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_commission_adjustment_applies_deduction(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/adjust', [
            'commission_run_id' => $run->id,
            'adjustment' => -500,
            'reason' => 'Client complaint - partial refund',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(4500, $run->fresh()->total_commission);
    }

    public function test_commission_flow_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherProfessional = Professional::factory()->create([
            'tenant_id' => $otherTenant->id,
            'commission_rate' => 0.40,
        ]);

        CommissionRun::factory()->create([
            'tenant_id' => $otherTenant->id,
            'professional_id' => $otherProfessional->id,
            'total_gross' => 20000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/dashboard/commissions');

        $response->assertStatus(200);
        $runs = $response->json('commission_runs');
        $this->assertEmpty($runs);
    }

    public function test_pay_commission_exceeds_pending_returns_error(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 5000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', [
            'commission_run_id' => $run->id,
            'amount' => 10000,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Payment amount exceeds pending commission.');
    }

    public function test_record_payment_updates_total_paid_attribute(): void
    {
        $run = CommissionRun::factory()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'total_commission' => 10000,
        ]);

        $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', [
            'commission_run_id' => $run->id,
            'amount' => 3000,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($this->user)->postJson('/dashboard/commissions/pay', [
            'commission_run_id' => $run->id,
            'amount' => 3000,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $run->refresh();
        $this->assertEquals(6000, $run->total_paid);
        $this->assertEquals(4000, $run->pending_amount);
    }
}