<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\NewConversationMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\SendConversationMessageRequest;
use App\Http\Resources\ConversationMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationMessageAttachment;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('customer')) {
            $conversations = Conversation::forCustomer($user->id)
                ->with(['store.owner', 'latestMessage'])
                ->withCount('messages')
                ->latest('last_message_at')
                ->latest('created_at')
                ->get();
        } elseif ($user->hasRole('seller') || $user->hasRole('administrator')) {
            $storeIds = $user->stores()->pluck('stores.id')
                ->merge(Store::where('owner_id', $user->id)->pluck('id'))
                ->unique();
            $conversations = Conversation::forStore($storeIds)
                ->with(['store.owner', 'customer', 'latestMessage'])
                ->withCount('messages')
                ->latest('last_message_at')
                ->latest('created_at')
                ->get();
        } else {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'data' => ConversationResource::collection($conversations),
        ]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('customer_user_id', $user->id)
            ->where('store_id', $request->input('store_id'))
            ->where('category', $request->input('category'))
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'customer_user_id' => $user->id,
                'store_id' => $request->input('store_id'),
                'category' => $request->input('category'),
                'subject' => $request->input('subject'),
                'last_message_at' => now(),
            ]);
        }

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->input('message'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $conversation->load(['store.owner', 'latestMessage']);

        $store = Store::find($request->input('store_id'));

        broadcast(new NewConversationMessage(
            message: $message->loadMissing(['sender', 'conversation']),
            customerUserId: $user->id,
            storeId: $store?->id ?? 0,
        ));

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
            'message' => 'Conversación iniciada exitosamente.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $conversation->load([
            'store.owner',
            'customer',
            'messages' => fn ($q) => $q->with(['sender', 'attachments'])->latest()->limit(50),
        ]);

        $conversation->setRelation('messages', $conversation->messages->reverse()->values());

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
        ]);
    }

    public function sendMessage(SendConversationMessageRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new NewConversationMessage(
            message: $message->loadMissing(['sender', 'conversation']),
            customerUserId: $conversation->customer_user_id,
            storeId: $conversation->store_id,
        ));

        return response()->json([
            'success' => true,
            'data' => new ConversationMessageResource($message->load('sender')),
        ], 201);
    }

    public function sendMessageWithAttachment(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required_without:content', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar', 'max:10240'],
        ]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments', 'public');

                $message->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $conversation->update(['last_message_at' => now()]);

        $message->load(['sender', 'attachments']);

        broadcast(new NewConversationMessage(
            message: $message,
            customerUserId: $conversation->customer_user_id,
            storeId: $conversation->store_id,
        ));

        return response()->json([
            'success' => true,
            'data' => new ConversationMessageResource($message),
        ], 201);
    }

    public function downloadAttachment(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        $attachment = ConversationMessageAttachment::with('message.conversation')->findOrFail($id);

        $message = $attachment->message;
        $conversation = $message->conversation;
        $user = $request->user();

        $canAccess = false;
        if ($user->hasRole('customer') && $conversation->customer_user_id === $user->id) {
            $canAccess = true;
        } elseif ($user->hasRole('seller') || $user->hasRole('administrator')) {
            $storeIds = $user->stores()->pluck('stores.id')
                ->merge(Store::where('owner_id', $user->id)->pluck('id'))
                ->unique();
            if ($storeIds->contains($conversation->store_id)) {
                $canAccess = true;
            }
        }

        if (!$canAccess) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para descargar este archivo.',
            ], 403);
        }

        $fullPath = Storage::disk('public')->path($attachment->file_path);

        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado.',
            ], 404);
        }

        return response()->download($fullPath, $attachment->file_name);
    }

    public function getMessages(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $query = $conversation->messages()->with(['sender', 'attachments'])->orderByDesc('id');

        if ($beforeId = $request->query('before_id')) {
            $query->where('id', '<', (int) $beforeId);
        }

        $messages = $query->limit(30)->get();
        $hasMore = $messages->count() === 30;

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => ConversationMessageResource::collection($messages->reverse()->values()),
            'has_more' => $hasMore,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->findConversation($user, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada.',
            ], 404);
        }

        $conversation->update(['status' => 'archived']);

        return response()->json([
            'success' => true,
            'message' => 'Conversación archivada.',
        ]);
    }

    public function stores(Request $request): JsonResponse
    {
        $stores = Store::where('status', 'approved')
            ->with('owner')
            ->get(['id', 'owner_id', 'nombre_comercial', 'trade_name', 'logo']);

        return response()->json([
            'success' => true,
            'data' => $stores->map(fn ($s) => [
                'id' => (string) $s->id,
                'name' => $s->owner?->name ?? '',
                'store' => $s->trade_name ?? $s->nombre_comercial ?? $s->razon_social ?? '',
                'avatar' => $s->owner?->avatar ?? '',
            ]),
        ]);
    }

    private function findConversation($user, int $id): ?Conversation
    {
        if ($user->hasRole('customer')) {
            return Conversation::forCustomer($user->id)->find($id);
        }

        if ($user->hasRole('seller') || $user->hasRole('administrator')) {
            $storeIds = $user->stores()->pluck('stores.id')
                ->merge(Store::where('owner_id', $user->id)->pluck('id'))
                ->unique();
            return Conversation::forStore($storeIds)->find($id);
        }

        return null;
    }
}
