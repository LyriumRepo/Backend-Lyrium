<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Store;
use App\Models\User;
use App\Notifications\ContractStatusNotification;
use App\Services\ContractDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\AuditService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

final class ContractController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * GET /api/contracts/terms
     * Términos y Condiciones Generales para Sellers (público, estático).
     * No depende de datos del formulario — se usa para el check 1 (T&C) en
     * el registro de vendedor, antes del check 2 (Cláusulas Monetarias que
     * ya cubre el preview del Acuerdo Comercial pre-llenado).
     */
    public function terms(): JsonResponse
    {
        try {
            $html = View::make('contratos.tyc-seller')->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Throwable $e) {
            Log::error('[ContractController@terms] '.$e->getMessage());

            return response()->json(
                ['message' => 'No se pudieron cargar los Términos y Condiciones.'],
                500
            );
        }
    }

    /**
     * POST /api/contracts/preview
     * Vista previa del acuerdo comercial con datos del seller (público).
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'storeName'       => ['required', 'string', 'max:255'],
            'contacto'        => ['required', 'string', 'max:255'],
            'domicilio_legal' => ['required', 'string', 'max:255'],
            'ruc'             => ['required', 'string', 'max:11'],
            'dni'             => ['required', 'string', 'max:8'],
            'phone'           => ['required', 'string', 'max:20'],
            'email'           => ['required', 'email', 'max:255'],
        ]);

        $year = date('Y');
        $random = strtoupper(Str::random(6));

        $viewData = [
            'nombre_comercial' => $data['storeName'],
            'ruc'              => $data['ruc'],
            'domicilio_legal'  => $data['domicilio_legal'],
            'contacto'         => $data['contacto'],
            'dni'              => $data['dni'],
            'telefono'         => $data['phone'],
            'email'            => $data['email'],
            'fecha'            => now()->format('d/m/Y'),
            'numero_contrato'  => "LYR-{$year}-{$random}",
        ];

        $html = View::make('contratos.preview', $viewData)->render();

        return response()->json([
            'success' => true,
            'html'    => $html,
        ]);
    }

    /**
     * POST /api/internal/rpa/generate-contract-doc
     * Genera el Word del acuerdo comercial usando el template acuerdo_comercial.docx
     * y lo asigna al contrato. Llamado por el RPA tras crear el contrato.
     */
    public function generateDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
        ]);

        $contract = Contract::with('store')->findOrFail($data['contract_id']);

        if (! $contract->store) {
            return response()->json(['error' => 'El contrato no tiene tienda asociada.'], 422);
        }

        $path = app(ContractDocumentService::class)->generateFromContract($contract);

        if (empty($path)) {
            return response()->json([
                'warning' => 'No se encontró la plantilla de acuerdo comercial.',
                'contract_id' => $contract->id,
                'file_path' => null,
            ]);
        }

        $contract->update(['file_path' => $path]);

        return response()->json([
            'success' => true,
            'contract_id' => $contract->id,
            'file_path' => $path,
        ]);
    }

    /**
     * GET /api/contracts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contract::with(['auditTrails', 'store']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('contract_number', 'like', "%{$search}%")
                    ->orWhere('representative', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($modality = $request->query('modality')) {
            $query->where('modality', $modality);
        }

        if ($storeId = $request->query('store_id')) {
            $query->where('store_id', $storeId);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $contracts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // KPIs
        $allContracts = Contract::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'EXPIRED' THEN 1 ELSE 0 END) as expired
        ")->first();

        $response = ContractResource::collection($contracts)->response()->getData(true);
        $response['kpis'] = [
            'total' => (int) $allContracts->total,
            'active' => (int) $allContracts->active,
            'pending' => (int) $allContracts->pending,
            'expired' => (int) $allContracts->expired,
        ];

        return response()->json($response);
    }

    /**
     * GET /api/contracts/{id}
     */
    public function show(string $id): JsonResponse
    {
        $contract = Contract::with(['auditTrails', 'store'])->where('contract_number', $id)->firstOrFail();

        return response()->json(['data' => new ContractResource($contract)]);
    }

    /**
     * POST /api/contracts
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $year = now()->year;
        $lastContract = Contract::where('contract_number', 'like', "CTR-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastContract) {
            $parts = explode('-', $lastContract->contract_number);
            $nextNumber = ((int) end($parts)) + 1;
        }

        $contractNumber = sprintf('CTR-%d-%03d', $year, $nextNumber);

        $contract = Contract::create([
            'contract_number' => $contractNumber,
            'store_id' => $data['storeId'] ?? null,
            'company' => $data['company'],
            'ruc' => $data['ruc'] ?? null,
            'representative' => $data['rep'] ?? null,
            'dni' => $data['dni'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'admin_name' => $data['admin_name'] ?? null,
            'admin_phone' => $data['admin_phone'] ?? null,
            'admin_email' => $data['admin_email'] ?? null,
            'type' => $data['type'] ?? 'General',
            'modality' => $data['modality'],
            'plan' => $data['plan'] ?? null,
            'status' => $data['status'] ?? 'PENDING',
            'start_date' => $data['start'],
            'end_date' => $data['end'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $contract->addAuditEntry(
            'Contrato Borrador Creado',
            $user->name ?? 'Admin'
        );

        User::role('administrator')->each(function (User $admin) use ($contract): void {
            try {
                $admin->notify(new ContractStatusNotification($contract, 'created'));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando creación', [
                    'contract_id' => $contract->id, 'admin_id' => $admin->id, 'error' => $e->getMessage(),
                ]);
            }
        });

        $this->auditService->record(
            event: 'contracts.created',
            module: 'contracts',
            description: 'Contrato creado: ' . $contractNumber,
            auditable: $contract,
            source: AuditService::SOURCE_WEB,
            metadata: ['contract_number' => $contractNumber],
        );

        $contract->load(['auditTrails', 'store']);

        return response()->json(['data' => new ContractResource($contract)], 201);
    }

    /**
     * PUT /api/contracts/{id}
     */
    public function update(UpdateContractRequest $request, string $id): JsonResponse
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();
        $data = $request->validated();
        $user = $request->user();

        $updateData = [];
        if (isset($data['storeId'])) {
            $updateData['store_id'] = $data['storeId'];
        }
        if (isset($data['company'])) {
            $updateData['company'] = $data['company'];
        }
        if (array_key_exists('ruc', $data)) {
            $updateData['ruc'] = $data['ruc'];
        }
        if (array_key_exists('rep', $data)) {
            $updateData['representative'] = $data['rep'];
        }
        if (array_key_exists('dni', $data)) {
            $updateData['dni'] = $data['dni'];
        }
        if (array_key_exists('direccion', $data)) {
            $updateData['direccion'] = $data['direccion'];
        }
        if (array_key_exists('admin_name', $data)) {
            $updateData['admin_name'] = $data['admin_name'];
        }
        if (array_key_exists('admin_phone', $data)) {
            $updateData['admin_phone'] = $data['admin_phone'];
        }
        if (array_key_exists('admin_email', $data)) {
            $updateData['admin_email'] = $data['admin_email'];
        }
        if (isset($data['type'])) {
            $updateData['type'] = $data['type'];
        }
        if (isset($data['modality'])) {
            $updateData['modality'] = $data['modality'];
        }
        if (array_key_exists('plan', $data)) {
            $updateData['plan'] = $data['plan'];
        }
        if (isset($data['start'])) {
            $updateData['start_date'] = $data['start'];
        }
        if (array_key_exists('end', $data)) {
            $updateData['end_date'] = $data['end'];
        }
        if (array_key_exists('notes', $data)) {
            $updateData['notes'] = $data['notes'];
        }

        $contract->update($updateData);

        $contract->addAuditEntry(
            'Contrato Actualizado',
            $user->name ?? 'Admin'
        );

        $this->auditService->record(
            event: 'contracts.updated',
            module: 'contracts',
            description: 'Contrato actualizado: ' . $contract->contract_number,
            auditable: $contract,
            source: AuditService::SOURCE_WEB,
            metadata: ['contract_number' => $contract->contract_number],
        );

        $contract->load(['auditTrails', 'store']);

        return response()->json(['data' => new ContractResource($contract)]);
    }

    /**
     * PUT /api/contracts/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();
        $user = $request->user();

        $data = $request->validate([
            'status' => 'required|string|in:ACTIVE,PENDING,EXPIRED',
        ]);

        $oldStatus = $contract->status;
        $contract->update(['status' => $data['status']]);

        $actionMap = [
            'ACTIVE' => 'Firma Digital Validada — Contrato Activado',
            'EXPIRED' => 'Contrato Expirado/Invalidado',
            'PENDING' => 'Contrato Devuelto a Pendiente',
        ];

        $contract->addAuditEntry(
            $actionMap[$data['status']] ?? "Estado cambiado de {$oldStatus} a {$data['status']}",
            $user->name ?? 'Admin'
        );

        $action = match ($data['status']) {
            'ACTIVE' => 'activated',
            'EXPIRED' => 'expired',
            default => 'updated',
        };

        if ($contract->store && $contract->store->owner) {
            try {
                $contract->store->owner->notify(new ContractStatusNotification($contract, $action));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando al dueño', [
                    'contract_id' => $contract->id, 'action' => $action, 'error' => $e->getMessage(),
                ]);
            }
        }

        User::role('administrator')->each(function (User $admin) use ($contract, $action): void {
            try {
                $admin->notify(new ContractStatusNotification($contract, $action));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando al admin', [
                    'contract_id' => $contract->id, 'action' => $action, 'admin_id' => $admin->id, 'error' => $e->getMessage(),
                ]);
            }
        });

        $auditEvent = match ($data['status']) {
            'ACTIVE' => 'contracts.activated',
            'EXPIRED' => 'contracts.expired',
            'TERMINATED' => 'contracts.terminated',
            default => null,
        };

        if ($auditEvent) {
            $this->auditService->record(
                event: $auditEvent,
                module: 'contracts',
                description: 'Contrato ' . $contract->contract_number . ' ' . match ($data['status']) {
                    'ACTIVE' => 'activado',
                    'EXPIRED' => 'expirado',
                    'TERMINATED' => 'terminado',
                    default => 'cambiado a ' . $data['status'],
                },
                auditable: $contract,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $data['status']],
                source: AuditService::SOURCE_WEB,
                metadata: ['contract_number' => $contract->contract_number],
            );
        }

        $contract->load(['auditTrails', 'store']);

        return response()->json(['data' => new ContractResource($contract)]);
    }

    /**
     * POST /api/contracts/{id}/upload
     */
    public function upload(Request $request, string $id): JsonResponse
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();
        $user = $request->user();

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
        ]);

        $file = $request->file('file');
        $companySlug = str_replace(' ', '_', $contract->company);
        $year = $contract->start_date->year;
        $path = $file->storeAs(
            "contracts/{$companySlug}/{$year}",
            $file->getClientOriginalName(),
            'private'
        );

        $contract->update(['file_path' => $path]);

        $contract->addAuditEntry(
            "Documento Cargado: {$file->getClientOriginalName()}",
            $user->name ?? 'Admin'
        );

        $this->auditService->record(
            event: 'contracts.document.uploaded',
            module: 'contracts',
            description: 'Documento cargado para el contrato ' . $contract->contract_number,
            auditable: $contract,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'contract_number' => $contract->contract_number,
                'file_name' => $file->getClientOriginalName(),
            ],
        );

        $contract->load(['auditTrails', 'store']);

        return response()->json(['data' => new ContractResource($contract)]);
    }

    /**
     * GET /api/contracts/{id}/download
     * Descarga el Word original (file_path).
     */
    public function download(string $id)
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();

        if (! $contract->file_path || ! Storage::disk('local')->exists($contract->file_path)) {
            return response()->json(['error' => 'No hay documento cargado.'], 404);
        }

        return Storage::disk('local')->download(
            $contract->file_path,
            "convenio_{$contract->contract_number}.docx"
        );
    }

    /**
     * GET /api/contracts/{id}/download-signed
     * Admin descarga el documento firmado subido por el vendedor.
     */
    public function downloadSigned(string $id)
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();

        if (! $contract->signed_file_path || ! Storage::disk('local')->exists($contract->signed_file_path)) {
            return response()->json(['error' => 'No hay documento firmado cargado.'], 404);
        }

        $ext = pathinfo($contract->signed_file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $contract->signed_file_path,
            "firmado_{$contract->contract_number}.{$ext}"
        );
    }

    // ── Template del convenio (admin) ────────────────────────────────────────

    /**
     * GET /api/admin/contracts/template/info
     * Informa si existe un template subido y su fecha de subida.
     */
    public function templateInfo(): JsonResponse
    {
        $exists = Storage::disk('local')->exists(ContractDocumentService::TEMPLATE_PATH);
        $uploadedAt = null;

        if ($exists) {
            $uploadedAt = date('Y-m-d H:i:s', Storage::disk('local')->lastModified(ContractDocumentService::TEMPLATE_PATH));
        }

        return response()->json([
            'has_template' => $exists,
            'uploaded_at' => $uploadedAt,
            'placeholders' => [
                '${contract_number}', '${company}', '${ruc}',
                '${rep_nombre}', '${rep_dni}', '${direccion}',
                '${email}', '${plan}', '${commission}',
                '${fecha_inicio}', '${ciudad}', '${year}',
            ],
        ]);
    }

    /**
     * POST /api/admin/contracts/template
     * Sube un nuevo template Word (.docx).
     */
    public function uploadTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:docx|max:5120',
        ]);

        $file = $request->file('file');

        // Guardar sobrescribiendo el template anterior
        $dir = dirname(ContractDocumentService::TEMPLATE_PATH);
        $name = basename(ContractDocumentService::TEMPLATE_PATH);
        $file->storeAs($dir, $name, 'local');

        return response()->json([
            'message' => 'Template subido correctamente',
            'uploaded_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * GET /api/admin/contracts/template/download
     * Descarga el template Word actual.
     */
    public function downloadTemplate()
    {
        if (! Storage::disk('local')->exists(ContractDocumentService::TEMPLATE_PATH)) {
            return response()->json(['error' => 'No hay template subido'], 404);
        }

        return Storage::disk('local')->download(
            ContractDocumentService::TEMPLATE_PATH,
            'convenio_template.docx'
        );
    }

    /**
     * DELETE /api/contracts/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $contract = Contract::where('contract_number', $id)->firstOrFail();
        $user = request()->user();

        $contract->addAuditEntry(
            'Contrato Eliminado',
            $user->name ?? 'Admin'
        );

        User::role('administrator')->each(function (User $admin) use ($contract): void {
            try {
                $admin->notify(new ContractStatusNotification($contract, 'deleted'));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando eliminación', [
                    'contract_id' => $contract->id, 'admin_id' => $admin->id, 'error' => $e->getMessage(),
                ]);
            }
        });

        $contractData = $contract->toArray();
        $contract->delete();

        $this->auditService->record(
            event: 'contracts.deleted',
            module: 'contracts',
            description: 'Contrato eliminado: ' . $contract->contract_number,
            auditable: $contract,
            source: AuditService::SOURCE_WEB,
            oldValues: $contractData,
            metadata: ['contract_number' => $contract->contract_number],
        );

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/contracts/me
     * Vendedor: ver su contrato pendiente de firma
     */
    public function myContract(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $contract = $store->contracts()->latest()->first();

        if (! $contract) {
            return response()->json(['message' => 'No tienes un contrato generado aún'], 404);
        }

        return response()->json(['data' => new ContractResource($contract->load('auditTrails'))]);
    }

    /**
     * GET /api/contracts/me/download
     * Vendedor: descargar el documento Word de su contrato para firmarlo
     */
    public function downloadMyContract(Request $request)
    {
        $store = Store::where('owner_id', $request->user()->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $contract = $store->contracts()->whereIn('status', ['PENDING'])->latest()->first();

        if (! $contract || ! $contract->file_path) {
            return response()->json(['message' => 'No hay documento disponible para descargar'], 404);
        }

        if (! Storage::disk('local')->exists($contract->file_path)) {
            return response()->json(['message' => 'El archivo no se encontró en el servidor'], 404);
        }

        return Storage::disk('local')->download(
            $contract->file_path,
            "convenio_{$contract->contract_number}.docx"
        );
    }

    /**
     * POST /api/contracts/me/upload-signed
     * Vendedor: subir el contrato firmado digitalmente
     */
    public function uploadSigned(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->first();

        if (! $store) {
            return response()->json(['message' => 'No tienes una tienda registrada'], 404);
        }

        $contract = $store->contracts()->where('status', 'PENDING')->latest()->first();

        if (! $contract) {
            return response()->json(['message' => 'No tienes un contrato pendiente de firma'], 422);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $companySlug = preg_replace('/[^a-zA-Z0-9_]/', '_', $contract->company ?? 'empresa');
        $year = now()->year;
        $path = $file->storeAs(
            "contracts/{$companySlug}/{$year}/signed",
            "firmado_{$contract->contract_number}.".$file->getClientOriginalExtension(),
            'local'
        );

        // Guardar en signed_file_path para no sobreescribir el Word original (file_path)
        $contract->update(['signed_file_path' => $path]);

        $contract->addAuditEntry(
            'Documento firmado subido por el vendedor — pendiente de verificación por admin',
            $request->user()->name ?? 'Vendedor'
        );

        $this->auditService->record(
            event: 'contracts.signed',
            module: 'contracts',
            description: 'Documento firmado subido para el contrato ' . $contract->contract_number,
            auditable: $contract,
            source: AuditService::SOURCE_WEB,
            metadata: ['contract_number' => $contract->contract_number],
        );

        return response()->json(['data' => new ContractResource($contract->fresh()->load('auditTrails'))]);
    }

    /**
     * POST /api/contracts/{id}/renew
     * Renueva un contrato creando una nueva versión (V+1).
     * Accesible por admin y por el dueño de la tienda.
     */
    public function renew(Request $request, string $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);
        $user = $request->user();

        // Seller must own the contract's store
        if ($user->hasRole('seller')) {
            $store = Store::where('owner_id', $user->id)->first();
            if (! $store || $contract->store_id !== $store->id) {
                return response()->json(['error' => 'No tienes permiso para renovar este contrato.'], 403);
            }
        }

        $parentId = $contract->parent_contract_id ?? $contract->id;
        $newVersion = $contract->version + 1;

        $year = now()->year;
        $lastContract = Contract::where('contract_number', 'like', "CTR-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastContract) {
            $parts = explode('-', $lastContract->contract_number);
            $nextNumber = ((int) end($parts)) + 1;
        }

        $contractNumber = sprintf('CTR-%d-%03d', $year, $nextNumber);

        $duration = $contract->start_date && $contract->end_date
            ? $contract->start_date->diffInDays($contract->end_date)
            : 365;

        $renewed = Contract::create([
            'parent_contract_id' => $parentId,
            'contract_number' => $contractNumber,
            'store_id' => $contract->store_id,
            'company' => $contract->company,
            'ruc' => $contract->ruc,
            'representative' => $contract->representative,
            'dni' => $contract->dni,
            'direccion' => $contract->direccion,
            'admin_name' => $contract->admin_name,
            'admin_phone' => $contract->admin_phone,
            'admin_email' => $contract->admin_email,
            'type' => $contract->type,
            'modality' => $contract->modality,
            'plan' => $contract->plan,
            'status' => 'ACTIVE',
            'version' => $newVersion,
            'start_date' => now(),
            'end_date' => now()->addDays($duration),
            'notes' => $contract->notes,
        ]);

        $renewed->addAuditEntry(
            "Contrato Renovado a V{$newVersion} (desde V{$contract->version})",
            $user->name ?? 'Admin'
        );

        $contract->addAuditEntry(
            "Renovado a contrato #{$renewed->contract_number} (V{$newVersion})",
            $user->name ?? 'Admin'
        );

        $renewed->load(['auditTrails', 'store']);

        if ($renewed->store && $renewed->store->owner) {
            try {
                $renewed->store->owner->notify(new ContractStatusNotification($renewed, 'renewed'));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando renovación al dueño', [
                    'contract_id' => $renewed->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        User::role('administrator')->each(function (User $admin) use ($renewed): void {
            try {
                $admin->notify(new ContractStatusNotification($renewed, 'renewed'));
            } catch (\Throwable $e) {
                Log::error('[Contract] Error notificando renovación al admin', [
                    'contract_id' => $renewed->id, 'admin_id' => $admin->id, 'error' => $e->getMessage(),
                ]);
            }
        });

        return response()->json(['data' => new ContractResource($renewed)], 201);
    }
}
