<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceSchedule;
use App\Models\Category;
use App\Mail\BookingConfirmationMail;
use App\Models\Order;
use App\Models\OrderServiceItem;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\BookingOnTheWayNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class ServiceService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    /**
     * Inyección del servicio de Google Calendar en el constructor.
     */
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar
    ) {}

    public function paginateForStore(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Service::query()
            ->where('store_id', $storeId)
            ->with(['schedules', 'category.parent', 'specialists'])
            ->latest()
            ->paginate($perPage);
    }

    public function paginatePublic(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Service::query()
            ->where('status', Service::STATUS_ACTIVE)
            ->with(['store', 'schedules', 'category.parent', 'specialists']);

        if (! empty($filters['category_id'])) {
            $ids = $this->getDescendantIds((int) $filters['category_id']);
            $query->whereIn('category_id', $ids);
        }

        if (! empty($filters['category_slug'])) {
            $category = Category::where('slug', $filters['category_slug'])->first();
            if ($category) {
                $ids = $this->getDescendantIds($category->id);
                $query->whereIn('category_id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    private function getDescendantIds(int $categoryId): array
    {
        $allIds = [$categoryId];
        $toSearch = [$categoryId];
        $maxDepth = 5;
        $depth = 0;

        while (! empty($toSearch) && $depth < $maxDepth) {
            $children = \App\Models\Category::whereIn('parent_id', $toSearch)
                ->pluck('id')
                ->toArray();

            if (empty($children)) {
                break;
            }

            $allIds = array_merge($allIds, $children);
            $toSearch = $children;
            $depth++;
        }

        return array_unique($allIds);
    }

    public function getActiveByStore(int $storeId): Collection
    {
        return Service::query()
            ->where('store_id', $storeId)
            ->where('status', Service::STATUS_ACTIVE)
            ->with(['category.parent', 'schedules' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();
    }

    public function findOrFail(int $id): Service
    {
        return Service::query()
            ->with(['store', 'schedules', 'category.parent', 'specialists'])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): Service
    {
        return Service::query()
            ->with(['store', 'schedules', 'category.parent', 'specialists.schedules'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function createForStore(int $storeId, array $data): Service
    {
        return DB::transaction(function () use ($storeId, $data) {
            // 1. Unificar el store_id y generar un slug dinámico
            $slug = Str::slug($data['name']).'-'.uniqid();

            // 2. Crear el objeto de Servicio principal con los nuevos campos
            $service = Service::create([
                'store_id' => $storeId,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'image' => $data['image'] ?? null,
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],

                // Nuevas configuraciones del Frontend
                'buffer_minutes' => $data['buffer_minutes'] ?? 10,
                'is_home_service' => $data['is_home_service'] ?? false,
                'booking_advance_hours' => $data['booking_advance_hours'] ?? 24,
                'max_capacity' => $data['max_capacity'] ?? 1,

                'status' => $data['status'] ?? 'draft',
                'cancellation_policy' => $data['cancellation_policy'] ?? 'flexible',
                'max_cancellations' => $data['max_cancellations'] ?? 3,
                'settings' => $data['settings'] ?? null,
                'sticker' => $data['sticker'] ?? null,
                'discount_percentage' => $data['discount_percentage'] ?? null,
            ]);

            // 3. Asociar Especialistas asignados (Muchos a Muchos)
            if (! empty($data['specialist_ids'])) {
                $service->specialists()->sync($data['specialist_ids']);
            }

            // 4. Registrar los bloques de Horarios
            if (! empty($data['schedules'])) {
                // Mapeador inverso para traducir índices numéricos (0-6) de vuelta a strings compatibles con tu DB
                $dayMappingInverse = [
                    0 => 'monday',
                    1 => 'tuesday',
                    2 => 'wednesday',
                    3 => 'thursday',
                    4 => 'friday',
                    5 => 'saturday',
                    6 => 'sunday',
                ];

                foreach ($data['schedules'] as $schedule) {
                    $dayInput = $schedule['day_of_week'];

                    // Si el input es numérico (ej: 0), lo traducimos a 'monday' para la base de datos.
                    $dayValue = is_numeric($dayInput)
                        ? ($dayMappingInverse[(int) $dayInput] ?? 'monday')
                        : strtolower((string) $dayInput);

                    $service->schedules()->create([
                        'day_of_week' => $dayValue,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'max_appointments' => $schedule['max_appointments'] ?? $data['max_capacity'] ?? 1,
                        'is_active' => $schedule['is_active'] ?? true,

                        // ── LAS DOS LÍNEAS QUE SOLUCIONAN TU PROBLEMA ──
                        'specialist_id' => $schedule['specialist_id'] ?? null,
                        'orden_bloque' => $schedule['orden_bloque'] ?? 1,
                    ]);
                }
            }

            // Devolvemos el servicio fresco cargando las relaciones necesarias
            return $service->fresh(['schedules', 'specialists']);
        });
    }

    public function update(int $id, array $data): Service
    {
        $service = $this->findOrFail($id);
        $service->update($data);

        if (! empty($data['specialist_ids'])) {
            $service->specialists()->sync($data['specialist_ids']);
        }

        if (isset($data['schedules'])) {
            $service->schedules()->delete();
            foreach ($data['schedules'] as $schedule) {
                $service->schedules()->create($schedule);
            }
        }

        return $service->fresh(['schedules', 'specialists']);
    }

    public function delete(int $id): bool
    {
        $service = $this->findOrFail($id);

        return $service->delete();
    }

    public function getAvailableSlots(int $serviceId, int $specialistId, string $dateString): array
    {
        $service = $this->findOrFail($serviceId);
        $specialist = \App\Models\Specialist::findOrFail($specialistId);
        $date = \Carbon\Carbon::parse($dateString);

        // 1. Obtener TODOS los bloques horarios definidos para ESTE especialista y ESTE día
        $dayName = strtolower($date->format('l')); // 'monday', 'tuesday', etc.

        // Cambiamos ->first() por ->get() y filtramos por specialist_id
        $schedules = $service->schedules()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->where('specialist_id', $specialistId)
            ->where('day_of_week', $dayName)
            ->get();

        if ($schedules->isEmpty()) {
            return []; // El especialista no atiende este día para este servicio
        }

        // 2. Generar todos los intervalos teóricos iterando por cada bloque horario
        $duration = (int) $service->duration_minutes;
        $buffer = (int) ($service->buffer_minutes ?? 10);
        $step = $duration + $buffer;

        $allSlots = [];

        foreach ($schedules as $schedule) {
            $startStr = (string) $schedule->start_time;
            $endStr = (string) $schedule->end_time;

            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', strlen($startStr) === 5 ? $startStr.':00' : $startStr);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', strlen($endStr) === 5 ? $endStr.':00' : $endStr);

            $current = $startTime->copy();

            // Generamos los slots específicos de este bloque
            while ($current->copy()->addMinutes($duration)->lte($endTime)) {
                $allSlots[] = [
                    'time' => $current->format('H:i'),
                    'carbon_start' => $date->copy()->setTimeFromTimeString($current->format('H:i:00')),
                    'carbon_end' => $date->copy()->setTimeFromTimeString($current->copy()->addMinutes($duration)->format('H:i:00')),
                ];
                $current->addMinutes($step);
            }
        }

        // 3. Consultar Ciegamente la API FreeBusy de Google Calendar para el especialista
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        $googleBusySlots = $this->googleCalendar->getBusySlots($specialist, $dayStart, $dayEnd);

        // 4. Obtener las reservas locales que ya están confirmadas o pendientes en MySQL
        $localBookings = \App\Models\ServiceBooking::where('service_id', $serviceId)
            ->where('specialist_id', $specialistId)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereNotIn('status', [\App\Models\ServiceBooking::STATUS_CANCELLED])
            ->get();

        // 4b. Obtener holds activos para excluir slots tomados temporalmente
        $activeHolds = \App\Models\ServiceSlotHold::where('service_id', $serviceId)
            ->where('specialist_id', $specialistId)
            ->where('appointment_date', $date->toDateString())
            ->active()
            ->pluck('start_time')
            ->toArray();

        // 5. Filtrar el cruce de disponibilidades
        $availableHours = [];

        foreach ($allSlots as $slot) {
            $slotTimeStr = $slot['time'];
            $slotStart = $slot['carbon_start'];

            // A. Validar que la hora no sea en el pasado si es el día de hoy
            if ($slotStart->isPast()) {
                continue;
            }

            // B. Cruzar contra Base de Datos Local (MySQL)
            $isLocalBusy = $localBookings->contains(function ($booking) use ($slotStart) {
                return \Carbon\Carbon::parse($booking->appointment_date)->format('H:i') === $slotStart->format('H:i');
            });

            if ($isLocalBusy) {
                continue;
            }

            // B2. Excluir slots con holds activos (en carrito de otro usuario)
            if (in_array($slotTimeStr, $activeHolds, true)) {
                continue;
            }

            // C. Cruzar contra Google Calendar FreeBusy
            $isGoogleBusy = false;
            foreach ($googleBusySlots as $busyRange) {
                $busyStart = \Carbon\Carbon::createFromFormat('H:i', $busyRange['start']);
                $busyEnd = \Carbon\Carbon::createFromFormat('H:i', $busyRange['end']);
                $slotTime = \Carbon\Carbon::createFromFormat('H:i', $slotTimeStr);

                if ($slotTime->gte($busyStart) && $slotTime->lt($busyEnd)) {
                    $isGoogleBusy = true;
                    break;
                }
            }

            if ($isGoogleBusy) {
                continue;
            }

            // Si pasa todos los filtros, está verdaderamente LIBRE
            $availableHours[] = $slotTimeStr;
        }

        return $availableHours;
    }

    /**
     * Crear una reserva para un servicio, validando la disponibilidad del horario.
     */
    public function book(int $serviceId, int $userId, array $data): ServiceBooking
    {
        $service = $this->findOrFail($serviceId);

        $startTime = $data['start_time'] ?? request('start_time') ?? '00:00';
        $appointmentDate = \Carbon\Carbon::parse($data['appointment_date'].' '.$startTime);

        $booking = \Illuminate\Support\Facades\DB::transaction(
            function () use ($service, $appointmentDate, $userId, $data) {

                $schedule = \App\Models\ServiceSchedule::lockForUpdate()
                    ->where('service_id', $service->id)
                    ->findOrFail($data['schedule_id']);

                if (! $schedule->isAvailableForBooking($appointmentDate->toDateTimeString())) {
                    throw new \InvalidArgumentException('El horario seleccionado no está disponible');
                }

                $booking = ServiceBooking::create([
                    'service_id'       => $service->id,
                    'user_id'          => $userId,
                    'schedule_id'      => $schedule->id,
                    'appointment_date' => $appointmentDate,
                    'status' => \App\Models\ServiceBooking::STATUS_PENDING,
                    'total_price' => $service->price,
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_status' => 'pending',
                    'customer_notes' => $data['customer_notes'] ?? $data['notes'] ?? null,
                    'specialist_id' => $data['specialist_id'] ?? null,
                ]);

                // ── Crear o actualizar Order + OrderServiceItem ──
                $order = Order::create([
                    'order_number'  => Order::generateOrderNumber(),
                    'order_type'    => Order::ORDER_TYPE_SERVICE,
                    'user_id'       => $userId,
                    'status'        => Order::STATUS_PENDING_SELLER,
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_status' => 'pending',
                    'subtotal'      => $service->price,
                    'total'         => $service->price,
                ]);

                $specialistName = null;
                if (! empty($data['specialist_id'])) {
                    $specialist = \App\Models\Specialist::find($data['specialist_id']);
                    $specialistName = $specialist?->nombres . ' ' . $specialist?->apellidos;
                }

                OrderServiceItem::create([
                    'order_id'              => $order->id,
                    'service_booking_id'    => $booking->id,
                    'service_id'            => $service->id,
                    'store_id'              => $service->store_id,
                    'specialist_id'         => $data['specialist_id'] ?? null,
                    'service_name'          => $service->name,
                    'quantity'              => 1,
                    'unit_price'            => $service->price,
                    'line_total'            => $service->price,
                    'status'                => 'pending',
                    'appointment_date'      => $appointmentDate,
                    'modality'              => $service->is_home_service ? 'home' : 'in_person',
                    'duration_minutes'      => $service->duration_minutes,
                    'service_snapshot'      => $service->toArray(),
                    'store_name_snapshot'   => $service->store?->store_name ?? $service->store?->trade_name,
                    'specialist_name_snapshot' => $specialistName,
                ]);

                return $booking;
            }
        );

        // ── Google Calendar va FUERA de la transacción (API externa asíncrona) ──
        $eventIds = $this->googleCalendar->createEvent($booking);

        $updateData = array_filter([
            'google_event_id' => $eventIds['specialist'],
            'google_event_id_client' => $eventIds['client'],
            'google_event_id_seller' => $eventIds['seller'],
        ]);

        if (! empty($updateData)) {
            $booking->update($updateData);
            $booking->fill($updateData);
        }

        $storeUser = $booking->service?->store?->owner;
        if ($storeUser) {
            try {
                $storeUser->notify(new BookingCreatedNotification($booking, 'seller'));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingCreated al vendedor', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        if ($booking->user) {
            try {
                $booking->user->notify(new BookingCreatedNotification($booking, 'client'));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingCreated al cliente', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return $booking;
    }

    public function confirmBooking(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service', 'schedule'])
            ->findOrFail($bookingId);

        if (! $booking->canConfirm()) {
            throw new \InvalidArgumentException('Esta reserva no puede ser confirmada');
        }

        $booking->update([
            'status' => ServiceBooking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        OrderServiceItem::where('service_booking_id', $bookingId)
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);

        $orderServiceItem = \App\Models\OrderServiceItem::where('service_booking_id', $bookingId)->first();
        if ($orderServiceItem?->order) {
            $orderServiceItem->order->refreshGlobalStatus();
        }

        $booking = $booking->fresh()->load('service');

        try {
            \App\Events\BookingConfirmed::dispatch($booking);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('BookingConfirmed broadcast failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($booking->user) {
            try {
                $booking->user->notify(new BookingConfirmedNotification($booking));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingConfirmed al cliente', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }

            Mail::to($booking->user->email)
                ->queue(new BookingConfirmationMail(
                    booking: $booking,
                    recipientName: $booking->user->name,
                    role: 'client',
                    icsContent: null,
                    gcalOk: true,
                ));
        }

        return $booking;
    }

    public function cancelBooking(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service'])
            ->findOrFail($bookingId);

        if (! $booking->canCancel()) {
            throw new \InvalidArgumentException('Esta reserva no puede ser cancelada');
        }

        $service = $booking->service;
        if ($service->canCancelWithoutRefund()) {
            throw new \InvalidArgumentException('Política de cancelación: Sin reembolso');
        }

        // FIX: fresh() para asegurarnos de tener google_event_id actualizado en memoria
        $this->googleCalendar->deleteEvent($booking->fresh(['service.store']));

        $booking->update([
            'status' => ServiceBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $booking = $booking->fresh()->load('service');

        \App\Events\BookingCancelled::dispatch($booking);

        $storeUser = $booking->service?->store?->owner;
        if ($storeUser) {
            try {
                $storeUser->notify(new BookingCancelledNotification($booking, 'seller'));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingCancelled al vendedor', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        if ($booking->user) {
            try {
                $booking->user->notify(new BookingCancelledNotification($booking, 'client'));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingCancelled al cliente', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return $booking;
    }

    public function markAsNoShow(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service'])
            ->findOrFail($bookingId);

        if ($booking->status !== ServiceBooking::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Solo se puede marcar como no presentarse reservas confirmadas');
        }

        $booking->markAsNoShow();

        $service = $booking->service;
        $store = $service->store;

        if ($store && $store->strikes !== null) {
            $store->addStrike();
        }

        return $booking->fresh();
    }

    public function completeBooking(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service'])
            ->findOrFail($bookingId);

        if (! $booking->canComplete()) {
            throw new \InvalidArgumentException('Solo se puede completar reservas confirmadas o en camino');
        }

        $booking->update(['status' => ServiceBooking::STATUS_COMPLETED]);

        OrderServiceItem::where('service_booking_id', $bookingId)
            ->whereIn('status', ['confirmed', 'on_the_way'])
            ->update(['status' => 'completed']);

        $orderServiceItem = \App\Models\OrderServiceItem::where('service_booking_id', $bookingId)->first();
        if ($orderServiceItem?->order) {
            $orderServiceItem->order->refreshGlobalStatus();
        }

        return $booking->fresh();
    }

    public function markAsOnTheWay(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service'])
            ->findOrFail($bookingId);

        if (! $booking->canMarkOnTheWay()) {
            throw new \InvalidArgumentException('Solo se puede marcar como en camino reservas confirmadas');
        }

        $booking->markAsOnTheWay();

        OrderServiceItem::where('service_booking_id', $bookingId)
            ->where('status', 'confirmed')
            ->update(['status' => 'on_the_way']);

        $orderServiceItem = \App\Models\OrderServiceItem::where('service_booking_id', $bookingId)->first();
        if ($orderServiceItem?->order) {
            $orderServiceItem->order->refreshGlobalStatus();
        }

        $booking = $booking->fresh()->load('service', 'user');

        if ($booking->user) {
            try {
                $booking->user->notify(new BookingOnTheWayNotification($booking));
            } catch (\Throwable $e) {
                Log::error('[Booking] Error notificando BookingOnTheWay al cliente', [
                    'booking_id' => $booking->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        return $booking;
    }

    public function confirmCompletion(int $bookingId): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service'])
            ->findOrFail($bookingId);

        if (! $booking->canComplete()) {
            throw new \InvalidArgumentException('Esta reserva no puede ser confirmada por el cliente');
        }

        $booking->update(['status' => ServiceBooking::STATUS_COMPLETED]);

        return $booking->fresh();
    }

    public function reschedule(int $bookingId, string $newDateTime, string $token): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service', 'schedule'])
            ->where('reschedule_token', $token)
            ->findOrFail($bookingId);

        if (! $booking->canReschedule()) {
            throw new \InvalidArgumentException('Esta reserva no puede ser reagendada');
        }

        $newDateTime = \Carbon\Carbon::parse($newDateTime);
        $schedule = $booking->schedule;

        if (! $schedule->isAvailableForBooking($newDateTime->toDateTimeString())) {
            throw new \InvalidArgumentException('El nuevo horario no está disponible');
        }

        $booking->update([
            'appointment_date' => $newDateTime,
            'reschedule_token' => null,
        ]);

        // ── Actualizar evento en Google Calendar ─────────────────────────────
        $this->googleCalendar->updateEvent($booking->fresh(['service.store', 'user']));
        // ───────────────────────────────────────────────────────────────────

        $booking = $booking->fresh()->load('service');

        \App\Events\BookingRescheduled::dispatch($booking);

        return $booking;
    }

    public function getUserBookings(int $userId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ServiceBooking::query()
            ->where('user_id', $userId)
            ->with(['service', 'service.store', 'schedule', 'specialist'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStoreBookings(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ServiceBooking::query()
            ->whereHas('service', fn ($q) => $q->where('store_id', $storeId))
            ->with(['service', 'user', 'specialist'])
            ->latest()
            ->paginate($perPage);
    }

    public function addSellerNotes(int $bookingId, string $notes): ServiceBooking
    {
        $booking = ServiceBooking::query()
            ->with(['service', 'service.store'])
            ->findOrFail($bookingId);

        $booking->update(['seller_notes' => $notes]);

        return $booking->fresh();
    }

}
