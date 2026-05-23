<?php

namespace App\Domain\Cashbox\Services;

use App\Domain\Cashbox\Contracts\CashboxServiceInterface;
use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashboxService implements CashboxServiceInterface
{
    public function open(int $tenantId, Carbon $date, int $openingBalance, ?int $userId = null): CashboxDay
    {
        $existingDay = CashboxDay::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($existingDay) {
            throw new InvalidArgumentException('Cashbox day already exists for this date.');
        }

        return CashboxDay::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'date' => $date->toDateString(),
            'opening_balance' => $openingBalance,
            'status' => CashboxStatus::OPEN,
        ]);
    }

    public function recordEntry(
        CashboxDay $cashboxDay,
        int $amount,
        string $paymentMethod,
        ?int $appointmentId = null,
        ?string $note = null,
        ?int $userId = null
    ): CashMovement {
        $this->ensureCashboxIsOpen($cashboxDay);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Entry amount must be positive.');
        }

        return CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $cashboxDay->tenant_id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::ENTRY,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'appointment_id' => $appointmentId,
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    public function recordExpense(
        CashboxDay $cashboxDay,
        int $amount,
        string $paymentMethod,
        int $categoryId,
        ?string $note = null,
        ?int $userId = null
    ): CashMovement {
        $this->ensureCashboxIsOpen($cashboxDay);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be positive.');
        }

        return CashMovement::withoutGlobalScopes()->create([
            'tenant_id' => $cashboxDay->tenant_id,
            'cashbox_day_id' => $cashboxDay->id,
            'type' => CashMovementType::EXPENSE,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'expense_category_id' => $categoryId,
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    public function close(CashboxDay $cashboxDay, int $closingBalance, ?int $userId = null): CashboxDay
    {
        $this->ensureCashboxIsOpen($cashboxDay);

        $cashboxDay->update([
            'closing_balance' => $closingBalance,
            'status' => CashboxStatus::CLOSED,
        ]);

        return $cashboxDay->fresh();
    }

    public function getCurrentDay(int $tenantId): ?CashboxDay
    {
        return CashboxDay::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();
    }

    public function getByDate(int $tenantId, Carbon $date): ?CashboxDay
    {
        return CashboxDay::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    private function ensureCashboxIsOpen(CashboxDay $cashboxDay): void
    {
        if ($cashboxDay->status !== CashboxStatus::OPEN) {
            throw new InvalidArgumentException('Cashbox day is not open.');
        }
    }
}