<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\BookingConfirmationMail;
use App\Models\ServiceBooking;
use App\Models\Specialist;
use App\Models\Store;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * GoogleCalendarService — Refactorizado para triple sincronización
 *
 * ESTRATEGIA DE SINCRONIZACIÓN:
 * ─────────────────────────────
 * En lugar de crear 3 eventos separados (uno por calendario), creamos
 * UN SOLO evento con `attendees` (invitados). Google Calendar envía
 * automáticamente una invitación por email a cada asistente y coloca
 * el evento en su propio calendario cuando aceptan.
 *
 * Beneficios:
 *   ✓ Un solo google_event_id principal (el del organizador / especialista).
 *   ✓ Google gestiona las aceptaciones y sincronización entre calendarios.
 *   ✓ Cada asistente recibe email de invitación sin que el backend
 *     necesite OAuth de cada uno.
 *   ✓ Si el cliente o vendedor no tiene GCal, igualmente reciben el email.
 *
 * PLAN DE RESPALDO (si Google Calendar falla):
 *   → Se envía un Mailable con todos los detalles + archivo .ics adjunto.
 *   → El .ics es compatible con Google Calendar, Apple Calendar, Outlook.
 *   → El cliente, especialista y vendedor reciben el email de respaldo.
 *
 * FLUJO COMPLETO de createEvent():
 *   1. Intentar crear evento en Google Calendar con attendees.
 *   2. Si éxito  → guardar event_id, enviar BookingConfirmationMail (siempre).
 *   3. Si fallo  → enviar BookingConfirmationMail con .ics adjunto (fallback).
 *   4. Retornar array con los IDs (specialist, client, seller).
 */
final class GoogleCalendarService
{
    private const TIMEZONE = 'America/Lima';

    // ── 1. CREAR EVENTO ───────────────────────────────────────────────────────

    /**
     * Crea el evento de Google Calendar con invitados (attendees).
     *
     * @return array{specialist: string|null, client: string|null, seller: string|null}
     *                                                                                  Los IDs de Google Event para cada calendario.
     *                                                                                  Todos son el mismo ID porque es un evento compartido vía attendees.
     *                                                                                  Se guardan separados por si en el futuro se necesita eliminar
     *                                                                                  individualmente o manejar distintos IDs por calendario.
     */
    public function createEvent(ServiceBooking $booking): array
    {
        $emptyIds = ['specialist' => null, 'client' => null, 'seller' => null];

        $store = $booking->service?->store;
        $specialist = $booking->specialist;
        $client = $booking->user;

        // ── Recopilar emails de los 3 destinatarios ───────────────────────
        $specialistEmail = $specialist?->google_calendar_id ?? $specialist?->email;
        $clientEmail = $client?->email;
        $sellerEmail = $store?->google_calendar_id ?? $store?->corporate_email;

        // ── Intentar crear evento en Google Calendar ──────────────────────
        $calendar = $this->getCalendarClient($store);
        $eventId = null;

        if ($calendar) {
            $eventId = $this->insertEventWithAttendees(
                calendar: $calendar,
                booking: $booking,
                organizerEmail: $specialistEmail ?? $sellerEmail ?? 'primary',
                attendeeEmails: array_filter([$specialistEmail, $clientEmail, $sellerEmail]),
            );
        }

        // ── Siempre enviar email de confirmación ──────────────────────────
        // Si Google Calendar falló, el email incluye el .ics como respaldo.
        $gcalSucceeded = $eventId !== null;
        $this->sendBookingEmails($booking, $gcalSucceeded);

        if (! $gcalSucceeded) {
            Log::warning('GoogleCalendar: Evento no creado — se envió email de respaldo con .ics', [
                'booking_id' => $booking->id,
            ]);

            return $emptyIds;
        }

        Log::info('GoogleCalendar: Evento creado con attendees', [
            'booking_id' => $booking->id,
            'event_id' => $eventId,
            'attendees' => array_filter([$specialistEmail, $clientEmail, $sellerEmail]),
        ]);

        // El mismo event_id aplica para los 3 porque es un evento compartido.
        return [
            'specialist' => $eventId,
            'client' => $eventId,
            'seller' => $eventId,
        ];
    }

