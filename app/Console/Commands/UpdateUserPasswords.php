<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserPasswords extends Command
{
    protected $signature = 'user:update-passwords {--email= : Actualizar solo este email}';

    protected $description = 'Actualizar contraseñas de usuarios admin y seller de prueba';

    public function handle(): int
    {
        $password = 'password';

        $users = [
            'angel.enginner08@gmail.com' => 'Angel Engineer',
            'angel.ipanaque.torre@gmail.com' => 'Angel Ipanque',
        ];

        $targetEmail = $this->option('email');

        foreach ($users as $email => $name) {
            if ($targetEmail && $email !== $targetEmail) {
                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->warn("Usuario no encontrado: {$email}");

                continue;
            }

            $user->password = $password;
            $user->email_verified_at = now();
            $user->save();

            $this->info("✓ Contraseña actualizada para: {$email} ({$name})");
        }

        $this->newLine();
        $this->info('Credenciales de prueba:');
        $this->line('Admin:    angel.enginner08@gmail.com / password');
        $this->line('Vendedor: angel.ipanaque.torre@gmail.com / password');

        return Command::SUCCESS;
    }
}
