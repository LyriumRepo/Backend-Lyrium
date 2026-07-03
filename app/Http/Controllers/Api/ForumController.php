<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ForumCategoryResource;
use App\Http\Resources\ForumPostResource;
use App\Http\Resources\ForumTopicResource;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Services\ContentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ForumController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = ForumCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success(ForumCategoryResource::collection($categories)->resolve());
    }

    public function topics(Request $request): JsonResponse
    {
        $query = ForumTopic::with('category')
            ->where('status', 'active');

        if ($categoryId = $request->query('forum')) {
            $query->where('forum_category_id', $categoryId);
        }

        $topics = $query->orderBy('created_at', 'desc')->get();

        return $this->success(ForumTopicResource::collection($topics)->resolve());
    }

    public function topic(int $id): JsonResponse
    {
        $topic = ForumTopic::with('category')->find($id);

        if (! $topic) {
            return $this->notFound('Tema no encontrado.');
        }

        $topic->increment('views');

        return $this->success(new ForumTopicResource($topic));
    }

    public function createTopic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'forumid' => 'required|exists:forum_categories,id',
            'title' => 'required|string|max:180',
            'content' => 'required|string|max:2000',
        ]);

        $moderation = app(ContentModerationService::class)->check($validated['title'].' '.$validated['content']);
        if ($moderation) {
            return $this->error($moderation['message'], 422);
        }

        $topic = ForumTopic::create([
            'forum_category_id' => $validated['forumid'],
            'user_id' => $request->user()?->id,
            'anonymous_name' => $request->user() ? null : 'Anónimo',
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        $topic->load('category');

        ForumCategory::where('id', $validated['forumid'])->increment('topic_count');

        return $this->created([
            'success' => true,
            'id' => $topic->id,
        ]);
    }

    public function posts(int $topicId): JsonResponse
    {
        $posts = ForumPost::with(['replyTo', 'user'])
            ->where('forum_topic_id', $topicId)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->success(ForumPostResource::collection($posts)->resolve());
    }

    public function createPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topicid' => 'required|exists:forum_topics,id',
            'content' => 'required|string',
            'reply_to' => 'nullable|exists:forum_posts,id',
        ]);

        $moderation = app(ContentModerationService::class)->check($validated['content']);
        if ($moderation) {
            return $this->error($moderation['message'], 422);
        }

        $post = ForumPost::create([
            'forum_topic_id' => $validated['topicid'],
            'user_id' => $request->user()?->id,
            'anonymous_name' => $request->user() ? null : 'Anónimo',
            'content' => $validated['content'],
            'reply_to_id' => $validated['reply_to'] ?? null,
        ]);

        ForumTopic::where('id', $validated['topicid'])->increment('reply_count');

        $categoryId = ForumTopic::where('id', $validated['topicid'])->value('forum_category_id');
        ForumCategory::where('id', $categoryId)->increment('post_count');

        return $this->created(['success' => true]);
    }

    public function updatePost(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::findOrFail($id);
        $user = $request->user();

        if (! $user || ! $post->user_id || $user->id !== $post->user_id) {
            return $this->error('No tienes permiso para editar esta respuesta', 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $moderation = app(ContentModerationService::class)->check($validated['content']);
        if ($moderation) {
            return $this->error($moderation['message'], 422);
        }

        $post->update(['content' => $validated['content']]);

        return $this->success(['success' => true]);
    }

    public function deletePost(Request $request, int $id): JsonResponse
    {
        $post = ForumPost::findOrFail($id);
        $user = $request->user();

        if (! $user || ! $post->user_id || $user->id !== $post->user_id) {
            return $this->error('No tienes permiso para eliminar esta respuesta', 403);
        }

        $topicId = $post->forum_topic_id;
        $categoryId = ForumTopic::where('id', $topicId)->value('forum_category_id');

        $post->delete();

        ForumTopic::where('id', $topicId)->decrement('reply_count');
        ForumCategory::where('id', $categoryId)->decrement('post_count');

        return $this->success(['message' => 'Respuesta eliminada']);
    }

    public function vote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|integer',
            'type' => 'required|in:up,down',
        ]);

        $post = ForumPost::find($validated['post_id']);
        if (! $post) {
            $topic = ForumTopic::find($validated['post_id']);
            if (! $topic) {
                return $this->notFound('Recurso no encontrado.');
            }

            if ($validated['type'] === 'up') {
                $topic->increment('total_reactions');
            }

            return $this->success(['success' => true]);
        }

        if ($validated['type'] === 'up') {
            $post->increment('likes_count');
        } else {
            $post->increment('angry_count');
        }

        $topic = $post->topic;
        if ($topic) {
            $total = $topic->posts()->select(DB::raw('COALESCE(SUM(likes_count), 0) as total'))->value('total');
            $topic->update(['total_reactions' => $total]);
        }

        return $this->success(['success' => true]);
    }

    public function stats(): JsonResponse
    {
        return $this->success([
            'totalTopics' => ForumTopic::where('status', 'active')->count(),
            'totalReplies' => ForumPost::where('status', 'active')->count(),
            'onlineUsers' => 0,
        ]);
    }
}
