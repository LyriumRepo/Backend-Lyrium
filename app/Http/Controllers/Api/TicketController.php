<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\TicketMessageReceived;
use App\Events\TicketMessagesRead;
use App\Events\TicketInboxUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendTicketMessageRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\SubmitSurveyRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Services\TicketAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class TicketController extends Controller
{
    public function __construct(private readonly TicketAttachmentService $attachmentService) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->with('store', 'assignedAdmin', 'latestMessage')
            ->latest('updated_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => TicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::where('user_id', $request->user()->id)
            ->with([
                'store',
                'assignedAdmin',
                'messages' => fn ($q) => $q->with(['user', 'attachments'])
                    ->orderByDesc('id')
                    ->limit(30),
            ])
            ->withCount('messages')
            ->findOrFail($id);

        // Reverse to chronological order for the resource
        $ticket->setRelation('messages', $ticket->messages->reverse()->values());

        $unread = $ticket->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->where('is_read', false)
            ->count();

        $ticket->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($unread > 0) {
            broadcast(new TicketMessagesRead($ticket->id, $request->user()->id));
        }

        return response()->json([
            'success' => true,
            'data' => new TicketResource($ticket),
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user  = $request->user();
        $store = null;

        if ($user->hasRole('seller')) {
            $store = $user->store;

            if (! $store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes tener una tienda registrada para crear tickets.',
                ], 403);
            }
        }

        $priorityMap = [
            'baja'   => 'low',
            'media'  => 'medium',
            'alta'   => 'high',
            'critica' => 'critical',
        ];

        $criticidad = $request->input('criticidad');

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id'       => $user->id,
            'store_id'      => $store?->id,
            'subject'       => $request->input('asunto'),
            'description'   => $request->input('mensaje'),
            'category'      => $request->input('tipo_ticket'),
            'priority'      => $priorityMap[$criticidad] ?? 'medium',
            'is_critical'   => in_array($criticidad, ['alta', 'critica']),
        ]);

        $initialMessage = $ticket->messages()->create([
            'user_id' => $user->id,
            'content' => $request->input('mensaje', ''),
            'type' => 'normal',
        ]);

        if ($request->hasFile('adjuntos')) {
            $this->attachmentService->storeAttachments($initialMessage, $request->file('adjuntos'));
        }

        $admins = User::role('administrator')->get();
        foreach ($admins as $admin) {
            $admin->notify(new TicketCreatedNotification($ticket->load('user', 'store')));
        }

        $ticket->refresh();

        $previewText = $request->filled('mensaje')
            ? Str::limit($request->input('mensaje'), 100)
            : $this->buildImagePreview($request->file('adjuntos') ?? []);
        $updatedAt   = now()->toIso8601String();
        foreach ($admins as $admin) {
            broadcast(new TicketInboxUpdated(
                $admin->id,
                $ticket->id,
                1,
                $previewText,
                1,
                $updatedAt,
            ));
        }
        $ticket->load(['store', 'assignedAdmin', 'messages.user', 'messages.attachments']);
        $ticket->loadCount('messages');

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado exitosamente.',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    public function sendMessage(SendTicketMessageRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes enviar mensajes a un ticket cerrado.',
            ], 422);
        }

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', ''),
            'type' => 'normal',
        ]);

        if ($request->hasFile('attachments')) {
            $this->attachmentService->storeAttachments($message, $request->file('attachments'));
        }

        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'reopened']);
        }

        if ($ticket->assignedAdmin) {
            $ticket->assignedAdmin->notify(
                new TicketRepliedNotification($ticket, $message->load('user'))
            );
        }

        $message->load(['user', 'attachments']);
        $message->setRelation('ticket', $ticket);
        $ticket->touch();
        broadcast(new TicketMessageReceived($message));
        $previewText = $request->filled('content')
            ? Str::limit($message->content, 100)
            : $this->buildImagePreview($request->file('attachments') ?? []);
        $totalMessages = $ticket->messages()->count();
        $updatedAt = $message->created_at?->toIso8601String() ?? now()->toIso8601String();

        User::role('administrator')->each(function (User $admin) use ($ticket, $previewText, $totalMessages, $updatedAt): void {
            broadcast(new TicketInboxUpdated(
                $admin->id,
                $ticket->id,
                $ticket->unreadMessagesFor($admin->id),
                $previewText,
                $totalMessages,
                $updatedAt,
            ));
        });

        return response()->json([
            'success' => true,
            'data' => new \App\Http\Resources\TicketMessageResource($message),
        ], 201);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'El ticket ya está cerrado.',
            ], 422);
        }

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => 'El usuario cerró este ticket.',
            'type' => 'system',
        ]);

        $previewText   = 'El usuario cerró este ticket.';
        $totalMessages = $ticket->messages()->count();
        $updatedAt     = now()->toIso8601String();

        User::role('administrator')->each(function (User $admin) use ($ticket, $previewText, $totalMessages, $updatedAt): void {
            broadcast(new TicketInboxUpdated(
                $admin->id,
                $ticket->id,
                $ticket->unreadMessagesFor($admin->id),
                $previewText,
                $totalMessages,
                $updatedAt,
            ));
        });

        return response()->json([
            'success' => true,
            'message' => 'Ticket cerrado exitosamente.',
        ]);
    }

    public function getMessages(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::where('user_id', $request->user()->id)->findOrFail($id);

        $query = $ticket->messages()
            ->with(['user', 'attachments'])
            ->orderByDesc('id');

        if ($beforeId = $request->query('before_id')) {
            $query->where('id', '<', (int) $beforeId);
        }

        $messages = $query->limit(30)->get();
        $hasMore  = $messages->count() === 30;

        return response()->json([
            'success'  => true,
            'data'     => \App\Http\Resources\TicketMessageResource::collection($messages->reverse()->values()),
            'has_more' => $hasMore,
        ]);
    }

    private function buildImagePreview(array $files): string
    {
        $count = count($files);

        return match (true) {
            $count === 0 => '',
            $count === 1 => '[Imagen]',
            default      => "[{$count} imágenes]",
        };
    }

    public function submitSurvey(SubmitSurveyRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::where('user_id', $request->user()->id)
            ->where('status', 'closed')
            ->findOrFail($id);

        if ($ticket->satisfaction_rating !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Ya enviaste una encuesta para este ticket.',
            ], 422);
        }

        $ticket->update([
            'satisfaction_rating' => $request->input('rating'),
            'satisfaction_comment' => $request->input('comment'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gracias por tu feedback.',
        ]);
    }
}
