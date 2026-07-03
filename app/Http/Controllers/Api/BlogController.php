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
use App\Services\ContentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

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
        $this->optionalAuth($request);

        $articleId = $request->query('article_id');
        $postId = $request->query('post_id');
        $videoId = $request->query('video_id');
        $podcastId = $request->query('podcast_id');
        $shortId = $request->query('short_id');

        $query = BlogComment::where('is_approved', true)->with('user');

        if ($articleId) {
            $query->where('commentable_id', $articleId)
                ->where('commentable_type', 'article');
        } elseif ($postId) {
            $query->where('commentable_id', $postId)
                ->where('commentable_type', 'post');
        } elseif ($videoId) {
            $query->where('commentable_id', $videoId)
                ->where('commentable_type', 'video');
        } elseif ($podcastId) {
            $query->where('commentable_id', $podcastId)
                ->where('commentable_type', 'podcast');
        } elseif ($shortId) {
            $query->where('commentable_id', $shortId)
                ->where('commentable_type', 'short');
        } else {
            return $this->error('Se requiere article_id, post_id, video_id, podcast_id o short_id', 422);
        }

        $comments = $query->orderBy('created_at', 'desc')->get();

        return $this->success(BlogCommentResource::collection($comments));
    }

    public function storeComment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'article_id' => 'nullable|integer|exists:blog_articles,id',
            'post_id' => 'nullable|integer|exists:blog_posts,id',
            'video_id' => 'nullable|integer|exists:blog_videos,id',
            'podcast_id' => 'nullable|integer|exists:blog_podcasts,id',
            'short_id' => 'nullable|integer|exists:blog_shorts,id',
            'author_name' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'content' => 'required|string|max:5000',
        ]);

        if ($data['article_id'] ?? null) {
            $commentableType = 'article';
            $commentableId = $data['article_id'];
        } elseif ($data['post_id'] ?? null) {
            $commentableType = 'post';
            $commentableId = $data['post_id'];
        } elseif ($data['video_id'] ?? null) {
            $commentableType = 'video';
            $commentableId = $data['video_id'];
        } elseif ($data['podcast_id'] ?? null) {
            $commentableType = 'podcast';
            $commentableId = $data['podcast_id'];
        } elseif ($data['short_id'] ?? null) {
            $commentableType = 'short';
            $commentableId = $data['short_id'];
        } else {
            return $this->error('Se requiere article_id, post_id, video_id, podcast_id o short_id', 422);
        }

        $moderation = app(ContentModerationService::class)->check($data['content']);
        if ($moderation) {
            return $this->error($moderation['message'], 422);
        }

        $user = $request->user();
        $authorName = $data['author_name'] ?? ($user?->display_name ?? $user?->name ?? 'Anónimo');
        $authorEmail = $data['author_email'] ?? ($user?->email ?? '');

        $comment = BlogComment::create([
            'user_id' => $user?->id,
            'commentable_id' => $commentableId,
            'commentable_type' => $commentableType,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $data['content'],
            'is_approved' => true,
        ]);

        $comment->load('user');

        return $this->success(new BlogCommentResource($comment), null, 201);
    }

    public function updateComment(Request $request, int $id): JsonResponse
    {
        $comment = BlogComment::findOrFail($id);
        $user = $request->user();

        if (! $user || ! $comment->user_id || $user->id !== $comment->user_id) {
            return $this->error('No tienes permiso para editar este comentario', 403);
        }

        $data = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $moderation = app(ContentModerationService::class)->check($data['content']);
        if ($moderation) {
            return $this->error($moderation['message'], 422);
        }

        $comment->update(['content' => $data['content']]);
        $comment->load('user');

        return $this->success(new BlogCommentResource($comment));
    }

    public function deleteComment(Request $request, int $id): JsonResponse
    {
        $comment = BlogComment::findOrFail($id);
        $user = $request->user();

        if (! $user || ! $comment->user_id || $user->id !== $comment->user_id) {
            return $this->error('No tienes permiso para eliminar este comentario', 403);
        }

        $comment->delete();

        return $this->success(['message' => 'Comentario eliminado']);
    }

    public function podcasts(): JsonResponse
    {
        $podcasts = BlogPodcast::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogPodcastResource::collection($podcasts));
    }

    public function showPodcast(int $id): JsonResponse
    {
        $podcast = BlogPodcast::where('id', $id)
            ->where('status', 'published')
            ->first();

        if (! $podcast) {
            return $this->error('Podcast no encontrado', 404);
        }

        return $this->success(new BlogPodcastResource($podcast));
    }

    public function videos(): JsonResponse
    {
        $videos = BlogVideo::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogVideoResource::collection($videos));
    }

    public function showVideo(int $id): JsonResponse
    {
        $video = BlogVideo::where('id', $id)
            ->where('status', 'published')
            ->first();

        if (! $video) {
            return $this->error('Video no encontrado', 404);
        }

        return $this->success(new BlogVideoResource($video));
    }

    public function shorts(): JsonResponse
    {
        $shorts = BlogShort::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->get();

        return $this->success(BlogShortResource::collection($shorts));
    }

    public function showShort(int $id): JsonResponse
    {
        $short = BlogShort::where('id', $id)
            ->where('status', 'published')
            ->first();

        if (! $short) {
            return $this->error('Short no encontrado', 404);
        }

        return $this->success(new BlogShortResource($short));
    }

    private function optionalAuth(Request $request): void
    {
        if ($bearer = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($bearer);
            if ($accessToken?->tokenable) {
                Auth::setUser($accessToken->tokenable);
            }
        }
    }
}
