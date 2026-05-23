<?php

namespace Database\Factories\Domain\Services\Models;

use App\Domain\Services\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement([
                'Corte Feminino',
                'Corte Masculino',
                'Barbear',
                'Depilação',
                'Limpeza de Pele',
                'Manicure',
                'Pedicure',
                'Sobrancelha',
                'Maquiagem',
                'Massagem',
            ]),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'price' => $this->faker->randomElement([3000, 5000, 7000, 10000, 15000, 20000]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
