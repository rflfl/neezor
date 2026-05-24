<?php

namespace Database\Factories\Domain\Scheduling\Models;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Services\Models\Service;
use App\Models\Professional;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startAt = Carbon::tomorrow()->setTimeFromTimeString($this->faker->time('H:i'));
        $duration = $this->faker->randomElement([30, 45, 60, 90, 120]);

        return [
            'tenant_id' => Tenant::factory(),
            'professional_id' => Professional::factory(),
            'client_id' => Client::factory(),
            'service_id' => Service::factory(),
            'package_id' => null,
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->addMinutes($duration),
            'status' => Appointment::STATUS_SCHEDULED,
            'price' => $this->faker->randomElement([3000, 5000, 7000, 10000, 15000]),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_IN_PROGRESS,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_COMPLETED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_CANCELLED,
        ]);
    }
}