<?php

namespace App\Domain\Customers\Contracts;

use App\Domain\Customers\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface ClientServiceInterface
{
    public function create(array $data): Client;

    public function update(Client $client, array $data): Client;

    public function delete(Client $client): bool;

    public function getAll(): Collection;

    public function getInactive(): Collection;
}
