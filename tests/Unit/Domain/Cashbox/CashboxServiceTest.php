<?php

namespace Tests\Unit\Domain\Cashbox;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Cashbox\Services\CashboxService;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CashboxServiceTest extends TestCase
{
    use RefreshDatabase;

    private CashboxService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CashboxService();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_open_creates_new_cashbox_day(): void
    {
        $date = Carbon::today();
        $openingBalance = 10000;

        $cashboxDay = $this->service->open($this->tenant->id, $date, $openingBalance);

        $this->assertInstanceOf(CashboxDay::class, $cashboxDay);
        $this->assertEquals($this->tenant->id, $cashboxDay->tenant_id);
        $this->assertEquals($openingBalance, $cashboxDay->opening_balance);
        $this->assertEquals(CashboxStatus::OPEN, $cashboxDay->status);
    }

    public function test_open_throws_when_day_already_exists(): void
    {
        $date = Carbon::today();

        $this->service->open($this->tenant->id, $date, 0);

        $this->expectException(InvalidArgumentException::class);
        $this->service->open($this->tenant->id, $date, 0);
    }

    public function test_record_entry_creates_cash_movement(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::OPEN,
        ]);
        $amount = 5000;

        $entry = $this->service->recordEntry(
            $cashboxDay,
            $amount,
            PaymentMethod::MONEY->value,
            null,
            'Test entry'
        );

        $this->assertInstanceOf(CashMovement::class, $entry);
        $this->assertEquals($amount, $entry->amount);
        $this->assertEquals(CashMovementType::ENTRY, $entry->type);
        $this->assertEquals(PaymentMethod::MONEY, $entry->payment_method);
    }

    public function test_record_entry_throws_for_closed_cashbox(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::CLOSED,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordEntry($cashboxDay, 5000, PaymentMethod::MONEY->value);
    }

    public function test_record_entry_throws_for_zero_amount(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::OPEN,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordEntry($cashboxDay, 0, PaymentMethod::MONEY->value);
    }

    public function test_record_expense_creates_cash_movement(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::OPEN,
        ]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $amount = 2000;

        $expense = $this->service->recordExpense(
            $cashboxDay,
            $amount,
            PaymentMethod::MONEY->value,
            $category->id
        );

        $this->assertInstanceOf(CashMovement::class, $expense);
        $this->assertEquals($amount, $expense->amount);
        $this->assertEquals(CashMovementType::EXPENSE, $expense->type);
        $this->assertEquals($category->id, $expense->expense_category_id);
    }

    public function test_record_expense_throws_for_closed_cashbox(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::CLOSED,
        ]);
        $category = ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->recordExpense($cashboxDay, 2000, PaymentMethod::MONEY->value, $category->id);
    }

    public function test_close_updates_cashbox_day_status(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);
        $closingBalance = 15000;

        $closedDay = $this->service->close($cashboxDay, $closingBalance);

        $this->assertEquals(CashboxStatus::CLOSED, $closedDay->status);
        $this->assertEquals($closingBalance, $closedDay->closing_balance);
    }

    public function test_close_throws_for_already_closed_cashbox(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => CashboxStatus::CLOSED,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->close($cashboxDay, 10000);
    }

    public function test_reconciliation_matches_when_balances_equal(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);
        $this->service->recordEntry($cashboxDay, 5000, PaymentMethod::MONEY->value);
        $this->service->recordExpense($cashboxDay, 1000, PaymentMethod::MONEY->value, ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id])->id);

        $expectedClosing = $cashboxDay->expected_closing_balance;
        $closedDay = $this->service->close($cashboxDay, $expectedClosing);

        $this->assertFalse($closedDay->hasDiscrepancy());
        $this->assertEquals(0, $closedDay->discrepancy_amount);
    }

    public function test_reconciliation_detects_discrepancy(): void
    {
        $cashboxDay = CashboxDay::factory()->create([
            'tenant_id' => $this->tenant->id,
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);
        $this->service->recordEntry($cashboxDay, 5000, PaymentMethod::MONEY->value);

        $expectedClosing = $cashboxDay->expected_closing_balance;
        $wrongClosing = $expectedClosing + 500;

        $closedDay = $this->service->close($cashboxDay, $wrongClosing);

        $this->assertTrue($closedDay->hasDiscrepancy());
        $this->assertEquals(500, $closedDay->discrepancy_amount);
    }

    public function test_get_current_day_returns_today_cashbox(): void
    {
        $today = Carbon::today();
        $cashboxDay = $this->service->open($this->tenant->id, $today, 0);

        $current = $this->service->getCurrentDay($this->tenant->id);

        $this->assertNotNull($current);
        $this->assertEquals($cashboxDay->id, $current->id);
    }

    public function test_get_by_date_returns_cashbox_for_specific_date(): void
    {
        $date = Carbon::today()->subDays(5);
        $this->service->open($this->tenant->id, $date, 0);

        $found = $this->service->getByDate($this->tenant->id, $date);

        $this->assertNotNull($found);
        $this->assertEquals($date->toDateString(), $found->date->toDateString());
    }
}