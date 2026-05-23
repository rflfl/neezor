<?php

namespace App\Domain\Packages\Services;

use App\Domain\Packages\Contracts\PackageServiceInterface;
use App\Domain\Packages\Models\Package;
use App\Domain\Packages\Models\PackageSession;
use App\Domain\Scheduling\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PackageService implements PackageServiceInterface
{
    public function createPackage(array $data): Package
    {
        return Package::create($data);
    }

    public function updatePackage(Package $package, array $data): Package
    {
        $package->update($data);
        return $package->fresh();
    }

    public function deletePackage(Package $package): bool
    {
        return $package->delete();
    }

    public function addService(Package $package, int $serviceId, int $sessionCount): void
    {
        $package->services()->attach($serviceId, ['session_count' => $sessionCount]);
    }

    public function removeService(Package $package, int $serviceId): void
    {
        $package->services()->detach($serviceId);
    }

    public function purchase(int $tenantId, int $clientId, Package $package): Collection
    {
        return DB::transaction(function () use ($tenantId, $clientId, $package) {
            $sessions = new Collection();
            $package->load('services');

            foreach ($package->services as $service) {
                $expiresAt = Carbon::now()->addDays($package->valid_until_days);

                $session = PackageSession::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId,
                    'client_id' => $clientId,
                    'package_id' => $package->id,
                    'service_id' => $service->id,
                    'sessions_remaining' => $service->pivot->session_count,
                    'expires_at' => $expiresAt,
                ]);

                $sessions->push($session);
            }

            return $sessions;
        });
    }

    public function findUsableSession(int $tenantId, int $clientId, int $serviceId): ?PackageSession
    {
        return PackageSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('client_id', $clientId)
            ->where('service_id', $serviceId)
            ->where('sessions_remaining', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderBy('expires_at', 'asc')
            ->first();
    }

    public function debitSessionForAppointment(Appointment $appointment): bool
    {
        if (!$appointment->client_id || !$appointment->service_id) {
            return false;
        }

        $session = $this->findUsableSession(
            $appointment->tenant_id,
            $appointment->client_id,
            $appointment->service_id
        );

        if (!$session) {
            return false;
        }

        return DB::transaction(function () use ($session, $appointment) {
            $session->decrement('sessions_remaining');
            $session->update([
                'used_at' => Carbon::now(),
                'appointment_id' => $appointment->id,
            ]);
            return true;
        });
    }

    public function calculateSessionsRemaining(int $clientId, int $packageId, int $serviceId): int
    {
        return PackageSession::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->where('package_id', $packageId)
            ->where('service_id', $serviceId)
            ->where('sessions_remaining', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->sum('sessions_remaining');
    }

    public function getClientActiveSessions(int $tenantId, int $clientId): Collection
    {
        return PackageSession::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('client_id', $clientId)
            ->where('sessions_remaining', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->with(['package', 'service'])
            ->get();
    }

    public function getPackageSessions(int $packageId): Collection
    {
        return PackageSession::withoutGlobalScopes()
            ->where('package_id', $packageId)
            ->with(['client', 'service'])
            ->get();
    }
}