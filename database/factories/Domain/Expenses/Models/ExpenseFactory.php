<?php

namespace Database\Factories\Domain\Expenses\Models;

use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Expenses\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'tenant_id' => $this->faker->randomNumber(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'expense_category_id' => ExpenseCategory::factory(),
            'is_recurring' => $this->faker->boolean(30),
            'description' => $this->faker->sentence(3),
            'due_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => false,
        ]);
    }
}