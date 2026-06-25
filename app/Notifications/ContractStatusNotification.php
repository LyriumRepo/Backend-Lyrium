<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ContractStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Contract $contract,
        private readonly string $action,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        $settings = $notifiable->notificationSetting;
        if ($settings?->wantsPush() ?? true) {
            $channels[] = PushChannel::class;
        }

        return $channels;
    }

    public function toPush(object $notifiable): array
    {
        $label = match ($this->action) {
            'created'   => 'Nuevo contrato creado',
            'activated' => 'Contrato activado',
            'expired'   => 'Contrato expirado',
            'renewed'   => 'Contrato renovado',
            'deleted'   => 'Contrato eliminado',
            default     => 'Contrato actualizado',
        };

        return [
            'title' => $label,
            'body'  => "Contrato #{$this->contract->contract_number} — {$this->contract->company}",
            'data'  => [
                'type'        => 'contract_status_changed',
                'contract_id' => $this->contract->id,
                'action'      => $this->action,
                'url'         => '/admin/contratos/' . $this->contract->id,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->action) {
            'created' => (new MailMessage)
                ->subject('Nuevo contrato creado - Lyrium BioMarketplace')
                ->greeting('Hola, '.$notifiable->name)
                ->line("Se ha creado un nuevo contrato para {$this->contract->company}.")
                ->line("Número: {$this->contract->contract_number}")
                ->action('Ver Contrato', config('app.frontend_url').'/admin/contratos/'.$this->contract->id),

            'activated' => (new MailMessage)
                ->subject('Contrato activado - Lyrium BioMarketplace')
                ->greeting('Hola, '.$notifiable->name)
                ->line("El contrato {$this->contract->contract_number} de {$this->contract->company} ha sido activado.")
                ->action('Ver Contrato', config('app.frontend_url').'/admin/contratos/'.$this->contract->id),

            'expired' => (new MailMessage)
                ->subject('Contrato expirado - Lyrium BioMarketplace')
                ->greeting('Hola, '.$notifiable->name)
                ->line("El contrato {$this->contract->contract_number} de {$this->contract->company} ha expirado.")
                ->line('Por favor, revisa los detalles para gestionar la renovación.'),

            'renewed' => (new MailMessage)
                ->subject('Contrato renovado - Lyrium BioMarketplace')
                ->greeting('Hola, '.$notifiable->name)
                ->line("El contrato {$this->contract->contract_number} de {$this->contract->company} ha sido renovado.")
                ->line("Nueva versión: V{$this->contract->version}")
                ->action('Ver Contrato', config('app.frontend_url').'/admin/contratos/'.$this->contract->id),

            'deleted' => (new MailMessage)
                ->subject('Contrato eliminado - Lyrium BioMarketplace')
                ->greeting('Hola, '.$notifiable->name)
                ->line("El contrato {$this->contract->contract_number} de {$this->contract->company} ha sido eliminado."),

            default => (new MailMessage)
                ->subject('Actualización de contrato - Lyrium BioMarketplace')
                ->line("El contrato {$this->contract->contract_number} ha sido actualizado."),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'contract_name' => $this->contract->company,
            'contract_status' => $this->contract->status,
            'contract_version' => (string) $this->contract->version,
            'contract_action' => $this->action,
            'reason' => $this->reason,
            'type' => 'contract_status_changed',
        ];
    }
}
