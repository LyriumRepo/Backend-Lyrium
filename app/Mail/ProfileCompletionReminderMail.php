<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class ProfileCompletionReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $missingFieldLabels
     */
    public function __construct(
        private readonly User $user,
        private readonly array $missingFieldLabels,
    ) {}

    public function build(): self
    {
        return $this
            ->view('emails.profile-completion-reminder')
            ->subject('Te falta completar tu perfil en Lyrium')
            ->with([
                'name' => $this->user->name,
                'missingFieldLabels' => $this->missingFieldLabels,
                'profileUrl' => config('app.frontend_url', 'http://localhost:3000') . '/customer/profile',
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
