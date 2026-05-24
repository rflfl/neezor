<?php

namespace Database\Factories\Domain\Packages\Models;

use App\Domain\Packages\Models\PackageSession;
use App\Domain\Packages\Models\Package;
use App\Domain\Customers\Models\Client;
use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageSessionFactory extends Factory
{
    protected $model = PackageSession::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'package_id' => Package::factory(),
            'service_id' => Service::factory(),
            'sessions_remaining' => $this->faker->randomElement([1, 2, 3, 5, 10]),
            'used_at' => null,
            'expires_at' => Carbon::now()->addDays(90),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'sessions_remaining' => 0,
            'used_at' => Carbon::now()->subHours(2),
        ]);
    }

    public function withSessions(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'sessions_remaining' => $count,
        ]);
    }
}