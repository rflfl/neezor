<?php

namespace App\Domain\Notifications\Contracts;

interface NotificationServiceInterface
{
    public function sendReminder(int $appointmentId): void;
    public function sendConfirmation(int $appointmentId): void;
    public function sendCancellation(int $appointmentId, string $reason): void;
    public function sendPackageAlert(int $clientId, int $packageId): void;
}