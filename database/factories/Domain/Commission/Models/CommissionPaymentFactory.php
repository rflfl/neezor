<?php

namespace Database\Factories\Domain\Commission\Models;

use App\Domain\Commission\Models\CommissionPayment;
use App\Domain\Commission\Models\CommissionRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionPaymentFactory extends Factory
{
    protected $model = CommissionPayment::class;

    public function definition(): array
    {
        return [
            'commission_run_id' => CommissionRun::factory(),
            'amount' => $this->faker->randomNumber(4),
            'paid_at' => now(),
            'note' => $this->faker->optional()->sentence(),
            'recorded_by' => null,
        ];
    }
}