<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\TicketMessageReceived;
use App\Events\TicketMessagesRead;
use App\Events\TicketInboxUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendTicketMessageRequest;
use App\Http\Resources\AdminTicketResource;
use App\Http\Resources\TicketMessageResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketRepliedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Services\TicketAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AdminTicketController extends Controller
{
    public function __construct(private readonly TicketAttachmentService $attachmentService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['user', 'store', 'assignedAdmin', 'latestMessage'])
            ->withCount('messages');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('store', fn ($s) => $s->where('trade_name', 'like', "%{$search}%"));
            });
        }

        $tickets = $query->latest('updated_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => AdminTicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::with([
            'user',
            'store',
            'assignedAdmin',
            'messages' => fn ($q) => $q->with(['user', 'attachments'])
                ->orderByDesc('id')
                ->limit(30),
        ])
            ->withCount('messages')
            ->findOrFail($id);

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
            'data' => new AdminTicketResource($ticket),
        ]);
    }

    public function sendMessage(SendTicketMessageRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes responder a un ticket cerrado.',
            ], 422);
        }

        if (! $ticket->assigned_admin_id) {
            $ticket->update(['assigned_admin_id' => $request->user()->id]);
        }

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', ''),
            'type' => $request->input('type', 'normal'),
        ]);

        if ($request->hasFile('attachments')) {
            $this->attachmentService->storeAttachments($message, $request->file('attachments'));
        }

        $ticket->user->notify(
            new TicketRepliedNotification($ticket, $message->load('user'))
        );

        $message->load(['user', 'attachments']);
        $ticket->touch();
        broadcast(new TicketMessageReceived($message));
        $previewText = $request->filled('content')
            ? Str::limit($message->content, 100)
            : $this->buildImagePreview($request->file('attachments') ?? []);
        $totalMessages = $ticket->messages()->count();
        $updatedAt     = $message->created_at?->toIso8601String() ?? now()->toIso8601String();

        broadcast(new TicketInboxUpdated(
            $ticket->user_id,
            $ticket->id,
            $ticket->unreadMessagesFor($ticket->user_id),
            $previewText,
            $totalMessages,
            $updatedAt,
        ));

        User::role('administrator')
            ->where('id', '!=', $request->user()->id)
            ->each(function (User $admin) use ($ticket, $previewText, $totalMessages, $updatedAt): void {
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
            'data' => new TicketMessageResource($message),
        ], 201);
    }

    public function getMessages(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);

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
            'data'     => TicketMessageResource::collection($messages->reverse()->values()),
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

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed,reopened'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $oldStatus = $ticket->status;
        $newStatus = $request->input('status');

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'closed') {
            $updateData['closed_at'] = now();
        }

        $ticket->update($updateData);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => "Estado cambiado de {$oldStatus} a {$newStatus}.",
            'type' => 'system',
        ]);

        $ticket->user->notify(
            new TicketStatusChangedNotification($ticket, $oldStatus, $newStatus)
        );

        $previewText  = "Estado cambiado de {$oldStatus} a {$newStatus}.";
        $totalMessages = $ticket->messages()->count();
        $updatedAt    = now()->toIso8601String();

        // Notificar al seller dueño del ticket
        broadcast(new TicketInboxUpdated(
            $ticket->user_id,
            $ticket->id,
            $ticket->unreadMessagesFor($ticket->user_id),
            $previewText,
            $totalMessages,
            $updatedAt,
        ));

        // Notificar a los demás admins (excluir al que hizo el cambio)
        User::role('administrator')
            ->where('id', '!=', $request->user()->id)
            ->each(function (User $admin) use ($ticket, $previewText, $totalMessages, $updatedAt): void {
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
            'message' => 'Estado actualizado.',
        ]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'admin_id' => ['required', 'exists:users,id'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $admin = User::findOrFail($request->input('admin_id'));

        $ticket->update(['assigned_admin_id' => $admin->id]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => "Ticket asignado a {$admin->name}.",
            'type' => 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ticket asignado a {$admin->name}.",
        ]);
    }

    public function updatePriority(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'priority' => ['required', 'in:low,medium,high,critical'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'priority' => $request->input('priority'),
            'is_critical' => in_array($request->input('priority'), ['high', 'critical']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prioridad actualizada.',
        ]);
    }

    public function escalate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'escalated_to' => ['required', 'string', 'max:100'],
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'is_escalated' => true,
            'escalated_to' => $request->input('escalated_to'),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'content' => "Ticket escalado a: {$request->input('escalated_to')}.",
            'type' => 'escalation',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket escalado.',
        ]);
    }
}
