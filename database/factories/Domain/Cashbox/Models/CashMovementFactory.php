<?php

namespace Database\Factories\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\CashMovementType;
use App\Domain\Cashbox\Enums\PaymentMethod;
use App\Domain\Cashbox\Models\CashMovement;
use App\Domain\Cashbox\Models\CashboxDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'tenant_id' => $this->faker->randomNumber(),
            'cashbox_day_id' => CashboxDay::factory(),
            'type' => CashMovementType::ENTRY,
            'amount' => $this->faker->numberBetween(1000, 50000),
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'appointment_id' => null,
            'expense_category_id' => null,
            'note' => $this->faker->sentence(),
            'created_by' => null,
        ];
    }

    public function entry(int $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CashMovementType::ENTRY,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function expense(int $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CashMovementType::EXPENSE,
            'amount' => $amount ?? $attributes['amount'],
        ]);
    }

    public function money(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::MONEY,
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::CREDIT_CARD,
        ]);
    }
}