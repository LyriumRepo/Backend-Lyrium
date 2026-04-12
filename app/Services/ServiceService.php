<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ServiceService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function paginateForStore(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Service::query()
            ->where('store_id', $storeId)
            ->with(['schedules', 'category'])
            ->latest()
            ->paginate($perPage);
    }

    public function paginatePublic(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Service::query()
            ->where('status', Service::STATUS_ACTIVE)
            ->with(['store', 'schedules', 'category']);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['category_slug'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category_slug']));
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

    public function getActiveByStore(int $storeId): Collection
    {
        return Service::query()
            ->where('store_id', $storeId)
            ->where('status', Service::STATUS_ACTIVE)
            ->with(['category', 'schedules' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();
    }

    public function findOrFail(int $id): Service
    {
        return Service::query()
            ->with(['store', 'schedules', 'category'])
            ->findOrFail($id);
    }

    public function createForStore(int $storeId, array $data): Service
    {
        $data['store_id'] = $storeId;

        $service = Service::create($data);

        if (! empty($data['schedules'])) {
            foreach ($data['schedules'] as $schedule) {
                $service->schedules()->create($schedule);
            }
        }

        return $service->fresh(['schedules']);
    }

    public function update(int $id, array $data): Service
    {
        $service = $this->findOrFail($id);
        $service->update($data);

        if (isset($data['schedules'])) {
            $service->schedules()->delete();
            foreach ($data['schedules'] as $schedule) {
                $service->schedules()->create($schedule);
            }
        }

        return $service->fresh(['schedules']);
    }

    public function delete(int $id): bool
    {
        $service = $this->findOrFail($id);

        return $service->delete();
    }

    public function getAvailableSlots(int $serviceId, string $date): array
    {
        $service = $this->findOrFail($serviceId);

        return $service->getNextAvailableSlot($date) ?? [];
    }

    public function book(int $serviceId, int $userId, array $data): ServiceBooking
    {
        $service = $this->findOrFail($serviceId);

        $schedule = ServiceSchedule::query()
            ->where('service_id', $serviceId)
            ->findOrFail($data['schedule_id']);

        $appointmentDate = \Carbon\Carbon::parse($data['appointment_date']);

        if (! $schedule->isAvailableForBooking($appointmentDate)) {
            throw new \InvalidArgumentException('El horario seleccionado no está disponible');
        }

        return ServiceBooking::create([
            'service_id' => $serviceId,
            'user_id' => $userId,
            'schedule_id' => $data['schedule_id'],
            'appointment_date' => $appointmentDate,
            'status' => ServiceBooking::STATUS_PENDING,
            'total_price' => $service->price,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_status' => 'pending',
            'customer_notes' => $data['notes'] ?? null,
        ]);
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

        return $booking->fresh();
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

        $booking->update([
            'status' => ServiceBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $booking->fresh();
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

        if (! $schedule->isAvailableForBooking($newDateTime)) {
            throw new \InvalidArgumentException('El nuevo horario no está disponible');
        }

        $booking->update([
            'appointment_date' => $newDateTime,
            'reschedule_token' => null,
        ]);

        return $booking->fresh();
    }

    public function getUserBookings(int $userId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ServiceBooking::query()
            ->where('user_id', $userId)
            ->with(['service', 'schedule'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStoreBookings(int $storeId, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ServiceBooking::query()
            ->whereHas('service', fn ($q) => $q->where('store_id', $storeId))
            ->with(['service', 'user'])
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
