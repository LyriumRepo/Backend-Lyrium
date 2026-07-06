<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogVideo;
use App\Models\Store;
use App\Services\ContentLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogVideoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $videos = BlogVideo::where('store_id', $store->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $videos->items(),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
                'total_pages' => $videos->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => BlogVideo::findOrFail($id)]);
    }

    public function store(Request $request, ContentLimitService $contentLimit): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        if ($error = $contentLimit->checkBioblogLimit($store, BlogVideo::class, 'bioblog_videos_per_week', 'videos')) {
            return response()->json(['success' => false, 'message' => $error, 'upgrade_required' => true], 403);
        }

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:30', 'in:youtube,vimeo,tiktok'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1800'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $data['store_id'] = $store->id;
        $video = BlogVideo::create($data);

        return response()->json(['success' => true, 'data' => $video], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $video = BlogVideo::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:30', 'in:youtube,vimeo,tiktok'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1800'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $video->update($data);

        return response()->json(['success' => true, 'data' => $video]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        BlogVideo::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
