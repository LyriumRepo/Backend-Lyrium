<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class UrlMetadataController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
        ]);

        $url = $data['url'];

        $metadata = [
            'title' => null,
            'description' => null,
            'thumbnail' => null,
            'platform' => $this->detectPlatform($url),
            'duration' => null,
            'channel' => null,
            'type' => $this->detectType($url),
        ];

        // Try oEmbed endpoints
        $oEmbed = $this->fetchOEmbed($url);

        if ($oEmbed !== null) {
            $metadata['title'] = $oEmbed['title'] ?? null;
            $metadata['thumbnail'] = $oEmbed['thumbnail_url'] ?? $oEmbed['thumbnail'] ?? null;
            $metadata['duration'] = $oEmbed['duration'] ?? null;
            $metadata['channel'] = $oEmbed['author_name'] ?? null;
            $metadata['description'] = $oEmbed['description'] ?? null;
        }

        // Fallback: try Open Graph tags
        if ($metadata['title'] === null || $metadata['thumbnail'] === null) {
            $og = $this->fetchOpenGraph($url);
            $metadata['title'] = $metadata['title'] ?? $og['title'] ?? null;
            $metadata['description'] = $metadata['description'] ?? $og['description'] ?? null;
            $metadata['thumbnail'] = $metadata['thumbnail'] ?? $og['image'] ?? null;
        }

        return response()->json(['success' => true, 'data' => $metadata]);
    }

    private function detectPlatform(string $url): string
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) return 'youtube';
        if (str_contains($url, 'spotify.com')) return 'spotify';
        if (str_contains($url, 'tiktok.com')) return 'tiktok';
        if (str_contains($url, 'vimeo.com')) return 'vimeo';
        if (str_contains($url, 'apple.com')) return 'apple_podcasts';
        return 'other';
    }

    private function detectType(string $url): string
    {
        if (str_contains($url, 'youtube.com/shorts') || str_contains($url, 'tiktok.com')) return 'video';
        if (str_contains($url, 'spotify.com') || str_contains($url, 'apple.com')) return 'audio';
        return 'video';
    }

    private function fetchOEmbed(string $url): ?array
    {
        $platform = $this->detectPlatform($url);

        $endpoints = [
            'youtube' => 'https://www.youtube.com/oembed?url=' . urlencode($url) . '&format=json',
            'spotify' => 'https://open.spotify.com/oembed?url=' . urlencode($url) . '&format=json',
            'vimeo' => 'https://vimeo.com/api/oembed.json?url=' . urlencode($url),
            'tiktok' => 'https://www.tiktok.com/oembed?url=' . urlencode($url),
        ];

        $endpoint = $endpoints[$platform] ?? null;
        if ($endpoint === null) return null;

        try {
            $response = Http::timeout(5)->get($endpoint);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            // Silently fail
        }

        return null;
    }

    private function fetchOpenGraph(string $url): array
    {
        try {
            $response = Http::timeout(5)->get($url);
            if (!$response->successful()) return [];

            $html = $response->body();
            $og = [];

            if (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $m)) {
                $og['title'] = $m[1];
            }
            if (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $m)) {
                $og['description'] = $m[1];
            }
            if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $m)) {
                $og['image'] = $m[1];
            }

            return $og;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
