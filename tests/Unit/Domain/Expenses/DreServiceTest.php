<?php

namespace Tests\Unit\Domain\Expenses;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Expenses\DTO\DreReport;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Services\DreService;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DreServiceTest extends TestCase
{
    use RefreshDatabase;

    private DreService $dreService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dreService = new DreService();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_dre_formula_with_revenue_commissions_and_expenses(): void
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
            'amount' => 50000,
            'payment_method' => PaymentMethod::MONEY,
            'created_at' => $start->copy()->addDays(5),
        ]);
        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 30000,
            'payment_method' => PaymentMethod::PIX,
            'created_at' => $start->copy()->addDays(10),
        ]);

        $professional = \App\Models\Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Prof Test',
            'email' => 'prof@test.com',
            'commission_rate' => 40.0,
        ]);

        CommissionRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $professional->id,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_gross' => 80000,
            'total_commission' => 20000,
            'status' => CommissionRunStatus::CALCULATED,
        ]);

        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 5000,
            'is_recurring' => true,
            'due_date' => $start->copy()->addDays(15),
        ]);
        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 3000,
            'is_recurring' => false,
            'due_date' => $start->copy()->addDays(20),
        ]);

        $report = $this->dreService->calculateMonthlyReport($this->tenant->id, $year, $month);

        $this->assertEquals(80000, $report->totalRevenue);
        $this->assertEquals(20000, $report->totalCommission);
        $this->assertEquals(8000, $report->totalExpenses);
        $this->assertEquals(52000, $report->netProfit);
        $this->assertEquals(65.0, $report->profitMarginPercentage);
    }

    public function test_profit_margin_calculation(): void
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
            'amount' => 100000,
            'payment_method' => PaymentMethod::MONEY,
            'created_at' => $start->copy()->addDays(1),
        ]);

        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 20000,
            'is_recurring' => false,
            'due_date' => $start->copy()->addDays(5),
        ]);

        $report = $this->dreService->calculateMonthlyReport($this->tenant->id, $year, $month);

        $this->assertEquals(100000, $report->totalRevenue);
        $this->assertEquals(0, $report->totalCommission);
        $this->assertEquals(20000, $report->totalExpenses);
        $this->assertEquals(80000, $report->netProfit);
        $this->assertEquals(80.0, $report->profitMarginPercentage);
    }

    public function test_zero_revenue_edge_case(): void
    {
        $report = $this->dreService->calculateMonthlyReport($this->tenant->id, 2026, 5);

        $this->assertEquals(0, $report->totalRevenue);
        $this->assertEquals(0, $report->totalCommission);
        $this->assertEquals(0, $report->totalExpenses);
        $this->assertEquals(0, $report->netProfit);
        $this->assertEquals(0.0, $report->profitMarginPercentage);
    }

    public function test_negative_profit_margin(): void
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
            'amount' => 10000,
            'payment_method' => PaymentMethod::MONEY,
            'created_at' => $start->copy()->addDays(1),
        ]);

        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 15000,
            'is_recurring' => false,
            'due_date' => $start->copy()->addDays(5),
        ]);

        $report = $this->dreService->calculateMonthlyReport($this->tenant->id, $year, $month);

        $this->assertEquals(10000, $report->totalRevenue);
        $this->assertEquals(-5000, $report->netProfit);
        $this->assertEquals(-50.0, $report->profitMarginPercentage);
    }

    public function test_expense_dre_empty_returns_zero_report(): void
    {
        $report = $this->dreService->calculateMonthlyReport($this->tenant->id, 2026, 12);

        $this->assertInstanceOf(DreReport::class, $report);
        $this->assertEquals(2026, $report->year);
        $this->assertEquals(12, $report->month);
        $this->assertEquals(0, $report->totalRevenue);
        $this->assertEquals(0, $report->totalCommission);
        $this->assertEquals(0, $report->totalExpenses);
        $this->assertEquals(0, $report->netProfit);
    }
}