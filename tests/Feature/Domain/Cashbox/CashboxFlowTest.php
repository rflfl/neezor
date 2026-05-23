<?php

namespace Tests\Feature\Domain\Cashbox;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Cashbox\Services\CashboxService;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashboxFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private CashboxDay $cashboxDay;
    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->cashboxDay = $this->openCashbox();
        $this->category = ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function openCashbox(): CashboxDay
    {
        return CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'date' => Carbon::today()->toDateString(),
            'opening_balance' => 10000,
            'status' => CashboxStatus::OPEN,
        ]);
    }

    public function test_complete_cashbox_day_flow(): void
    {
        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $this->cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 5000,
            'payment_method' => PaymentMethod::MONEY,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $this->cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => 3000,
            'payment_method' => PaymentMethod::PIX,
        ]);

        CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'cashbox_day_id' => $this->cashboxDay->id,
            'type' => CashMovementType::EXPENSE,
            'amount' => 1000,
            'payment_method' => PaymentMethod::MONEY,
            'expense_category_id' => $this->category->id,
        ]);

        $openingBalance = (int) $this->cashboxDay->opening_balance;
        $totalEntries = (int) CashMovement::withoutGlobalScopes()
            ->where('cashbox_day_id', $this->cashboxDay->id)
            ->where('type', CashMovementType::ENTRY)
            ->sum('amount');
        $totalExpenses = (int) CashMovement::withoutGlobalScopes()
            ->where('cashbox_day_id', $this->cashboxDay->id)
            ->where('type', CashMovementType::EXPENSE)
            ->sum('amount');

        $expectedClosing = $openingBalance + $totalEntries - $totalExpenses;
        $this->assertEquals(17000, $expectedClosing);

        $this->cashboxDay->update([
            'closing_balance' => 17000,
            'status' => CashboxStatus::CLOSED,
        ]);

        $closedDay = CashboxDay::withoutGlobalScopes()->find($this->cashboxDay->id);
        $this->assertEquals(CashboxStatus::CLOSED, $closedDay->status);
    }

    public function test_appointment_completion_triggers_cash_entry(): void
    {
        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);
        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);
        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $start = Carbon::tomorrow()->setTime(10, 0);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'professional_id' => $professional->id,
            'client_id' => $client->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
            'status' => Appointment::STATUS_IN_PROGRESS,
            'price' => 5000,
            'payment_method' => PaymentMethod::MONEY->value,
        ]);

        TenantContext::setCurrent($this->tenant->id);
        $appointmentService = new AppointmentService(
            new AvailabilityService(),
            new CashboxService()
        );

        $appointmentService->transitionTo($appointment, Appointment::STATUS_COMPLETED);

        $cashEntry = CashMovement::withoutGlobalScopes()
            ->where('appointment_id', $appointment->id)
            ->first();

        $this->assertNotNull($cashEntry);
        $this->assertEquals(5000, $cashEntry->amount);
        $this->assertEquals(CashMovementType::ENTRY, $cashEntry->type);
    }

    public function test_cash_entry_not_created_when_no_open_cashbox(): void
    {
        $otherTenant = Tenant::factory()->create();

        $closedDay = CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'date' => Carbon::yesterday()->toDateString(),
            'opening_balance' => 0,
            'closing_balance' => 0,
            'status' => CashboxStatus::CLOSED,
        ]);

        $professional = Professional::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'commission_rate' => 40.00,
        ]);
        $client = Client::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Maria',
            'phone' => '11999999999',
        ]);
        $service = Service::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 5000,
        ]);

        $start = Carbon::tomorrow()->setTime(14, 0);
        $appointment = Appointment::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'professional_id' => $professional->id,
            'client_id' => $client->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(60),
            'status' => Appointment::STATUS_IN_PROGRESS,
            'price' => 5000,
            'payment_method' => PaymentMethod::MONEY->value,
        ]);

        TenantContext::setCurrent($otherTenant->id);
        $appointmentService = new AppointmentService(
            new AvailabilityService(),
            new CashboxService()
        );

        $appointmentService->transitionTo($appointment, Appointment::STATUS_COMPLETED);

        $cashEntry = CashMovement::withoutGlobalScopes()
            ->where('appointment_id', $appointment->id)
            ->first();

        $this->assertNull($cashEntry);
    }
}