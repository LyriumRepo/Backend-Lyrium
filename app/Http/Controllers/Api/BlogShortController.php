<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogShort;
use App\Models\Store;
use App\Services\ContentLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogShortController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $shorts = BlogShort::where('store_id', $store->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $shorts->items(),
            'meta' => [
                'current_page' => $shorts->currentPage(),
                'per_page' => $shorts->perPage(),
                'total' => $shorts->total(),
                'total_pages' => $shorts->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => BlogShort::findOrFail($id)]);
    }

    public function store(Request $request, ContentLimitService $contentLimit): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        if ($error = $contentLimit->checkBioblogLimit($store, BlogShort::class, 'bioblog_shorts_per_week', 'shorts')) {
            return response()->json(['success' => false, 'message' => $error, 'upgrade_required' => true], 403);
        }

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:30', 'in:tiktok,youtube_shorts,instagram_reels'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $data['store_id'] = $store->id;
        $short = BlogShort::create($data);

        return response()->json(['success' => true, 'data' => $short], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $short = BlogShort::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:30', 'in:tiktok,youtube_shorts,instagram_reels'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $short->update($data);

        return response()->json(['success' => true, 'data' => $short]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        BlogShort::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
