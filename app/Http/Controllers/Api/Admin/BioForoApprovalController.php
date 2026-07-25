<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use App\Models\User;
use App\Notifications\ForumContentStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BioForoApprovalController extends Controller
{
    public function pending(): JsonResponse
    {
        $topics = ForumTopic::with('store')
            ->where('status', 'pending_review')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($t) => [...$t->toArray(), 'content_type' => 'forum_topic']);

        return response()->json(['success' => true, 'data' => $topics]);
    }

    public function stats(): JsonResponse
    {
        $pending = ForumTopic::where('status', 'pending_review')->count();

        return response()->json(['success' => true, 'data' => [
            'pending_topics' => $pending,
            'total_pending' => $pending,
        ]]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $topic = ForumTopic::find($id);
        if (! $topic) {
            return response()->json(['error' => 'Tema no encontrado'], 404);
        }

        $topic->status = 'approved';
        $topic->save();

        $this->notifySeller($topic, 'approved', $request->input('note'));

        return response()->json(['success' => true, 'message' => 'Tema aprobado']);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $topic = ForumTopic::find($id);
        if (! $topic) {
            return response()->json(['error' => 'Tema no encontrado'], 404);
        }

        $topic->status = 'rejected';
        $topic->save();

        $this->notifySeller($topic, 'rejected', $request->input('note', 'No cumple con los requisitos de calidad.'));

        return response()->json(['success' => true, 'message' => 'Tema rechazado']);
    }

    private function notifySeller(ForumTopic $topic, string $action, ?string $note): void
    {
        $store = $topic->store ?? null;
        if (! $store || ! $store->owner_id) {
            return;
        }

        $owner = User::find($store->owner_id);
        if (! $owner) {
            return;
        }

        $title = $topic->title ?? 'Sin título';

        if ($action === 'approved') {
            $message = "Tu tema \"{$title}\" ha sido aprobado. Ya puedes publicarlo.";
        } else {
            $message = "Tu tema \"{$title}\" ha sido rechazado. Motivo: ".($note ?? 'No cumple con los requisitos.');
        }

        $owner->notify(new ForumContentStatusChanged($message, $action, $topic->id));
    }
}
