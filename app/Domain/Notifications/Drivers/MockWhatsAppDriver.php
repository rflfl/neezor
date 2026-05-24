<?php

namespace App\Domain\Notifications\Drivers;

use App\Domain\Notifications\Contracts\NotificationServiceInterface;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Packages\Models\PackageSession;
use Illuminate\Support\Facades\Log;

class MockWhatsAppDriver implements NotificationServiceInterface
{
    public function sendReminder(int $appointmentId): void
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with(['client', 'professional', 'service'])
            ->find($appointmentId);

        if (!$appointment || !$appointment->client) {
            return;
        }

        Log::info('WhatsApp notification', [
            'type' => 'reminder',
            'appointment_id' => $appointmentId,
            'client_phone' => $appointment->client->phone,
            'client_name' => $appointment->client->name,
            'professional_name' => $appointment->professional ? $appointment->professional->name : null,
            'service_name' => $appointment->service ? $appointment->service->name : null,
            'scheduled_at' => $appointment->start_at ? $appointment->start_at->toIso8601String() : null,
            'message' => $this->buildReminderMessage($appointment),
        ]);
    }

    public function sendConfirmation(int $appointmentId): void
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with(['client', 'professional', 'service'])
            ->find($appointmentId);

        if (!$appointment || !$appointment->client) {
            return;
        }

        Log::info('WhatsApp notification', [
            'type' => 'confirmation',
            'appointment_id' => $appointmentId,
            'client_phone' => $appointment->client->phone,
            'client_name' => $appointment->client->name,
            'professional_name' => $appointment->professional ? $appointment->professional->name : null,
            'service_name' => $appointment->service ? $appointment->service->name : null,
            'scheduled_at' => $appointment->start_at ? $appointment->start_at->toIso8601String() : null,
            'message' => $this->buildConfirmationMessage($appointment),
        ]);
    }

    public function sendCancellation(int $appointmentId, string $reason): void
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with(['client', 'professional', 'service'])
            ->find($appointmentId);

        if (!$appointment || !$appointment->client) {
            return;
        }

        Log::info('WhatsApp notification', [
            'type' => 'cancellation',
            'appointment_id' => $appointmentId,
            'client_phone' => $appointment->client->phone,
            'client_name' => $appointment->client->name,
            'professional_name' => $appointment->professional ? $appointment->professional->name : null,
            'reason' => $reason,
            'message' => $this->buildCancellationMessage($appointment, $reason),
        ]);
    }

    public function sendPackageAlert(int $clientId, int $packageId): void
    {
        $session = PackageSession::withoutGlobalScopes()
            ->with(['client', 'package', 'service'])
            ->where('client_id', $clientId)
            ->where('package_id', $packageId)
            ->first();

        if (!$session || !$session->client) {
            return;
        }

        Log::info('WhatsApp notification', [
            'type' => 'package_alert',
            'client_id' => $clientId,
            'package_id' => $packageId,
            'client_phone' => $session->client->phone,
            'client_name' => $session->client->name,
            'package_name' => $session->package ? $session->package->name : null,
            'service_name' => $session->service ? $session->service->name : null,
            'sessions_remaining' => $session->sessions_remaining,
            'expires_at' => $session->expires_at ? $session->expires_at->toIso8601String() : null,
            'message' => $this->buildPackageAlertMessage($session),
        ]);
    }

    private function buildReminderMessage(Appointment $appointment): string
    {
        $serviceName = $appointment->service ? $appointment->service->name : 'serviço';
        $professionalName = $appointment->professional ? $appointment->professional->name : 'profissional';
        $date = $appointment->start_at ? $appointment->start_at->format('d/m/Y \à\s H:i') : '';

        return "Olá {$appointment->client->name}! Lembramos do seu agendamento para {$serviceName} com {$professionalName} no dia {$date}.";
    }

    private function buildConfirmationMessage(Appointment $appointment): string
    {
        $serviceName = $appointment->service ? $appointment->service->name : 'serviço';
        $professionalName = $appointment->professional ? $appointment->professional->name : 'profissional';
        $date = $appointment->start_at ? $appointment->start_at->format('d/m/Y \à\s H:i') : '';

        return "Olá {$appointment->client->name}! Confirme seu agendamento para {$serviceName} no dia {$date} com {$professionalName}.";
    }

    private function buildCancellationMessage(Appointment $appointment, string $reason): string
    {
        return "Olá {$appointment->client->name}! Seu agendamento foi cancelado. Motivo: {$reason}. Em caso de dúvidas, entre em contato com o salão.";
    }

    private function buildPackageAlertMessage(PackageSession $session): string
    {
        $service = $session->service ? $session->service->name : 'serviço';
        $package = $session->package ? $session->package->name : 'pacote';
        $remaining = $session->sessions_remaining;
        $expires = $session->expires_at ? $session->expires_at->format('d/m/Y') : null;

        if ($remaining <= 2) {
            return "Olá {$session->client->name}! Seu pacote '{$package}' tem apenas {$remaining} sessão(ões) restante(s) de {$service}. Agende seu próximo atendimento!";
        }

        if ($expires && $session->expires_at && $session->expires_at->diffInDays(now()) < 7) {
            return "Olá {$session->client->name}! Seu pacote '{$package}' expira em {$expires}. Agende seus atendimentos restantes!";
        }

        return "Olá {$session->client->name}! Você ainda tem {$remaining} sessão(ões) de {$service} no pacote '{$package}'.";
    }
}