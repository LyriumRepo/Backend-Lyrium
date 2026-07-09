<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Blog\BlogReviewNotifier;
use App\Http\Controllers\Controller;
use App\Models\BlogShort;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class BlogShortController extends Controller
{
    use BlogReviewNotifier;

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

    private function resolveTikTokUrl(string $url): string
    {
        if (! preg_match('/^https?:\/\/(vt|vm)\.tiktok\.com\//', $url)) {
            return $url;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => true,
        ]);

        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $finalUrl ?: $url;
    }

    public function store(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:30', 'in:tiktok,youtube_shorts,instagram_reels'],
            'url' => ['required', 'url', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,approved,rejected,published,archived'],
        ]);

        if ($data['platform'] === 'tiktok') {
            $data['url'] = $this->resolveTikTokUrl($data['url']);
        }

        $data['store_id'] = $store->id;
        $short = BlogShort::create($data);

        $this->storeThumbnailLocally($short, $data['thumbnail'] ?? null);

        $this->notifyAdminsOnPendingReview($short, 'short');

        return response()->json(['success' => true, 'data' => $short], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $short = BlogShort::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'platform' => ['sometimes', 'required', 'string', 'max:30', 'in:tiktok,youtube_shorts,instagram_reels'],
            'url' => ['sometimes', 'required', 'url', 'max:500'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:60'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,approved,rejected,published,archived'],
        ]);

        if (isset($data['platform']) && $data['platform'] === 'tiktok') {
            $data['url'] = $this->resolveTikTokUrl($data['url']);
        }

        $short->update($data);

        $short->refresh();
        $this->storeThumbnailLocally($short, $data['thumbnail'] ?? null);
        $this->notifyAdminsOnPendingReview($short, 'short');

        return response()->json(['success' => true, 'data' => $short]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        BlogShort::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    private function storeThumbnailLocally(BlogShort $short, ?string $thumbnailUrl): void
    {
        if (! $thumbnailUrl || ! preg_match('/tiktokcdn\.com/', $thumbnailUrl)) {
            return;
        }

        try {
            $freshUrl = $this->getFreshTikTokThumbnail($short->url);
            $downloadUrl = $freshUrl ?: $thumbnailUrl;

            $response = Http::timeout(15)
                ->withHeaders(['Referer' => 'https://www.tiktok.com/'])
                ->get($downloadUrl);

            if ($response->failed()) {
                return;
            }

            $ext = 'jpg';
            $filename = 'shorts/'.$short->id.'_'.time().'.'.$ext;
            Storage::disk('public')->put($filename, $response->body());

            $short->updateQuietly(['thumbnail' => Storage::disk('public')->url($filename)]);
        } catch (\Throwable) {
            // Silently fail — keep the original URL as fallback
        }
    }

    private function getFreshTikTokThumbnail(string $videoUrl): ?string
    {
        try {
            $oembedUrl = 'https://www.tiktok.com/oembed?url='.urlencode($videoUrl);
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Referer' => 'https://www.tiktok.com/',
                ])
                ->get($oembedUrl);

            if ($response->failed()) {
                return null;
            }

            return $response->json('thumbnail_url');
        } catch (\Throwable) {
            return null;
        }
    }
}
