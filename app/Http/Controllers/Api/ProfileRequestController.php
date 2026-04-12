<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreProfileRequest;
use App\Models\User;
use App\Notifications\ProfileRequestNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileRequestController extends Controller
{
    private const CRITICAL_FIELDS = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'cuenta_bcp',
        'cci',
        'bank_secondary',
        'rep_legal_nombre',
        'rep_legal_dni',
        'rep_legal_foto',
        'direccion_fiscal',
        'tax_condition',
    ];

    private const NON_CRITICAL_FIELDS = [
        'instagram',
        'facebook',
        'tiktok',
        'whatsapp',
        'youtube',
        'twitter',
        'linkedin',
        'website',
    ];

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $latestRequest = StoreProfileRequest::where('store_id', $store->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'profile_status' => $store->profile_status,
            'profile_updated_at' => $store->profile_updated_at?->toIso8601String(),
            'pending_request' => $latestRequest?->status === StoreProfileRequest::STATUS_PENDING
                ? [
                    'id' => $latestRequest->id,
                    'status' => $latestRequest->status,
                    'created_at' => $latestRequest->created_at->toIso8601String(),
                ]
                : null,
            'rejected_request' => $latestRequest?->status === StoreProfileRequest::STATUS_REJECTED
                ? [
                    'id' => $latestRequest->id,
                    'status' => $latestRequest->status,
                    'admin_notes' => $latestRequest->admin_notes,
                    'attempts' => $latestRequest->attempts,
                    'can_retry' => $latestRequest->canRetry(),
                    'created_at' => $latestRequest->created_at->toIso8601String(),
                ]
                : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $store = Store::where('owner_id', $user->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $pendingRequest = StoreProfileRequest::where('store_id', $store->id)
            ->where('status', StoreProfileRequest::STATUS_PENDING)
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'message' => 'Ya tienes una solicitud pendiente de revisión',
                'request' => [
                    'id' => $pendingRequest->id,
                    'status' => $pendingRequest->status,
                    'created_at' => $pendingRequest->created_at->toIso8601String(),
                ],
            ], 422);
        }

        $data = $request->validate([
            'ruc' => 'sometimes|string|size:11',
            'razon_social' => 'sometimes|string|max:255',
            'nombre_comercial' => 'sometimes|string|max:255',
            'cuenta_bcp' => 'sometimes|string|max:50',
            'cci' => 'sometimes|string|max:50',
            'bank_secondary' => 'sometimes|array',
            'rep_legal_nombre' => 'sometimes|string|max:255',
            'rep_legal_dni' => 'sometimes|string|max:20',
            'rep_legal_foto' => 'sometimes|string|max:500',
            'direccion_fiscal' => 'sometimes|string',
            'tax_condition' => 'sometimes|string|max:100',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'youtube' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        $criticalData = array_intersect_key($data, array_flip(self::CRITICAL_FIELDS));
        $nonCriticalData = array_intersect_key($data, array_flip(self::NON_CRITICAL_FIELDS));

        $hasCriticalChanges = ! empty($criticalData);
        $hasNonCriticalChanges = ! empty($nonCriticalData);

        if ($hasNonCriticalChanges) {
            $store->update($nonCriticalData);
        }

        if ($hasCriticalChanges) {
            $latestRequest = StoreProfileRequest::where('store_id', $store->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestRequest?->status === StoreProfileRequest::STATUS_REJECTED && ! $latestRequest->canRetry()) {
                $cooldown = now()->addHours(StoreProfileRequest::REJECTION_COOLDOWN_HOURS);
                $hoursRemaining = now()->diffInHours($cooldown);

                return response()->json([
                    'message' => "Has alcanzado el límite de intentos. Intenta de nuevo en {$hoursRemaining} horas.",
                    'can_retry' => false,
                    'retry_at' => $cooldown->toIso8601String(),
                ], 422);
            }

            $profileRequest = StoreProfileRequest::create([
                'store_id' => $store->id,
                'data' => $criticalData,
                'status' => StoreProfileRequest::STATUS_PENDING,
                'attempts' => $latestRequest?->status === StoreProfileRequest::STATUS_REJECTED
                    ? $latestRequest->attempts + 1
                    : 1,
            ]);

            $store->update([
                'profile_status' => 'pending_review',
                'profile_updated_at' => now(),
            ]);

            // Notificar a todos los admins en tiempo real
            $profileRequest->load('store.owner');
            User::role('administrator')->each(
                fn (User $admin) => $admin->notify(new ProfileRequestNotification($profileRequest))
            );

            return response()->json([
                'message' => 'Solicitud enviada para revisión',
                'request' => [
                    'id' => $profileRequest->id,
                    'status' => $profileRequest->status,
                    'created_at' => $profileRequest->created_at->toIso8601String(),
                ],
            ], 201);
        }

        if (! $hasCriticalChanges && ! $hasNonCriticalChanges) {
            return response()->json([
                'message' => 'No hay cambios para enviar',
            ], 422);
        }

        return response()->json([
            'message' => 'Datos no críticos actualizados correctamente',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = StoreProfileRequest::query()
            ->with(['store.owner:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate($request->query('per_page', 20));

        return response()->json([
            'data' => $requests->map(fn ($req) => [
                'id' => $req->id,
                'store_id' => $req->store_id,
                'store_name' => $req->store->trade_name,
                'seller_name' => $req->store->owner?->name,
                'seller_email' => $req->store->owner?->email,
                'status' => $req->status,
                'attempts' => $req->attempts,
                'data' => $req->data,
                'created_at' => $req->created_at->toIso8601String(),
                'updated_at' => $req->updated_at->toIso8601String(),
            ]),
            'pagination' => [
                'page' => $requests->currentPage(),
                'perPage' => $requests->perPage(),
                'total' => $requests->total(),
                'totalPages' => $requests->lastPage(),
                'hasMore' => $requests->hasMorePages(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $request = StoreProfileRequest::with(['store.owner:id,name,email', 'reviewer:id,name'])
            ->findOrFail($id);

        return response()->json([
            'id' => $request->id,
            'store_id' => $request->store_id,
            'store_name' => $request->store->trade_name,
            'store_ruc' => $request->store->ruc,
            'seller_name' => $request->store->owner?->name,
            'seller_email' => $request->store->owner?->email,
            'data' => $request->data,
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'attempts' => $request->attempts,
            'reviewed_by' => $request->reviewer?->name,
            'created_at' => $request->created_at->toIso8601String(),
            'updated_at' => $request->updated_at->toIso8601String(),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $profileRequest = StoreProfileRequest::with('store')->findOrFail($id);

        if ($profileRequest->status !== StoreProfileRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Esta solicitud ya ha sido procesada',
            ], 422);
        }

        $notes = $request->input('notes');

        $profileRequest->update([
            'status' => StoreProfileRequest::STATUS_APPROVED,
            'admin_notes' => $notes,
            'reviewed_by' => $request->user()->id,
        ]);

        $profileRequest->store->update([
            'profile_status' => 'approved',
            'profile_updated_at' => now(),
        ]);

        $profileRequest->store->update($profileRequest->data);

        return response()->json([
            'message' => 'Solicitud aprobada correctamente',
            'request' => [
                'id' => $profileRequest->id,
                'status' => $profileRequest->status,
                'reviewed_at' => $profileRequest->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $profileRequest = StoreProfileRequest::with('store')->findOrFail($id);

        if ($profileRequest->status !== StoreProfileRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Esta solicitud ya ha sido procesada',
            ], 422);
        }

        $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $profileRequest->update([
            'status' => StoreProfileRequest::STATUS_REJECTED,
            'admin_notes' => $request->input('notes'),
            'reviewed_by' => $request->user()->id,
            'last_rejected_at' => now(),
        ]);

        $profileRequest->store->update([
            'profile_status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Solicitud rechazada',
            'request' => [
                'id' => $profileRequest->id,
                'status' => $profileRequest->status,
                'admin_notes' => $profileRequest->admin_notes,
                'reviewed_at' => $profileRequest->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $lastEventId = $request->header('Last-Event-ID', 0);

        return response()->stream(function () use ($lastEventId) {
            $lastId = (int) $lastEventId;

            while (true) {
                $newRequests = StoreProfileRequest::query()
                    ->with(['store.owner:id,name,email'])
                    ->where('id', '>', $lastId)
                    ->where('status', StoreProfileRequest::STATUS_PENDING)
                    ->orderBy('id', 'asc')
                    ->limit(5)
                    ->get();

                foreach ($newRequests as $req) {
                    $lastId = $req->id;

                    $data = json_encode([
                        'id' => $req->id,
                        'type' => 'new_profile_request',
                        'store_name' => $req->store->trade_name,
                        'seller_name' => $req->store->owner?->name,
                        'seller_email' => $req->store->owner?->email,
                        'created_at' => $req->created_at->toIso8601String(),
                    ]);

                    echo "id: {$req->id}\n";
                    echo "event: new_profile_request\n";
                    echo "data: {$data}\n\n";
                    ob_flush();
                    flush();
                }

                if ($newRequests->isNotEmpty()) {
                    $pendingCount = StoreProfileRequest::where('status', StoreProfileRequest::STATUS_PENDING)->count();
                    $countData = json_encode(['pending_count' => $pendingCount]);
                    echo "event: pending_count\n";
                    echo "data: {$countData}\n\n";
                    ob_flush();
                    flush();
                }

                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
