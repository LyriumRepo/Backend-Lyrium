<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Store;
use App\Services\ContentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ForumTopicController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $topics = ForumTopic::with(['category', 'user'])
            ->where('store_id', $store->id)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('category_id'), fn ($q, $v) => $q->where('forum_category_id', $v))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $topics->items(),
            'meta' => [
                'current_page' => $topics->currentPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
                'total_pages' => $topics->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $topic = ForumTopic::with(['category', 'user', 'posts.user'])->findOrFail($id);
        $topic->increment('views');

        return response()->json(['success' => true, 'data' => $topic]);
    }

    public function store(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'forum_category_id' => ['required', 'integer', 'exists:forum_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,published,closed'],
        ]);

        $moderation = app(ContentModerationService::class)->check($data['title'].' '.$data['content']);
        if ($moderation) {
            return response()->json(['success' => false, 'message' => $moderation['message']], 422);
        }

        $data['store_id'] = $store->id;
        $data['user_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        $topic = ForumTopic::create($data);

        return response()->json(['success' => true, 'data' => $topic], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $topic = ForumTopic::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'forum_category_id' => ['required', 'integer', 'exists:forum_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'string', 'in:draft,pending_review,published,closed'],
        ]);

        $topic->update($data);

        return response()->json(['success' => true, 'data' => $topic]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        ForumTopic::where('store_id', $store->id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function submitForReview(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $topic = ForumTopic::where('store_id', $store->id)->findOrFail($id);

        if ($topic->status !== 'draft') {
            return response()->json(['error' => 'Solo los temas en borrador pueden enviarse a revisión.'], 422);
        }

        $topic->status = 'pending_review';
        $topic->save();

        return response()->json(['success' => true, 'message' => 'Tema enviado a revisión.']);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $topic = ForumTopic::where('store_id', $store->id)->findOrFail($id);

        if ($topic->status !== 'approved') {
            return response()->json(['error' => 'Solo los temas aprobados pueden publicarse.'], 422);
        }

        $topic->status = 'published';
        $topic->save();

        return response()->json(['success' => true, 'message' => 'Tema publicado.']);
    }

    public function hide(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $topic = ForumTopic::where('store_id', $store->id)->findOrFail($id);

        if ($topic->status !== 'published') {
            return response()->json(['error' => 'Solo los temas publicados pueden ocultarse.'], 422);
        }

        $topic->status = 'draft';
        $topic->save();

        return response()->json(['success' => true, 'message' => 'Tema ocultado.']);
    }

    // ── Replies ──────────────────────────────────────────────────────────

    public function replies(int $topicId): JsonResponse
    {
        $topic = ForumTopic::findOrFail($topicId);
        $posts = ForumPost::where('forum_topic_id', $topic->id)
            ->with('user')
            ->where('status', 'active')
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
            ],
        ]);
    }

    public function storeReply(Request $request, int $topicId): JsonResponse
    {
        $topic = ForumTopic::findOrFail($topicId);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'reply_to_id' => ['nullable', 'integer', 'exists:forum_posts,id'],
        ]);

        $moderation = app(ContentModerationService::class)->check($data['content']);
        if ($moderation) {
            return response()->json(['success' => false, 'message' => $moderation['message']], 422);
        }

        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $post = ForumPost::create([
            'store_id' => $store->id,
            'forum_topic_id' => $topic->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'status' => 'active',
        ]);

        $topic->increment('reply_count');

        return response()->json(['success' => true, 'data' => $post], 201);
    }

    public function hideReply(int $topicId, int $postId): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        ForumTopic::where('store_id', $store->id)->findOrFail($topicId);
        $post = ForumPost::findOrFail($postId);
        $post->update([
            'status' => 'hidden',
            'hidden_by' => $request->user()->id,
            'hidden_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteReply(int $topicId, int $postId): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        ForumTopic::where('store_id', $store->id)->findOrFail($topicId);
        ForumPost::findOrFail($postId)->delete();

        return response()->json(['success' => true]);
    }
}
