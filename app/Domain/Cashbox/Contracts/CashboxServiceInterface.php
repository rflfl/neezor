<?php

namespace App\Domain\Cashbox\Contracts;

use App\Domain\Cashbox\Models\CashboxDay;
use App\Domain\Cashbox\Models\CashMovement;
use Carbon\Carbon;

interface CashboxServiceInterface
{
    public function open(int $tenantId, Carbon $date, int $openingBalance, ?int $userId = null): CashboxDay;

    public function recordEntry(
        CashboxDay $cashboxDay,
        int $amount,
        string $paymentMethod,
        ?int $appointmentId = null,
        ?string $note = null,
        ?int $userId = null
    ): CashMovement;

    public function recordExpense(
        CashboxDay $cashboxDay,
        int $amount,
        string $paymentMethod,
        int $categoryId,
        ?string $note = null,
        ?int $userId = null
    ): CashMovement;

    public function close(CashboxDay $cashboxDay, int $closingBalance, ?int $userId = null): CashboxDay;

    public function getCurrentDay(int $tenantId): ?CashboxDay;

    public function getByDate(int $tenantId, Carbon $date): ?CashboxDay;
}