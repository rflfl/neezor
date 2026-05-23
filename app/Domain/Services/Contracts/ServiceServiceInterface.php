<?php

namespace App\Domain\Services\Contracts;

use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Collection;

interface ServiceServiceInterface
{
    public function create(array $data): Service;

    public function update(Service $service, array $data): Service;

    public function delete(Service $service): bool;

    public function getAll(): Collection;

    public function getActive(): Collection;
}
