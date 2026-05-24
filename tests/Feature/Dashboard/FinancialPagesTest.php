<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialPagesTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Professional $professional;
    private Client $client;
    private Service $service;
    private ExpenseCategory $cashboxCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['has_completed_onboarding' => true]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@test.com',
            'commission_rate' => 40.0,
        ]);
        $this->client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);
        $this->service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);
        $this->cashboxCategory = ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_cashbox_page_loads_with_empty_state(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $response = $this->getJson(route('dashboard.cashbox.index'));

        $response->assertOk();
        $response->assertJsonStructure(['cashbox']);
    }

    public function test_cashbox_page_shows_open_cashbox(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 5000,
            'payment_method' => 'money',
        ]);

        $response = $this->getJson(route('dashboard.cashbox.index'));

        $response->assertOk();
        $response->assertJsonPath('cashbox.status', 'open');
    }

    public function test_open_cashbox_creates_new_cashbox_day(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $response = $this->post(route('dashboard.cashbox.store'), [
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 10000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cashbox_days', [
            'tenant_id' => $this->tenant->id,
            'opening_balance' => 10000,
        ]);
    }

    public function test_record_entry_creates_cash_movement(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 0,
            'status' => CashboxStatus::OPEN,
        ]);

        $response = $this->post(route('dashboard.cashbox.entry'), [
            'cashbox_day_id' => $cashboxDay->id,
            'amount' => 5000,
            'payment_method' => 'money',
            'note' => 'Test entry',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_movements', [
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'amount' => 5000,
        ]);
    }

    public function test_record_expense_creates_cash_movement(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 0,
            'status' => CashboxStatus::OPEN,
        ]);

        $response = $this->post(route('dashboard.cashbox.expense'), [
            'cashbox_day_id' => $cashboxDay->id,
            'amount' => 1000,
            'payment_method' => 'money',
            'expense_category_id' => $this->cashboxCategory->id,
            'note' => 'Test expense',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_movements', [
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'amount' => 1000,
        ]);
    }

    public function test_close_cashbox_updates_status(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 5000,
            'payment_method' => 'money',
        ]);

        $response = $this->postJson(route('dashboard.cashbox.close'), [
            'cashbox_day_id' => $cashboxDay->id,
            'closing_balance' => 15000,
        ]);

        $response->assertStatus(200);
        $cashboxDay->refresh();
        $this->assertEquals(CashboxStatus::CLOSED, $cashboxDay->status);
        $this->assertEquals(15000, $cashboxDay->closing_balance);
    }

    public function test_commissions_page_loads(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $response = $this->getJson(route('dashboard.commissions.index'));

        $response->assertOk();
        $response->assertJsonStructure(['commission_runs']);
    }

public function test_commissions_page_shows_runs(): void
    {
        $this->actingAs($this->user);

        $run = CommissionRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'period_start' => Carbon::now()->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->endOfMonth()->toDateString(),
            'total_gross' => 5000,
            'total_commission' => 2000,
            'status' => CommissionRunStatus::CALCULATED,
        ]);

        $this->assertNotNull($run->id);

        TenantContext::setCurrent($this->tenant->id);

        $controller = app(\App\Domain\Commission\Controllers\CommissionController::class);
        $request = \Illuminate\Http\Request::create('/dashboard/commissions', 'GET');
        $request->setUserResolver(fn() => $this->user);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $runsBeforeController = \App\Domain\Commission\Models\CommissionRun::withoutGlobalScopes()
            ->where('tenant_id', TenantContext::current())
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->get();

        $response = $controller->index($request);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue(
            count($data['commission_runs'] ?? []) > 0 || $runsBeforeController->count() === 0,
            'Controller returned ' . count($data['commission_runs'] ?? []) . ' runs, but direct query found ' . $runsBeforeController->count() . '. ' .
            'This suggests a TenantContext issue during controller execution.'
        );
    }

    public function test_commission_professional_page_loads(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $start = Carbon::now()->startOfMonth();
        Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'start_at' => $start->copy()->addHours(10),
            'end_at' => $start->copy()->addHours(11),
            'status' => Appointment::STATUS_COMPLETED,
            'price' => 5000,
        ]);

        $response = $this->getJson(route('dashboard.commissions.professional', $this->professional->id));

        $response->assertOk();
        $response->assertJsonStructure(['appointments']);
    }

    public function test_mark_commission_paid(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $run = CommissionRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $this->professional->id,
            'period_start' => Carbon::now()->startOfMonth()->toDateString(),
            'period_end' => Carbon::now()->endOfMonth()->toDateString(),
            'total_gross' => 5000,
            'total_commission' => 2000,
            'status' => CommissionRunStatus::CALCULATED,
        ]);

        $response = $this->post(route('dashboard.commissions.pay'), [
            'commission_run_id' => $run->id,
            'amount' => 2000,
            'paid_at' => Carbon::today()->toDateString(),
            'note' => 'Full payment',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('commission_payments', [
            'commission_run_id' => $run->id,
            'amount' => 2000,
        ]);
    }

    public function test_dre_page_loads(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $response = $this->getJson(route('dashboard.dre'));

        $response->assertOk();
        $response->assertJsonStructure(['report']);
    }

    public function test_dre_page_shows_monthly_report(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $cashboxDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 0,
            'status' => CashboxStatus::CLOSED,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 20000,
            'payment_method' => 'money',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->getJson(route('dashboard.dre'));

        $response->assertOk();
        $this->assertArrayHasKey('report', $response->json());
    }

    public function test_expenses_page_loads(): void
    {
        $this->actingAs($this->user);
        TenantContext::setCurrent($this->tenant->id);

        $response = $this->getJson(route('dashboard.expenses.index'));

        $response->assertOk();
        $response->assertJsonStructure(['expenses']);
    }
}