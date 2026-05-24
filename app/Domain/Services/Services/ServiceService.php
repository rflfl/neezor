<?php

namespace App\Domain\Services\Services;

use App\Domain\Services\Contracts\ServiceServiceInterface;
use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceService implements ServiceServiceInterface
{
    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        return $service->delete();
    }

    public function getAll(): Collection
    {
        return Service::all();
    }

    public function getActive(): Collection
    {
        return Service::where('is_active', true)->get();
    }
}
