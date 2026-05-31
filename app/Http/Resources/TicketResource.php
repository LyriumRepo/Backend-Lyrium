<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $store = $this->store;
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
            'id_display' => str_replace('TKT-', '', $this->ticket_number),
            'titulo' => $this->subject,
            'descripcion' => $this->description,
            'ultimo_mensaje' => $lastMessage?->content ?? $this->description,
            'status' => $this->mapStatus($this->status),
            'type' => $this->category,
            'critical' => $this->is_critical,
            'tiempo' => $activityAt->diffForHumans(),
            'mensajes_count' => $this->messages_count ?? $this->messages->count(),
            'mensajes_sin_leer' => $currentUserId ? $this->unreadMessagesFor($currentUserId) : 0,
            'survey_required' => $this->status === 'closed' && $this->satisfaction_rating === null,
            'satisfaction_rating' => $this->satisfaction_rating,
            'satisfaction_comment' => $this->satisfaction_comment,
            'escalated' => $this->is_escalated,
            'escalated_to' => $this->escalated_to,
            'tienda' => [
                'razon_social' => $store?->trade_name ?? '',
                'nombre_comercial' => $store?->trade_name ?? '',
            ],
            'contacto_adm' => [
                'nombre' => $admin?->name ?? 'Sin asignar',
                'apellido' => '',
                'numeros' => $admin?->phone ?? '',
                'correo' => $admin?->email ?? '',
            ],
            'mensajes' => $this->when(
                $orderedMessages !== null,
                fn () => TicketMessageResource::collection($orderedMessages)
            ),
            'has_more_messages' => $this->when(
                $orderedMessages !== null,
                fn () => $this->messages_count > $orderedMessages->count()
            ),
            'oldest_message_id' => $this->when(
                $orderedMessages !== null && $orderedMessages->isNotEmpty(),
                fn () => $orderedMessages->first()?->id
            ),
        ];
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'open' => 'abierto',
            'in_progress' => 'proceso',
            'resolved' => 'resuelto',
            'closed' => 'cerrado',
            'reopened' => 'abierto',
            default => $status,
        };
    }
}
