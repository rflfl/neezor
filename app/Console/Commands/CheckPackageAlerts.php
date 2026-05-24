<?php

namespace App\Console\Commands;

use App\Domain\Notifications\Jobs\SendPackageAlertJob;
use App\Domain\Packages\Models\PackageSession;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class CheckPackageAlerts extends Command
{
    protected $signature = 'notifications:check-package-alerts';

    protected $description = 'Dispatch package alerts for sessions with 1-2 remaining or expiring within 7 days';

    public function handle(): int
    {
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            TenantContext::setCurrent($tenant->id);

            $sessions = PackageSession::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('sessions_remaining', '>', 0)
                ->where(function ($query) {
                    $query->whereBetween('sessions_remaining', [1, 2])
                        ->orWhere(function ($q) {
                            $q->whereNotNull('expires_at')
                                ->where('expires_at', '<=', Carbon::now()->addDays(7))
                                ->where('expires_at', '>', Carbon::now());
                        });
                })
                ->with(['client', 'package', 'service'])
                ->get();

            foreach ($sessions as $session) {
                if ($session->client) {
                    Queue::push(new SendPackageAlertJob($session->client_id, $session->package_id));
                    $this->info("Dispatched package alert for client {$session->client_id}, package {$session->package_id}");
                }
            }
        }

        TenantContext::clear();

        return Command::SUCCESS;
    }
}