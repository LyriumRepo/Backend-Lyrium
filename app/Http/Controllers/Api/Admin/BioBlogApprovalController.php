<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\BlogPodcast;
use App\Models\BlogShort;
use App\Models\BlogVideo;
use App\Models\User;
use App\Notifications\BlogContentStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BioBlogApprovalController extends Controller
{
    public function pending(): JsonResponse
    {
        $articles = BlogArticle::with('store')
            ->where('status', 'pending_review')
            ->orderByDesc('updated_at')
            ->get()
           ->map(fn ($a) => [...$a->toArray(), 'content_type' => 'article']);

        $podcasts = BlogPodcast::with('store')
            ->where('status', 'pending_review')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($p) => [...$p->toArray(), 'content_type' => 'podcast']);

        $videos = BlogVideo::with('store')
            ->where('status', 'pending_review')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($v) => [...$v->toArray(), 'content_type' => 'video']);

        $shorts = BlogShort::with('store')
            ->where('status', 'pending_review')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($s) => [...$s->toArray(), 'content_type' => 'short']);

        $all = collect()
            ->merge($articles)
            ->merge($podcasts)
            ->merge($videos)
            ->merge($shorts)
            ->sortByDesc('updated_at')
            ->values();

        return response()->json(['success' => true, 'data' => $all]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'pending_articles' => BlogArticle::where('status', 'pending_review')->count(),
            'pending_podcasts' => BlogPodcast::where('status', 'pending_review')->count(),
            'pending_videos' => BlogVideo::where('status', 'pending_review')->count(),
            'pending_shorts' => BlogShort::where('status', 'pending_review')->count(),
            'total_pending' => BlogArticle::where('status', 'pending_review')->count()
                + BlogPodcast::where('status', 'pending_review')->count()
                + BlogVideo::where('status', 'pending_review')->count()
                + BlogShort::where('status', 'pending_review')->count(),
        ]]);
    }

    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveModel($type, $id);
        if (! $model) {
            return response()->json(['error' => 'Contenido no encontrado'], 404);
        }

        $model->status = 'approved';
        $model->save();

        $this->notifySeller($model, 'approved', $request->input('note'));

        return response()->json(['success' => true, 'message' => 'Contenido aprobado']);
    }

    public function reject(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveModel($type, $id);
        if (! $model) {
            return response()->json(['error' => 'Contenido no encontrado'], 404);
        }

        $model->status = 'rejected';
        $model->save();

        $this->notifySeller($model, 'rejected', $request->input('note', 'No cumple con los requisitos de calidad.'));

        return response()->json(['success' => true, 'message' => 'Contenido rechazado']);
    }

    private function resolveModel(string $type, int $id): BlogArticle|BlogPodcast|BlogVideo|BlogShort|null
    {
        return match ($type) {
            'article' => BlogArticle::find($id),
            'podcast' => BlogPodcast::find($id),
            'video' => BlogVideo::find($id),
            'short' => BlogShort::find($id),
            default => null,
        };
    }

    private function notifySeller(BlogArticle|BlogPodcast|BlogVideo|BlogShort $model, string $action, ?string $note): void
    {
        $store = $model->store ?? null;
        if (! $store || ! $store->owner_id) return;

        $owner = User::find($store->owner_id);
        if (! $owner) return;

        $typeLabels = [
            BlogArticle::class => 'artículo',
            BlogPodcast::class => 'podcast',
            BlogVideo::class => 'video',
            BlogShort::class => 'short',
        ];

        $label = $typeLabels[get_class($model)] ?? 'contenido';

        $title = $model->title ?? 'Sin título';

        if ($action === 'approved') {
            $message = "Tu {$label} \"{$title}\" ha sido aprobado. Ya puedes publicarlo.";
        } else {
            $message = "Tu {$label} \"{$title}\" ha sido rechazado. Motivo: " . ($note ?? 'No cumple con los requisitos.');
        }

        $owner->notify(new BlogContentStatusChanged($message, $action, $typeLabels[get_class($model)] ?? 'contenido', $model->id));
    }
}
