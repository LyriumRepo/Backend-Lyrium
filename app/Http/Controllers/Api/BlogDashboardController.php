<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\BlogPodcast;
use App\Models\BlogShort;
use App\Models\BlogVideo;
use App\Models\ForumTopic;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $articlesCount = BlogArticle::where('store_id', $store->id)->where('status', 'published')->count();
        $podcastsCount = BlogPodcast::where('store_id', $store->id)->where('status', 'published')->count();
        $videosCount = BlogVideo::where('store_id', $store->id)->where('status', 'published')->count();
        $shortsCount = BlogShort::where('store_id', $store->id)->where('status', 'published')->count();
        $totalViews = BlogArticle::where('store_id', $store->id)->sum('views_count')
            + BlogPodcast::where('store_id', $store->id)->sum('views_count')
            + BlogVideo::where('store_id', $store->id)->sum('views_count')
            + BlogShort::where('store_id', $store->id)->sum('views_count');
        $forumTopics = ForumTopic::where('store_id', $store->id)->count();
        $forumReplies = $store->id; // simplified

        $recent = collect()
            ->merge(BlogArticle::where('store_id', $store->id)->get()->map(fn ($a) => ['id' => $a->id, 'type' => 'article', 'title' => $a->title, 'status' => $a->status, 'published_at' => $a->published_at?->toIso8601String(), 'created_at' => $a->created_at->toIso8601String(), 'views' => $a->views_count]))
            ->merge(BlogPodcast::where('store_id', $store->id)->get()->map(fn ($p) => ['id' => $p->id, 'type' => 'podcast', 'title' => $p->title, 'status' => $p->status, 'published_at' => $p->published_at?->toIso8601String(), 'created_at' => $p->created_at->toIso8601String(), 'views' => $p->views_count]))
            ->merge(BlogVideo::where('store_id', $store->id)->get()->map(fn ($v) => ['id' => $v->id, 'type' => 'video', 'title' => $v->title, 'status' => $v->status, 'published_at' => $v->published_at?->toIso8601String(), 'created_at' => $v->created_at->toIso8601String(), 'views' => $v->views_count]))
            ->merge(BlogShort::where('store_id', $store->id)->get()->map(fn ($s) => ['id' => $s->id, 'type' => 'short', 'title' => $s->title, 'status' => $s->status, 'published_at' => $s->published_at?->toIso8601String(), 'created_at' => $s->created_at->toIso8601String(), 'views' => $s->views_count]))
            ->sortByDesc(fn ($item) => $item['published_at'] ?? $item['created_at'])
            ->values()
            ->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => [
                    'articles' => $articlesCount,
                    'podcasts' => $podcastsCount,
                    'videos' => $videosCount,
                    'shorts' => $shortsCount,
                    'total_views' => $totalViews,
                    'forum_topics' => $forumTopics,
                    'forum_replies' => $forumReplies,
                ],
                'recent' => $recent,
            ],
        ]);
    }
}
