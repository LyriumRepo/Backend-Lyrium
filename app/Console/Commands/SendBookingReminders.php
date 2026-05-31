<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceBooking;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Envía recordatorio WhatsApp 3 horas antes de cada cita confirmada';

    public function handle(WhatsAppService $whatsapp): int
    {
        $target = now()->addHours(3);
        $windowStart = $target->copy()->subMinutes(5);
        $windowEnd = $target->copy()->addMinutes(5);

        $bookings = ServiceBooking::query()
            ->where('status', ServiceBooking::STATUS_CONFIRMED)
            ->whereBetween('appointment_date', [$windowStart, $windowEnd])
            ->with(['service.store', 'user', 'specialist'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No hay citas para recordar en este ciclo.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            $phone = $booking->user?->phone;

            if (empty($phone)) {
                $this->warn("Booking #{$booking->id}: cliente sin teléfono registrado");
                $failed++;

                continue;
            }

            $date = Carbon::parse($booking->appointment_date)
                ->setTimezone('America/Lima')
                ->translatedFormat('l d \d\e F \d\e Y');

            $time = Carbon::parse($booking->appointment_date)
                ->setTimezone('America/Lima')
                ->format('H:i');

            $ok = $whatsapp->sendBookingReminder(
                phone: $phone,
                customerName: $booking->user->name,
                serviceName: $booking->service?->name ?? 'Servicio',
                specialistName: $booking->specialist?->nombre_completo ?? '—',
                storeName: $booking->service?->store?->trade_name
                    ?? $booking->service?->store?->store_name
                    ?? 'Tienda',
                date: $date,
                time: $time,
            );

            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info("Recordatorios enviados: {$sent}, fallidos: {$failed}");

        return self::SUCCESS;
    }
}
