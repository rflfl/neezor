<?php

namespace App\Domain\Scheduling\Contracts;

use App\Domain\Scheduling\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface AvailabilityServiceInterface
{
    public function getAvailableSlots(
        int $tenantId,
        int $professionalId,
        int $serviceId,
        Carbon $date
    ): Collection;

    public function isSlotAvailable(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end
    ): bool;

    public function hasConflict(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $excludeAppointmentId = null
    ): bool;

    public function getConflictingAppointment(
        int $tenantId,
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $excludeAppointmentId = null
    ): ?Appointment;

    public function bookSlot(
        int $tenantId,
        int $appointmentId,
        Carbon $start,
        Carbon $end
    ): Appointment;
}