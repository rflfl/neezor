<?php

namespace Database\Factories\Domain\Commission\Models;

use App\Domain\Commission\Enums\CommissionRunStatus;
use App\Domain\Commission\Models\CommissionRun;
use App\Models\Professional;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionRunFactory extends Factory
{
    protected $model = CommissionRun::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'professional_id' => Professional::factory(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'total_gross' => $this->faker->randomNumber(5),
            'total_commission' => $this->faker->randomNumber(4),
            'status' => CommissionRunStatus::DRAFT,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommissionRunStatus::CALCULATED,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommissionRunStatus::PAID,
        ]);
    }
}