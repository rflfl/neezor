<?php

namespace App\Domain\Packages\Contracts;

use App\Domain\Packages\Models\Package;
use App\Domain\Packages\Models\PackageSession;
use App\Domain\Scheduling\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

interface PackageServiceInterface
{
    public function createPackage(array $data): Package;

    public function updatePackage(Package $package, array $data): Package;

    public function deletePackage(Package $package): bool;

    public function addService(Package $package, int $serviceId, int $sessionCount): void;

    public function removeService(Package $package, int $serviceId): void;

    public function purchase(int $tenantId, int $clientId, Package $package): Collection;

    public function findUsableSession(int $tenantId, int $clientId, int $serviceId): ?PackageSession;

    public function debitSessionForAppointment(Appointment $appointment): bool;

    public function calculateSessionsRemaining(int $clientId, int $packageId, int $serviceId): int;

    public function getClientActiveSessions(int $tenantId, int $clientId): Collection;

    public function getPackageSessions(int $packageId): Collection;
}