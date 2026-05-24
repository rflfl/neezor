<?php

namespace App\Providers;

use App\Domain\Cashbox\Contracts\CashboxServiceInterface;
use App\Domain\Cashbox\Services\CashboxService;
use App\Domain\Commission\Contracts\CommissionServiceInterface;
use App\Domain\Commission\Services\CommissionService;
use App\Domain\Notifications\Contracts\NotificationServiceInterface;
use App\Domain\Notifications\Drivers\MockWhatsAppDriver;
use App\Domain\Packages\Contracts\PackageServiceInterface;
use App\Domain\Packages\Services\PackageService;
use App\Domain\Scheduling\Contracts\AvailabilityServiceInterface;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AvailabilityServiceInterface::class, AvailabilityService::class);
        $this->app->singleton(PackageServiceInterface::class, PackageService::class);
        $this->app->singleton(CashboxServiceInterface::class, CashboxService::class);
        $this->app->singleton(CommissionServiceInterface::class, CommissionService::class);
        $this->app->singleton(NotificationServiceInterface::class, MockWhatsAppDriver::class);

        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService(
                $app->make(AvailabilityServiceInterface::class),
                $app->make(CashboxServiceInterface::class),
                $app->make(PackageServiceInterface::class),
                $app->make(CommissionServiceInterface::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}