<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPodcast;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogPodcastController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $podcasts = BlogPodcast::where('store_id', $store->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $podcasts->items(),
            'meta' => [
                'current_page' => $podcasts->currentPage(),
                'per_page' => $podcasts->perPage(),
                'total' => $podcasts->total(),
                'total_pages' => $podcasts->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => BlogPodcast::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'type' => ['required', 'string', 'in:audio,video'],
            'platform' => ['required', 'string', 'max:30'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1800'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $data['store_id'] = $store->id;
        $podcast = BlogPodcast::create($data);

        return response()->json(['success' => true, 'data' => $podcast], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $podcast = BlogPodcast::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'type' => ['required', 'string', 'in:audio,video'],
            'platform' => ['required', 'string', 'max:30'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:1800'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
        ]);

        $podcast->update($data);

        return response()->json(['success' => true, 'data' => $podcast]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        BlogPodcast::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
