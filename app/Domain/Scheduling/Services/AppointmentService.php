<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Cashbox\Contracts\CashboxServiceInterface;
use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Packages\Contracts\PackageServiceInterface;
use App\Domain\Scheduling\Contracts\AvailabilityServiceInterface;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Services\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AppointmentService
{
    public function __construct(
        private readonly AvailabilityServiceInterface $availabilityService,
        private readonly ?CashboxServiceInterface $cashboxService = null,
        private readonly ?PackageServiceInterface $packageService = null
    ) {}

    public function create(array $data): Appointment
    {
        $startAt = $data['start_at'] instanceof Carbon ? $data['start_at'] : Carbon::parse($data['start_at']);
        $endAt = $data['end_at'] instanceof Carbon ? $data['end_at'] : Carbon::parse($data['end_at']);

        if ($this->availabilityService->hasConflict(
            $data['tenant_id'],
            $data['professional_id'],
            $startAt,
            $endAt
        )) {
            throw new InvalidArgumentException('Time slot conflicts with an existing appointment.');
        }

        $service = Service::withoutGlobalScopes()->find($data['service_id']);
        $data['price'] = $data['price'] ?? $service->price;
        $data['end_at'] = $endAt;
        $data['start_at'] = $startAt;
        $data['status'] = $data['status'] ?? Appointment::STATUS_SCHEDULED;

        return Appointment::create($data);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $startAt = isset($data['start_at'])
            ? ($data['start_at'] instanceof Carbon ? $data['start_at'] : Carbon::parse($data['start_at']))
            : $appointment->start_at;

        $endAt = isset($data['end_at'])
            ? ($data['end_at'] instanceof Carbon ? $data['end_at'] : Carbon::parse($data['end_at']))
            : $appointment->end_at;

        if (isset($data['start_at']) || isset($data['end_at']) || isset($data['professional_id'])) {
            $professionalId = $data['professional_id'] ?? $appointment->professional_id;

            if ($this->availabilityService->hasConflict(
                $appointment->tenant_id,
                $professionalId,
                $startAt,
                $endAt,
                $appointment->id
            )) {
                throw new InvalidArgumentException('Time slot conflicts with an existing appointment.');
            }
        }

        $appointment->update([
            'professional_id' => $data['professional_id'] ?? $appointment->professional_id,
            'client_id' => $data['client_id'] ?? $appointment->client_id,
            'service_id' => $data['service_id'] ?? $appointment->service_id,
            'package_id' => $data['package_id'] ?? $appointment->package_id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $data['status'] ?? $appointment->status,
            'price' => $data['price'] ?? $appointment->price,
        ]);

        return $appointment->fresh();
    }

    public function transitionTo(Appointment $appointment, string $status): Appointment
    {
        if (!$appointment->canTransitionTo($status)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$appointment->status}' to '{$status}'."
            );
        }

        $previousStatus = $appointment->status;
        $appointment->update(['status' => $status]);

        if ($status === Appointment::STATUS_COMPLETED) {
            if ($this->packageService !== null && $appointment->client_id && $appointment->service_id) {
                $this->packageService->debitSessionForAppointment($appointment);
            }

            if ($this->cashboxService !== null) {
                $this->createCashEntryFromAppointment($appointment);
            }
        }

        return $appointment->fresh();
    }

    private function createCashEntryFromAppointment(Appointment $appointment): void
    {
        $cashboxDay = $this->cashboxService->getCurrentDay($appointment->tenant_id);

        if ($cashboxDay === null || $cashboxDay->status !== CashboxStatus::OPEN) {
            return;
        }

        $paymentMethod = $appointment->payment_method ?? 'money';

        $this->cashboxService->recordEntry(
            $cashboxDay,
            $appointment->price,
            $paymentMethod,
            $appointment->id,
            "Pagamento do atendimento",
            null
        );
    }

    public function delete(Appointment $appointment): bool
    {
        return $appointment->delete();
    }

    public function getAll(int $tenantId): Collection
    {
        return Appointment::where('tenant_id', $tenantId)
            ->with(['client', 'professional', 'service'])
            ->orderBy('start_at')
            ->get();
    }

    public function getByProfessional(int $tenantId, int $professionalId, ?Carbon $date = null): Collection
    {
        $query = Appointment::where('tenant_id', $tenantId)
            ->where('professional_id', $professionalId)
            ->with(['client', 'service']);

        if ($date !== null) {
            $query->whereDate('start_at', $date->toDateString());
        }

        return $query->orderBy('start_at')->get();
    }

    public function getByDate(int $tenantId, Carbon $date): Collection
    {
        return Appointment::where('tenant_id', $tenantId)
            ->whereDate('start_at', $date->toDateString())
            ->with(['client', 'professional', 'service'])
            ->orderBy('start_at')
            ->get();
    }

    public function debitSessionForAppointment(Appointment $appointment): bool
    {
        if ($this->packageService === null || !$appointment->client_id || !$appointment->service_id) {
            return false;
        }

        return $this->packageService->debitSessionForAppointment($appointment);
    }
}