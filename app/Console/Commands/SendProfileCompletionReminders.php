<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ProfileCompletionReminderMail;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendProfileCompletionReminders extends Command
{
    protected $signature = 'customers:profile-reminders';

    protected $description = 'Envía un recordatorio mensual a clientes con el perfil incompleto para que lo completen';

    /** Columna del perfil => etiqueta legible en el correo. */
    private const MISSING_FIELD_LABELS = [
        'phone' => 'Celular principal',
        'avatar' => 'Foto de perfil',
        'birthday' => 'Fecha de cumpleaños',
        'document_number' => 'Documento de identidad',
        'phone_2' => 'Celular secundario',
        'secondary_email' => 'Correo secundario',
    ];

    public function handle(): int
    {
        $fields = array_keys(self::MISSING_FIELD_LABELS);

        $customers = User::role('customer')
            ->whereNotNull('email_verified_at')
            ->where('is_banned', false)
            ->where(function ($query) use ($fields) {
                foreach ($fields as $field) {
                    $query->orWhereNull($field)->orWhere($field, '');
                }
            })
            ->where(function ($query) {
                $query->whereNull('last_profile_reminder_sent_at')
                    ->orWhere('last_profile_reminder_sent_at', '<', now()->subDays(28));
            })
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No hay clientes elegibles para recordatorio de perfil hoy.');
            return self::SUCCESS;
        }

        $this->info("Revisando {$customers->count()} cliente(s)...");

        $sent = 0;

        foreach ($customers as $customer) {
            $missingLabels = [];
            foreach (self::MISSING_FIELD_LABELS as $field => $label) {
                $value = $customer->{$field};
                if ($value === null || $value === '') {
                    $missingLabels[] = $label;
                }
            }

            // El perfil ya está completo (pudo haberse llenado desde el último corte) — nada que enviar.
            if (empty($missingLabels)) {
                continue;
            }

            try {
                Mail::to($customer->email)->send(
                    new ProfileCompletionReminderMail($customer, $missingLabels)
                );

                $customer->update(['last_profile_reminder_sent_at' => now()]);

                $sent++;
                $this->line("  ✓ {$customer->name} ({$customer->email}) — faltan: " . implode(', ', $missingLabels));
            } catch (\Throwable $e) {
                Log::error('[ProfileReminder] Error al enviar recordatorio', [
                    'user_id' => $customer->id,
                    'email' => $customer->email,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ {$customer->name}: {$e->getMessage()}");
            }
        }

        $this->info("Recordatorios enviados: {$sent} | Omitidos (perfil ya completo): " . ($customers->count() - $sent));

        app(AuditService::class)->record(
            source: AuditService::SOURCE_SCHEDULER,
            event: 'system.scheduler.executed',
            module: 'system',
            description: 'Tarea programada ejecutada: customers:profile-reminders',
            severity: 'info',
            metadata: ['command' => 'customers:profile-reminders'],
        );

        return self::SUCCESS;
    }
}
