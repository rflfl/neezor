<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Contracts\AvailabilityServiceInterface;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Services\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AvailabilityService implements AvailabilityServiceInterface
{
    private const SLOT_INTERVAL_MINUTES = 30;

    public function getAvailableSlots(
        int $tenantId,
        int $professionalId,
        int $serviceId,
        Carbon $date
    ): Collection {
        $service = Service::withoutGlobalScopes()->find($serviceId);
        $durationMinutes = $service->duration_minutes;

        $dayOfWeek = $date->dayOfWeek;
        $startOfDay = (clone $date)->setTime(8, 0);
        $endOfDay = (clone $date)->setTime(18, 0);

        $existingAppointments = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_id', $professionalId)
            ->whereBetween('start_at', [$startOfDay, $endOfDay])
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->orderBy('start_at')
            ->get();

        $slots = collect();
        $current = $startOfDay->copy();

        while ($current->copy()->addMinutes($durationMinutes)->lte($endOfDay)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);

            $hasConflict = $existingAppointments->contains(function ($apt) use ($current, $slotEnd) {
                $aptStart = Carbon::parse($apt->start_at);
                $aptEnd = Carbon::parse($apt->end_at);
                return $current->lt($aptEnd) && $slotEnd->gt($aptStart);
            });

            if (!$hasConflict) {
                $slots->push([
                    'start' => $current->copy(),
                    'end' => $slotEnd->copy(),
                ]);
            }

            $current->addMinutes(self::SLOT_INTERVAL_MINUTES);
        }

        return $slots;
    }

    public function isSlotAvailable(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end
    ): bool {
        return !$this->hasConflict($tenantId, $professionalId, $start, $end);
    }

    public function hasConflict(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $excludeAppointmentId = null
    ): bool {
        return $this->getConflictingAppointment($tenantId, $professionalId, $start, $end, $excludeAppointmentId) !== null;
    }

    public function getConflictingAppointment(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $excludeAppointmentId = null
    ): ?Appointment {
        $query = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_id', $professionalId)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q) use ($start, $end) {
                    $q->where('start_at', '<', $end)
                      ->where('end_at', '>', $start);
                });
            });

        if ($excludeAppointmentId !== null) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return $query->first();
    }

    public function bookSlot(
        int $tenantId,
        int $appointmentId,
        Carbon $start,
        Carbon $end
    ): Appointment {
        $appointment = Appointment::withoutGlobalScopes()
            ->where('id', $appointmentId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$appointment) {
            throw new InvalidArgumentException('Appointment not found.');
        }

        if ($this->hasConflict($tenantId, $appointment->professional_id, $start, $end, $appointmentId)) {
            throw new InvalidArgumentException('Time slot conflicts with an existing appointment.');
        }

        $appointment->forceFill(['start_at' => $start, 'end_at' => $end])->save();

        return $appointment;
    }
}