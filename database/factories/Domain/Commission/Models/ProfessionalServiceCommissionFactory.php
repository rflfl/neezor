<?php

namespace Database\Factories\Domain\Commission\Models;

use App\Domain\Commission\Models\ProfessionalServiceCommission;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfessionalServiceCommissionFactory extends Factory
{
    protected $model = ProfessionalServiceCommission::class;

    public function definition(): array
    {
        return [
            'professional_id' => Professional::factory(),
            'service_id' => Service::factory(),
            'commission_rate' => $this->faker->randomFloat(4, 0.20, 0.60),
        ];
    }
}