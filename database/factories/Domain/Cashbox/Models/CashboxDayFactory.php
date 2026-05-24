<?php

namespace Database\Factories\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\CashboxStatus;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashboxDayFactory extends Factory
{
    protected $model = CashboxDay::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'date' => $this->faker->date(),
            'opening_balance' => 0,
            'closing_balance' => null,
            'status' => CashboxStatus::OPEN,
        ];
    }

    public function closed(int $closingBalance = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CashboxStatus::CLOSED,
            'closing_balance' => $closingBalance ?? $attributes['opening_balance'],
        ]);
    }
}