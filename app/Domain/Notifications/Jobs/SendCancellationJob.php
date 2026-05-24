<?php

namespace App\Domain\Notifications\Jobs;

use App\Domain\Notifications\Contracts\NotificationServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCancellationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $reason
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationServiceInterface $notificationService): void
    {
        $notificationService->sendCancellation($this->appointmentId, $this->reason);
    }
}