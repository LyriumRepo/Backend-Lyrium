<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewSellerRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly User $seller,
        private readonly Store $store,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', PushChannel::class];
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '🏪 Nuevo vendedor registrado',
            'body'  => "\"{$this->store->trade_name}\" (RUC: {$this->store->ruc}) solicita aprobación.",
            'data'  => [
                'type'     => 'new_seller_registration',
                'store_id' => (string) $this->store->id,
                'url'      => '/admin/stores?status=pending',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🏪 Nuevo vendedor registrado — {$this->store->trade_name}")
            ->greeting('¡Hola, ' . $notifiable->name . '!')
            ->line('Un nuevo vendedor se ha registrado en Lyrium BioMarketplace y está esperando aprobación.')
            ->line("**Tienda:** {$this->store->trade_name}")
            ->line("**RUC:** {$this->store->ruc}")
            ->line("**Correo:** {$this->seller->email}")
            ->line("**Teléfono:** " . ($this->seller->phone ?? '—'))
            ->action('Revisar tienda', config('app.frontend_url') . '/admin/stores?status=pending');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'new_seller_registration',
            'title'      => "🏪 Nuevo vendedor: {$this->store->trade_name}",
            'message'    => "La tienda \"{$this->store->trade_name}\" (RUC: {$this->store->ruc}) está esperando aprobación.",
            'seller_id'  => $this->seller->id,
            'seller_name' => $this->seller->name,
            'seller_email' => $this->seller->email,
            'store_id'   => $this->store->id,
            'store_name' => $this->store->trade_name,
            'ruc'        => $this->store->ruc,
            'action_url' => '/admin/stores?status=pending',
        ];
    }
}