    // ── 2. ACTUALIZAR EVENTO (reagendamiento) ─────────────────────────────────

    /**
     * Actualiza fecha/hora del evento al reagendar.
     * Actualiza el evento del especialista (organizador); Google propaga a attendees.
     */
    public function updateEvent(ServiceBooking $booking): bool
    {
        if (! $booking->google_event_id) {
            return false;
        }

        $store = $booking->service?->store;
        $calendar = $this->getCalendarClient($store);

        if (! $calendar) {
            return false;
        }

        $calendarId = $booking->specialist?->google_calendar_id
            ?? $store?->google_calendar_id
            ?? 'primary';

        try {
            $event = $calendar->events->get($calendarId, $booking->google_event_id);
            $duration = (int) ($booking->service?->duration_minutes ?? 30);
            $start = Carbon::parse($booking->appointment_date, self::TIMEZONE);
            $end = $start->copy()->addMinutes($duration);

            $event->setStart(new EventDateTime([
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => self::TIMEZONE,
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => self::TIMEZONE,
            ]));
            $event->setSummary('Cita reagendada: '.($booking->service?->name ?? 'Consulta'));

            $calendar->events->update($calendarId, $booking->google_event_id, $event);

            Log::info('GoogleCalendar: Evento reagendado', [
                'booking_id' => $booking->id,
                'event_id' => $booking->google_event_id,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar: Fallo al actualizar evento', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ── 3. ELIMINAR EVENTOS (cancelación) ─────────────────────────────────────

    /**
     * Elimina el evento del organizador (especialista).
     * Google notifica automáticamente a los attendees que el evento fue cancelado.
     *
     * También intenta eliminar google_event_id_client y google_event_id_seller
     * si difieren del principal (por si en el futuro se crean eventos separados).
     */
    public function deleteEvent(ServiceBooking $booking): bool
    {
        if (! $booking->google_event_id) {
            return false;
        }

        $store = $booking->service?->store;
        $calendar = $this->getCalendarClient($store);

        if (! $calendar) {
            return false;
        }

        $calendarId = $booking->specialist?->google_calendar_id
            ?? $store?->google_calendar_id
            ?? 'primary';

        $deleted = false;

        // Eliminar evento principal (del organizador/especialista)
        try {
            $calendar->events->delete($calendarId, $booking->google_event_id);
            $deleted = true;
            Log::info('GoogleCalendar: Evento principal eliminado', [
                'booking_id' => $booking->id,
                'event_id' => $booking->google_event_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar: Fallo al eliminar evento principal', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Intentar eliminar evento del cliente si tiene ID distinto
        if (
            $booking->google_event_id_client &&
            $booking->google_event_id_client !== $booking->google_event_id
        ) {
            $this->tryDeleteEventById(
                calendar: $calendar,
                calendarId: $booking->user?->email ?? 'primary',
                eventId: $booking->google_event_id_client,
                bookingId: $booking->id,
                label: 'cliente',
            );
        }

        // Intentar eliminar evento del vendedor si tiene ID distinto
        if (
            $booking->google_event_id_seller &&
            $booking->google_event_id_seller !== $booking->google_event_id
        ) {
            $this->tryDeleteEventById(
                calendar: $calendar,
                calendarId: $store?->google_calendar_id ?? 'primary',
                eventId: $booking->google_event_id_seller,
                bookingId: $booking->id,
                label: 'vendedor',
            );
        }

        return $deleted;
    }

    // ── 4. FREEBUSY (disponibilidad ciega) ───────────────────────────────────

    /**
     * Retorna los bloques ocupados de un especialista (solo rangos, sin detalles).
     * Garantiza privacidad total: no expone títulos ni participantes de eventos.
     *
     * @return array<array{start: string, end: string}>
     */
    public function getBusySlots(Specialist $specialist, Carbon $startDate, Carbon $endDate): array
    {
        $store = $specialist->store;
        $calendar = $this->getCalendarClient($store);

        if (! $calendar) {
            Log::warning('GoogleCalendar FreeBusy: No se pudo instanciar el cliente', [
                'specialist_id' => $specialist->id,
            ]);

            return [];
        }

        $calendarId = $specialist->google_calendar_id
            ?? $specialist->email
            ?? $store?->google_calendar_id
            ?? 'primary';

        try {
            $request = new FreeBusyRequest;
            $request->setTimeMin($startDate->toRfc3339String());
            $request->setTimeMax($endDate->toRfc3339String());
            $request->setTimeZone(self::TIMEZONE);

            $item = new FreeBusyRequestItem;
            $item->setId($calendarId);
            $request->setItems([$item]);

            $result = $calendar->freebusy->query($request);
            $busyEvents = $result->getCalendars()[$calendarId]?->getBusy() ?? [];

            $formatted = [];
            foreach ($busyEvents as $busy) {
                $formatted[] = [
                    'start' => Carbon::parse($busy->getStart(), self::TIMEZONE)->format('H:i'),
                    'end' => Carbon::parse($busy->getEnd(), self::TIMEZONE)->format('H:i'),
                ];
            }

            Log::info('GoogleCalendar FreeBusy: Consulta OK', [
                'specialist_id' => $specialist->id,
                'busy_count' => count($formatted),
            ]);

            return $formatted;
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar FreeBusy: Error en API', [
                'specialist_id' => $specialist->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    // ── 5. EMAIL DE CONFIRMACIÓN + RESPALDO ICS ───────────────────────────────

    /**
     * Envía emails de confirmación a cliente, especialista y vendedor.
     *
     * @param  bool  $gcalSucceeded  Si es false, adjunta el .ics como respaldo.
     */
    public function sendBookingEmails(ServiceBooking $booking, bool $gcalSucceeded = true): void
    {
        $client = $booking->user;
        $specialist = $booking->specialist;
        $store = $booking->service?->store;

        $icsContent = $gcalSucceeded ? null : $this->generateIcsContent($booking);

        // Email al CLIENTE
        if ($client?->email) {
            $this->trySendMail(
                to: $client->email,
                name: $client->name,
                booking: $booking,
                role: 'client',
                icsContent: $icsContent,
                gcalOk: $gcalSucceeded,
            );
        }

        // Email al ESPECIALISTA
        if ($specialist?->email) {
            $this->trySendMail(
                to: $specialist->email,
                name: $specialist->nombre_completo,
                booking: $booking,
                role: 'specialist',
                icsContent: $icsContent,
                gcalOk: $gcalSucceeded,
            );
        }

        // Email al VENDEDOR (usa el corporate_email de la tienda)
        $sellerEmail = $store?->corporate_email ?? $store?->google_calendar_id;
        if ($sellerEmail) {
            $this->trySendMail(
                to: $sellerEmail,
                name: $store->trade_name ?? $store->store_name ?? 'Vendedor',
                booking: $booking,
                role: 'seller',
                icsContent: $icsContent,
                gcalOk: $gcalSucceeded,
            );
        }
    }

    // ── 6. GENERADOR DE ARCHIVO ICS ───────────────────────────────────────────

    /**
     * Genera un archivo iCalendar (.ics) estándar RFC 5545.
     * Compatible con Google Calendar, Apple Calendar y Outlook.
     */
    public function generateIcsContent(ServiceBooking $booking): string
    {
        $duration = (int) ($booking->service?->duration_minutes ?? 30);
        $start = Carbon::parse($booking->appointment_date)->setTimezone(self::TIMEZONE);
        $end = $start->copy()->addMinutes($duration);

        // Formato UTC para iCalendar
        $dtStart = $start->utc()->format('Ymd\THis\Z');
        $dtEnd = $end->utc()->format('Ymd\THis\Z');
        $dtStamp = now()->utc()->format('Ymd\THis\Z');
        $uid = 'booking-'.$booking->id.'-'.uniqid().'@lyrium.pe';

        $serviceName = addslashes($booking->service?->name ?? 'Consulta');
        $clientName = addslashes($booking->user?->name ?? 'Cliente');
        $specialistName = addslashes(
            ($booking->specialist?->nombre_completo) ?? 'Especialista'
        );
        $storeName = addslashes(
            $booking->service?->store?->trade_name
                ?? $booking->service?->store?->store_name
                ?? 'Tienda'
        );
        $notes = addslashes($booking->customer_notes ?? '');

        $description = "Servicio: {$serviceName}\\n"
            ."Especialista: {$specialistName}\\n"
            ."Cliente: {$clientName}\\n"
            ."Tienda: {$storeName}\\n"
            .($notes ? "Notas: {$notes}\\n" : '');

        // Construir lista de attendees para el ICS
        $attendees = '';
        if ($booking->user?->email) {
            $attendees .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;CN={$clientName}:mailto:{$booking->user->email}\r\n";
        }
        if ($booking->specialist?->email) {
            $attendees .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;CN={$specialistName}:mailto:{$booking->specialist->email}\r\n";
        }
        $sellerEmail = $booking->service?->store?->corporate_email;
        if ($sellerEmail) {
            $attendees .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=OPT-PARTICIPANT;CN={$storeName}:mailto:{$sellerEmail}\r\n";
        }

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//Lyrium Platform//ES\r\n"
            ."CALSCALE:GREGORIAN\r\n"
            ."METHOD:REQUEST\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            ."DTSTAMP:{$dtStamp}\r\n"
            ."DTSTART:{$dtStart}\r\n"
            ."DTEND:{$dtEnd}\r\n"
            ."SUMMARY:{$serviceName} — {$specialistName}\r\n"
            ."DESCRIPTION:{$description}\r\n"
            .$attendees
            ."STATUS:CONFIRMED\r\n"
            ."SEQUENCE:0\r\n"
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }

    // ── 7. INFRAESTRUCTURA DE CLIENTE GOOGLE ──────────────────────────────────

    private function getCalendarClient(?Store $store = null): ?Calendar
    {
        // Intentar primero con OAuth de la tienda
        if ($store && ! empty($store->google_calendar_token)) {
            $calendar = $this->buildOAuthClient($store);
            if ($calendar) {
                return $calendar;
            }
        }

        // Fallback: Service Account
        return $this->buildServiceAccountClient();
    }

    private function buildOAuthClient(Store $store): ?Calendar
    {
        try {
            $tokenData = is_array($store->google_calendar_token)
                ? $store->google_calendar_token
                : json_decode($store->google_calendar_token, true);

            $client = new GoogleClient;
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($tokenData);

            if ($client->isAccessTokenExpired() && ! empty($tokenData['refresh_token'])) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
                if (isset($newToken['error'])) {
                    return null;
                }
                $store->update(['google_calendar_token' => json_encode($newToken)]);
                $client->setAccessToken($newToken);
            }

            return new Calendar($client);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildServiceAccountClient(): ?Calendar
    {
        $path = storage_path('app/google-service-account.json');

        if (! file_exists($path)) {
            Log::error('GoogleCalendar: google-service-account.json no encontrado');

            return null;
        }

        try {
            $client = new GoogleClient;
            $client->setAuthConfig($path);
            $client->addScope(Calendar::CALENDAR);

            return new Calendar($client);
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar: Error inicializando Service Account', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ── 8. HELPERS PRIVADOS ───────────────────────────────────────────────────

    /**
     * Crea el evento en Google Calendar con la lista de attendees.
     * Usa el calendarId del organizador (especialista o tienda).
     *
     * sendUpdates: 'all' → Google envía invitación por email a cada attendee
     * automáticamente, sin que el backend deba gestionar ningún OAuth de cliente.
     */
    private function insertEventWithAttendees(
        Calendar $calendar,
        ServiceBooking $booking,
        string $organizerEmail,
        array $attendeeEmails,
    ): ?string {
        try {
            $duration = (int) ($booking->service?->duration_minutes ?? 30);
            $start = Carbon::parse($booking->appointment_date, self::TIMEZONE);
            $end = $start->copy()->addMinutes($duration);

            $attendees = array_map(
                fn (string $email) => new EventAttendee(['email' => $email]),
                array_unique(array_values($attendeeEmails))
            );

            $event = new Event([
                'summary' => ($booking->service?->name ?? 'Consulta').' — '.
                    ($booking->specialist?->nombre_completo ?? 'Especialista'),
                'description' => $this->buildEventDescription($booking),
                'location' => $this->buildEventLocation($booking),
                'start' => new EventDateTime([
                    'dateTime' => $start->toRfc3339String(),
                    'timeZone' => self::TIMEZONE,
                ]),
                'end' => new EventDateTime([
                    'dateTime' => $end->toRfc3339String(),
                    'timeZone' => self::TIMEZONE,
                ]),
                'attendees' => $attendees,
                // Recordatorio 24h antes por email y 30 min antes por popup
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email',  'minutes' => 1440], // 24 horas
                        ['method' => 'popup',  'minutes' => 30],
                    ],
                ],
            ]);

            // sendUpdates=all → Google envía el email de invitación a los attendees
            $created = $calendar->events->insert(
                $organizerEmail,
                $event,
                ['sendUpdates' => 'all']
            );

            return $created->getId();
        } catch (\Throwable $e) {
            Log::error('GoogleCalendar: insertEventWithAttendees falló', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildEventDescription(ServiceBooking $booking): string
    {
        $isHome = $booking->service?->is_home_service ?? false;
        $lines = [
            'Servicio: '.($booking->service?->name ?? '—'),
            'Especialista: '.($booking->specialist?->nombre_completo ?? '—'),
            'Cliente: '.($booking->user?->name ?? '—'),
            'Estado: '.ucfirst($booking->status),
        ];

        if ($isHome && $booking->service_address) {
            $lines[] = 'Dirección del servicio: '.$booking->service_address;
        } elseif (! $isHome) {
            $store = $booking->service?->store;
            $branch = $store?->branches()?->where('is_principal', true)->first();
            $lines[] = 'Ubicación: '.($branch?->address ?? $store?->address ?? 'En tienda');
        }

        if ($booking->customer_notes) {
            $lines[] = 'Notas del cliente: '.$booking->customer_notes;
        }

        return implode("\n", $lines);
    }

    /**
     * Build the event location string based on service type.
     */
    private function buildEventLocation(ServiceBooking $booking): string
    {
        $isHome = $booking->service?->is_home_service ?? false;

        if ($isHome && $booking->service_address) {
            return $booking->service_address;
        }

        $store = $booking->service?->store;
        $branch = $store?->branches()?->where('is_principal', true)->first();

        return $branch?->address ?? $store?->address ?? '';
    }

    private function trySendMail(
        string $to,
        string $name,
        ServiceBooking $booking,
        string $role,
        ?string $icsContent,
        bool $gcalOk,
    ): void {
        // Validar formato de email antes de encolar
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning("GoogleCalendar: Email inválido para [{$role}]", [
                'booking_id' => $booking->id,
                'to' => $to,
            ]);
            return;
        }

        try {
            Mail::to($to)->queue(
                new BookingConfirmationMail(
                    booking: $booking,
                    recipientName: $name,
                    role: $role,
                    icsContent: $icsContent,
                    gcalOk: $gcalOk,
                )
            );
        } catch (\Throwable $e) {
            Log::error("GoogleCalendar: Fallo al enviar email de confirmación [{$role}]", [
                'booking_id' => $booking->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function tryDeleteEventById(
        Calendar $calendar,
        string $calendarId,
        string $eventId,
        int $bookingId,
        string $label,
    ): void {
        try {
            $calendar->events->delete($calendarId, $eventId);
            Log::info("GoogleCalendar: Evento del {$label} eliminado", [
                'booking_id' => $bookingId,
                'event_id' => $eventId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("GoogleCalendar: No se pudo eliminar evento del {$label}", [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
