<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TicketMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user->name,
            'role' => $this->user->frontend_role === 'administrator' ? 'Admin' : 'Vendedor',
            'timestamp' => $this->created_at->format('H:i'),
            'texto' => $this->content,
            'isUser' => $this->user_id === $request->user()?->id,
            'hora' => $this->created_at->format('H:i'),
            'leido' => $this->is_read,
            'tipo' => $this->type,
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'name' => $a->name,
                    'type' => $a->file_type,
                    'url' => asset('storage/'.$a->path),
                ]);
            }),
        ];
    }
}
