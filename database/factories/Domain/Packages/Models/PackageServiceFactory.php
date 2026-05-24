<?php

namespace Database\Factories\Domain\Packages\Models;

use App\Domain\Packages\Models\PackageService;
use App\Domain\Packages\Models\Package;
use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageServiceFactory extends Factory
{
    protected $model = PackageService::class;

    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'service_id' => Service::factory(),
            'session_count' => $this->faker->randomElement([1, 2, 3, 5, 10]),
        ];
    }
}