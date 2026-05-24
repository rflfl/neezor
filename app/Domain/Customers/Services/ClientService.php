<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Contracts\ClientServiceInterface;
use App\Domain\Customers\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService implements ClientServiceInterface
{
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->fresh();
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    public function getAll(): Collection
    {
        return Client::all();
    }

    public function getInactive(): Collection
    {
        return Client::inactive()->get();
    }
}
