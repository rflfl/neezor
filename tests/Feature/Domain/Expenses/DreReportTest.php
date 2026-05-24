<?php

namespace Tests\Feature\Domain\Expenses;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Expenses\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DreReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        TenantContext::setCurrent($this->tenant->id);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_dre_report_returns_monthly_summary(): void
    {
        $year = 2026;
        $month = 5;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => $start->copy()->startOfMonth(),
            'opening_balance' => 0,
            'status' => \App\Domain\Cashbox\Enums\CashboxStatus::OPEN,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 40000,
            'payment_method' => PaymentMethod::MONEY,
            'created_at' => $start->copy()->addDays(3),
        ]);
        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 60000,
            'payment_method' => PaymentMethod::PIX,
            'created_at' => $start->copy()->addDays(10),
        ]);

        $professional = \App\Models\Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Prof Test',
            'email' => 'proftest@example.com',
            'commission_rate' => 25.0,
        ]);

        CommissionRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $professional->id,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_gross' => 100000,
            'total_commission' => 25000,
            'status' => CommissionRunStatus::CALCULATED,
        ]);

        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 8000,
            'is_recurring' => true,
            'due_date' => $start->copy()->addDays(1),
        ]);
        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 2000,
            'is_recurring' => false,
            'due_date' => $start->copy()->addDays(12),
        ]);

        $response = $this->actingAs($this->user)->getJson("/dashboard/dre?year={$year}&month={$month}");

        $response->assertOk()
            ->assertJsonPath('report.year', $year)
            ->assertJsonPath('report.month', $month)
            ->assertJsonPath('report.totalRevenue', 100000)
            ->assertJsonPath('report.totalCommission', 25000)
            ->assertJsonPath('report.totalExpenses', 10000)
            ->assertJsonPath('report.netProfit', 65000);
        $this->assertEquals(65.0, $response->json('report.profitMarginPercentage'));
    }

    public function test_dre_report_defaults_to_current_month(): void
    {
        $response = $this->actingAs($this->user)->getJson('/dashboard/dre');

        $response->assertOk();
        $report = $response->json('report');
        $this->assertEquals(now()->year, $report['year']);
        $this->assertEquals(now()->month, $report['month']);
    }

    public function test_dre_with_only_expenses_no_revenue(): void
    {
        $year = 2026;
        $month = 6;
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 5000,
            'is_recurring' => true,
            'due_date' => $start->copy()->addDays(5),
        ]);

        $response = $this->actingAs($this->user)->getJson("/dashboard/dre?year={$year}&month={$month}");

        $response->assertOk();
        $report = $response->json('report');
        $this->assertEquals(0, $report['totalRevenue']);
        $this->assertEquals(5000, $report['totalExpenses']);
        $this->assertEquals(-5000, $report['netProfit']);
        $this->assertEquals(0.0, $report['profitMarginPercentage']);
    }
}