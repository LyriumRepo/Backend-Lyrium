<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RpaDiagnosticoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SellerApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = $this->application;
        $diagnostico = is_array($app->diagnostico) ? $app->diagnostico : [];

        $mail = (new MailMessage)
            ->subject('Diagnóstico de tu solicitud — Lyrium BioMarketplace')
            ->greeting('Hola, '.($app->nombre_comercial ?? $notifiable->name).'!')
            ->line('Aquí tienes el resultado completo de tu solicitud de registro como vendedor:')
            ->line('')
            ->line("**Estado:** {$app->estado}")
            ->line("**Puntaje:** {$app->score}/100")
            ->line("**Riesgo:** {$app->riesgo}");

        if (! empty($diagnostico)) {
            $mail->line('**Diagnóstico detallado:**');
            foreach ($diagnostico as $item) {
                $mail->line("- {$item}");
            }
        }

        return $mail
            ->line('')
            ->line('Gracias por confiar en Lyrium BioMarketplace.');
    }
}