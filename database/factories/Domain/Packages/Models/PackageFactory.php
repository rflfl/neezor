<?php

namespace Database\Factories\Domain\Packages\Models;

use App\Domain\Packages\Models\Package;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement([
                'Pacote Bronze',
                'Pacote Prata',
                'Pacote Ouro',
                'Pacote Premium',
                'Pacote Mensal',
            ]),
            'price' => $this->faker->randomElement([15000, 20000, 25000, 30000, 40000, 50000]),
            'valid_until_days' => $this->faker->randomElement([30, 60, 90, 180]),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until_days' => -1,
        ]);
    }
}