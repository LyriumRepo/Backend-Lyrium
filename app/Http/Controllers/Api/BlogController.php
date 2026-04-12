<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Http\Resources\BlogCommentResource;
use App\Http\Resources\BlogPodcastResource;
use App\Http\Resources\BlogPostResource;
use App\Http\Resources\BlogVideoResource;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPodcast;
use App\Models\BlogPost;
use App\Models\BlogVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')
            ->get();

        return $this->success(BlogCategoryResource::collection($categories));
    }

    public function posts(Request $request): JsonResponse
    {
        $query = BlogPost::with(['category', 'comments'])
            ->where('is_published', true);

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 12), 50);
        $posts = $query->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'data' => BlogPostResource::collection($posts),
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

        $posts = BlogPost::with(['category'])
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(BlogPostResource::collection($posts));
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 20);

        $posts = BlogPost::with(['category'])
            ->where('is_published', true)
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success(BlogPostResource::collection($posts));
    }

    public function show(string $slug): JsonResponse
    {
        $post = BlogPost::with(['category', 'comments'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $post) {
            return $this->error('Post no encontrado', 404);
        }

        return $this->success(new BlogPostResource($post));
    }

    public function comments(Request $request): JsonResponse
    {
        $postId = $request->query('post_id');

        if (! $postId) {
            return $this->error('post_id es requerido', 422);
        }

        $comments = BlogComment::where('blog_post_id', $postId)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(BlogCommentResource::collection($comments));
    }

    public function storeComment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => 'required|integer|exists:blog_posts,id',
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email|max:255',
            'content' => 'required|string|max:5000',
        ]);

        $comment = BlogComment::create([
            'blog_post_id' => $data['post_id'],
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'],
            'content' => $data['content'],
            'is_approved' => true,
        ]);

        return $this->success(new BlogCommentResource($comment), null, 201);
    }

    public function podcasts(): JsonResponse
    {
        $podcasts = BlogPodcast::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogPodcastResource::collection($podcasts));
    }

    public function videos(): JsonResponse
    {
        $videos = BlogVideo::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogVideoResource::collection($videos));
    }
}
