<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TicketMessage;
use Illuminate\Http\UploadedFile;

final class TicketAttachmentService
{
    /**
     * Persist uploaded image files as ticket attachments.
     *
     * @param  UploadedFile[]  $files
     */
    public function storeAttachments(TicketMessage $message, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store(
                "tickets/attachments/{$message->ticket_id}/{$message->id}",
                'public'
            );

            $message->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'file_type' => 'image',
                'path' => $path,
            ]);
        }
    }
}
