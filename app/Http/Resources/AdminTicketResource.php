<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vendor = $this->user;
        $admin = $this->assignedAdmin;
        $currentUserId = $request->user()?->id;
        $orderedMessages = $this->relationLoaded('messages')
            ? $this->messages->sortBy('id')->values()
            : null;
        $lastMessage = $this->relationLoaded('latestMessage')
            ? $this->latestMessage
            : ($orderedMessages?->last());
        $activityAt = $lastMessage?->created_at ?? $this->updated_at ?? $this->created_at;

        return [
            'id' => $this->id,
            'numero' => $this->ticket_number,
            'asunto' => $this->subject,
            'descripcion' => $this->description,
            'ultimo_mensaje' => $lastMessage?->content ?? $this->description,
            'vendedor' => [
                'id' => $vendor->id,
                'nombre' => $vendor->name,
                'empresa' => $this->store?->trade_name,
            ],
            'admin_asignado' => $admin ? [
                'id' => $admin->id,
                'nombre' => $admin->name,
            ] : null,
            'categoria' => $this->category,
            'prioridad' => $this->mapPriority($this->priority),
            'estado' => $this->mapStatus($this->status),
            'fecha_creacion' => $this->created_at->toIso8601String(),
            'fecha_actualizacion' => $activityAt->toIso8601String(),
            'total_mensajes' => $this->messages_count ?? $this->messages->count(),
            'mensajes_sin_leer' => $currentUserId ? $this->unreadMessagesFor($currentUserId) : 0,
            'is_critical' => $this->is_critical,
            'is_escalated' => $this->is_escalated,
            'escalated_to' => $this->escalated_to,
            'satisfaction_rating' => $this->satisfaction_rating,
            'mensajes' => $this->when(
                $orderedMessages !== null,
                fn () => TicketMessageResource::collection($orderedMessages)
            ),
        ];
    }

    private function mapPriority(string $priority): string
    {
        return match ($priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica',
            default => $priority,
        };
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'open' => 'Abierto',
            'in_progress' => 'En Proceso',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
            default => $status,
        };
    }
}
