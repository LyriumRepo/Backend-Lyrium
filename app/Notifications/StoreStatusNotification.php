<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StoreStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Store $store,
        private readonly string $newStatus,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->newStatus === 'approved') {
            return (new MailMessage)
                ->subject('¡Tu tienda ha sido aprobada! - Lyrium BioMarketplace')
                ->greeting('¡Felicidades, ' . $notifiable->name . '!')
                ->line('Tu tienda "' . $this->store->trade_name . '" ha sido aprobada.')
                ->line('Ya puedes iniciar sesión y comenzar a gestionar tus productos.')
                ->action('Iniciar Sesión', config('app.frontend_url') . '/auth')
                ->line('¡Bienvenido a Lyrium BioMarketplace!');
        }

        if ($this->newStatus === 'rejected') {
            $mail = (new MailMessage)
                ->subject('Actualización sobre tu tienda - Lyrium BioMarketplace')
                ->greeting('Hola, ' . $notifiable->name)
                ->line('Lamentablemente, tu tienda "' . $this->store->trade_name . '" no fue aprobada.');

            if ($this->reason) {
                $mail->line('Motivo: ' . $this->reason);
            }

            return $mail->line('Puedes contactarnos si tienes alguna consulta.');
        }

        if ($this->newStatus === 'banned') {
            $mail = (new MailMessage)
                ->subject('Tu tienda ha sido suspendida - Lyrium BioMarketplace')
                ->greeting('Hola, ' . $notifiable->name)
                ->line('Tu tienda "' . $this->store->trade_name . '" ha sido suspendida.');

            if ($this->reason) {
                $mail->line('Motivo: ' . $this->reason);
            }

            return $mail->line('Si crees que esto es un error, puedes contactarnos.');
        }

        return (new MailMessage)
            ->subject('Actualización de tu tienda - Lyrium BioMarketplace')
            ->line('El estado de tu tienda "' . $this->store->trade_name . '" ha cambiado a: ' . $this->newStatus);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'store_id' => $this->store->id,
            'store_name' => $this->store->trade_name,
            'status' => $this->newStatus,
            'reason' => $this->reason,
            'type' => 'store_status_changed',
        ];
    }
}
