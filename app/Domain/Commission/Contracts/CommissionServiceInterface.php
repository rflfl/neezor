<?php

namespace App\Domain\Commission\Contracts;

use App\Domain\Commission\Models\CommissionPayment;
use App\Domain\Scheduling\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CommissionServiceInterface
{
    public function calculateForAppointment(Appointment $appointment): int;

    public function calculateForPeriod(int $tenantId, int $professionalId, Carbon $start, Carbon $end): Collection;

    public function recordPayment(
        int $tenantId,
        int $commissionRunId,
        int $amount,
        Carbon $paidAt,
        ?string $note,
        ?int $recordedBy
    ): CommissionPayment;
}