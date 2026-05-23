<?php

namespace App\Providers;

use App\Domain\Packages\Contracts\PackageServiceInterface;
use App\Domain\Packages\Services\PackageService;
use App\Domain\Scheduling\Contracts\AvailabilityServiceInterface;
use App\Domain\Scheduling\Services\AvailabilityService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AvailabilityServiceInterface::class, AvailabilityService::class);
        $this->app->singleton(PackageServiceInterface::class, PackageService::class);
    }

    public function boot(): void
    {
        //
    }
}
