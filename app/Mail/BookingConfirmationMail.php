<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ServiceBooking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * BookingConfirmationMail
 *
 * Se envía SIEMPRE que se crea una reserva, en 3 variantes:
 *
 *   role = 'client'     → "Tu cita ha sido reservada"
 *   role = 'specialist' → "Nueva cita asignada"
 *   role = 'seller'     → "Nueva reserva en tu tienda"
 *
 * Si $gcalOk = false (Google Calendar falló), adjunta el archivo .ics
 * para que el destinatario pueda importar la cita a su calendario.
 *
 * Si $gcalOk = true, Google ya envió la invitación vía attendees,
 * pero igual se envía este email como confirmación con los detalles.
 *
 * Se encola (ShouldQueue) para no bloquear el request HTTP del cliente.
 */
final class BookingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ServiceBooking $booking,
        public readonly string $recipientName,
        public readonly string $role,           // 'client' | 'specialist' | 'seller'
        public readonly ?string $icsContent,    // null si gcalOk = true
        public readonly bool $gcalOk,           // ¿Google Calendar funcionó?
    ) {}

    public function envelope(): Envelope
    {
        $serviceName = $this->booking->service?->name ?? 'Consulta';
        $date        = Carbon::parse($this->booking->appointment_date)
            ->setTimezone('America/Lima')
            ->translatedFormat('l d \d\e F \d\e Y \a \l\a\s H:i');

        $subject = match ($this->role) {
            'client'     => "✅ Tu cita está confirmada — {$serviceName}",
            'specialist' => "📅 Nueva cita asignada — {$serviceName} el {$date}",
            'seller'     => "🔔 Nueva reserva en tu tienda — {$serviceName}",
            default      => "Confirmación de cita — {$serviceName}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'booking'       => $this->booking,
                'recipientName' => $this->recipientName,
                'role'          => $this->role,
                'gcalOk'        => $this->gcalOk,
                'hasIcs'        => $this->icsContent !== null,
                // Datos formateados para la vista
                'serviceName'   => $this->booking->service?->name ?? 'Consulta',
                'specialistName' => $this->booking->specialist?->nombre_completo ?? '—',
                'clientName'    => $this->booking->user?->name ?? '—',
                'storeName'     => $this->booking->service?->store?->trade_name
                    ?? $this->booking->service?->store?->store_name
                    ?? '—',
                'appointmentDate' => Carbon::parse($this->booking->appointment_date)
                    ->setTimezone('America/Lima')
                    ->translatedFormat('l d \d\e F \d\e Y'),
                'appointmentTime' => Carbon::parse($this->booking->appointment_date)
                    ->setTimezone('America/Lima')
                    ->format('H:i'),
                'duration'      => $this->booking->service?->duration_minutes ?? 30,
                'price'         => number_format((float) $this->booking->total_price, 2),
                'status'        => $this->booking->status,
                'customerNotes' => $this->booking->customer_notes,
            ]
        );
    }

    /**
     * Adjunta el .ics solo cuando Google Calendar falló.
     * El archivo puede abrirse directamente para agregar la cita al calendario.
     */
    public function attachments(): array
    {
        if ($this->icsContent === null) {
            return [];
        }

        return [
            Attachment::fromData(
                fn() => $this->icsContent,
                'cita-lyrium.ics'
            )->withMime('text/calendar'),
        ];
    }
}
