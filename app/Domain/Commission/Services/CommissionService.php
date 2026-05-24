<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Contracts\CommissionServiceInterface;
use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Domain\Commission\Models\CommissionPayment;
use App\Domain\Commission\Models\CommissionRun;
use App\Domain\Commission\Models\ProfessionalServiceCommission;
use App\Domain\Scheduling\Models\Appointment;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CommissionService implements CommissionServiceInterface
{
    public function calculateForAppointment(Appointment $appointment): int
    {
        $price = $appointment->price ?? 0;

        if ($price <= 0) {
            return 0;
        }

        $rate = $this->getEffectiveRate($appointment->professional_id, $appointment->service_id);

        $priceCents = (int) round($price);
        $rateBps = (int) round($rate * 10000);
        return (int) round($priceCents * $rateBps / 10000);
    }

    public function calculateForPeriod(int $tenantId, int $professionalId, Carbon $start, Carbon $end): Collection
    {
        $appointments = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_id', $professionalId)
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereBetween('start_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();

        $totalGross = 0;
        $totalCommission = 0;

        foreach ($appointments as $appointment) {
            $totalGross += $appointment->price ?? 0;
            $totalCommission += $this->calculateForAppointment($appointment);
        }

        $run = CommissionRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_id', $professionalId)
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->first();

        if ($run && $run->status === CommissionRunStatus::PAID) {
            throw new InvalidArgumentException(
                'Cannot recalculate a paid commission run. Reverse the payment first.'
            );
        }

        if ($run) {
            $run->update([
                'total_gross' => $totalGross,
                'total_commission' => $totalCommission,
                'status' => CommissionRunStatus::CALCULATED,
            ]);
        } else {
            $run = CommissionRun::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'professional_id' => $professionalId,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'total_gross' => $totalGross,
                'total_commission' => $totalCommission,
                'status' => CommissionRunStatus::CALCULATED,
            ]);
        }

        return new Collection([
            'commission_run' => $run,
            'appointments' => $appointments,
            'total_gross' => $totalGross,
            'total_commission' => $totalCommission,
        ]);
    }

    public function recordPayment(
        int $tenantId,
        int $commissionRunId,
        int $amount,
        Carbon $paidAt,
        ?string $note,
        ?int $recordedBy
    ): CommissionPayment {
        $run = CommissionRun::withoutGlobalScopes()
            ->where('id', $commissionRunId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > $run->pending_amount) {
            throw new InvalidArgumentException('Payment amount exceeds pending commission.');
        }

        $payment = CommissionPayment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'commission_run_id' => $commissionRunId,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'note' => $note,
            'recorded_by' => $recordedBy,
        ]);

        if ($run->pending_amount <= 0) {
            $run->update(['status' => CommissionRunStatus::PAID]);
        } elseif ($run->status !== CommissionRunStatus::CALCULATED) {
            $run->update(['status' => CommissionRunStatus::CALCULATED]);
        }

        return $payment;
    }

    public function recordAdjustment(
        int $tenantId,
        int $commissionRunId,
        int $adjustment,
        string $reason,
        ?int $recordedBy
    ): CommissionRun {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Adjustment reason is required.');
        }

        $run = CommissionRun::withoutGlobalScopes()
            ->where('id', $commissionRunId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $run->update([
            'total_commission' => max(0, $run->total_commission + $adjustment),
        ]);

        return $run->fresh();
    }

    private function getEffectiveRate(int $professionalId, int $serviceId): float
    {
        $serviceSpecific = ProfessionalServiceCommission::withoutGlobalScopes()
            ->where('professional_id', $professionalId)
            ->where('service_id', $serviceId)
            ->first();

        if ($serviceSpecific) {
            return (float) $serviceSpecific->commission_rate;
        }

        $professional = Professional::withoutGlobalScopes()->find($professionalId);

        return $professional ? (float) $professional->commission_rate : 0.0;
    }
}