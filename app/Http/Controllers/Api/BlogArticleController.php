<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Store;
use App\Services\ContentLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class BlogArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $articles = BlogArticle::where('store_id', $store->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $articles->items(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'total_pages' => $articles->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => BlogArticle::findOrFail($id)]);
    }

    public function store(Request $request, ContentLimitService $contentLimit): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        if ($error = $contentLimit->checkBioblogLimit($store, BlogArticle::class, 'bioblog_articles_per_week', 'artículos')) {
            return response()->json(['success' => false, 'message' => $error, 'upgrade_required' => true], 403);
        }

        $data = $request->validate([
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'main_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['store_id'] = $store->id;
        $data['slug'] = Str::slug($data['title']).'-'.Str::random(5);

        $article = BlogArticle::create($data);

        return response()->json(['success' => true, 'data' => $article], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $article = BlogArticle::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'main_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);

        if ($data['title'] !== $article->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::random(5);
        }

        $article->update($data);

        return response()->json(['success' => true, 'data' => $article]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        BlogArticle::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
