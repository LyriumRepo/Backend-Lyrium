<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class CustomerPanelReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly int $reminderNumber,
    ) {}

    public function build(): self
    {
        return $this
            ->view('emails.customer-panel-reminder')
            ->subject('Tu panel de cliente en Lyrium te espera')
            ->with([
                'name' => $this->user->name,
                'reminderNumber' => $this->reminderNumber,
                'panelUrl' => config('app.frontend_url', 'http://localhost:3000') . '/customer',
            ])
            ->withSymfonyMessage(function ($message) {
                $iconPath = public_path('images/iconologo.png');
                $textPath = public_path('images/nombrelogo.png');
                if (file_exists($iconPath)) {
                    $message->embedFromPath($iconPath, 'logo-icon');
                }
                if (file_exists($textPath)) {
                    $message->embedFromPath($textPath, 'logo-text');
                }
            });
    }
}
