<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogArticleResource;
use App\Http\Resources\BlogCategoryResource;
use App\Http\Resources\BlogCommentResource;
use App\Http\Resources\BlogPodcastResource;
use App\Http\Resources\BlogShortResource;
use App\Http\Resources\BlogVideoResource;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPodcast;
use App\Models\BlogShort;
use App\Models\BlogVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = BlogCategory::withCount('articles')
            ->orderBy('sort_order')
            ->get();

        return $this->success(BlogCategoryResource::collection($categories));
    }

    public function posts(Request $request): JsonResponse
    {
        $query = BlogArticle::with(['category', 'store'])
            ->where('status', 'published');

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 12), 50);
        $posts = $query->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'data' => BlogArticleResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 20);

        $posts = BlogArticle::with(['category', 'store'])
            ->where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(BlogArticleResource::collection($posts));
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 20);

        $posts = BlogArticle::with(['category', 'store'])
            ->where('status', 'published')
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(BlogArticleResource::collection($posts));
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogArticle::with(['category', 'store'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $post) {
            return $this->error('Artículo no encontrado', 404);
        }

        return $this->success(new BlogArticleResource($post));
    }

    public function comments(Request $request): JsonResponse
    {
        $articleId = $request->query('article_id');
        $postId = $request->query('post_id');

        $query = BlogComment::where('is_approved', true);

        if ($articleId) {
            $query->where('commentable_id', $articleId)
                ->where('commentable_type', 'article');
        } elseif ($postId) {
            $query->where('commentable_id', $postId)
                ->where('commentable_type', 'post');
        } else {
            return $this->error('article_id o post_id es requerido', 422);
        }

        $comments = $query->orderBy('created_at', 'desc')->get();

        return $this->success(BlogCommentResource::collection($comments));
    }

    public function storeComment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'article_id' => 'nullable|integer|exists:blog_articles,id',
            'post_id' => 'nullable|integer|exists:blog_posts,id',
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email|max:255',
            'content' => 'required|string|max:5000',
        ]);

        if ($data['article_id'] ?? null) {
            $commentableType = 'article';
            $commentableId = $data['article_id'];
        } elseif ($data['post_id'] ?? null) {
            $commentableType = 'post';
            $commentableId = $data['post_id'];
        } else {
            return $this->error('article_id o post_id es requerido', 422);
        }

        $comment = BlogComment::create([
            'commentable_id' => $commentableId,
            'commentable_type' => $commentableType,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'],
            'content' => $data['content'],
            'is_approved' => true,
        ]);

        return $this->success(new BlogCommentResource($comment), null, 201);
    }

    public function podcasts(): JsonResponse
    {
        $podcasts = BlogPodcast::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogPodcastResource::collection($podcasts));
    }

    public function videos(): JsonResponse
    {
        $videos = BlogVideo::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogVideoResource::collection($videos));
    }

    public function shorts(): JsonResponse
    {
        $shorts = BlogShort::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogShortResource::collection($shorts));
    }
}
