<?php

namespace Database\Factories\Domain\Cashbox\Models;

use App\Domain\Cashbox\Enums\ExpenseCategoryType;
use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement([ExpenseCategoryType::FIXED, ExpenseCategoryType::VARIABLE]),
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExpenseCategoryType::FIXED,
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExpenseCategoryType::VARIABLE,
        ]);
    }
}