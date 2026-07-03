<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\UserNotificationSetting;
use App\Mail\WelcomeInternalUserMail;
use App\Models\User;
use App\Notifications\BirthdayNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class UserController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}
    /**
     * GET /api/users/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    /**
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::query()
            ->with('ownedStores')
            ->withCount('ownedStores')
            ->findOrFail($id);

        return response()->json(new UserResource($user));
    }

    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->withCount('ownedStores');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            match ($role) {
                'administrator' => $query->role('administrator'),
                'seller' => $query->role('seller'),
                'customer' => $query->role('customer'),
                'logistics_operator' => $query->role('logistics_operator'),
                default => null,
            };
        }

        if ($status = $request->query('status')) {
            match ($status) {
                'active' => $query->where('is_banned', false),
                'banned' => $query->where('is_banned', true),
                default => null,
            };
        }

        $perPage = min((int) $request->query('per_page', 50), 100);
        $users = $query
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'pagination' => [
                'page' => $users->currentPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'totalPages' => $users->lastPage(),
                'hasMore' => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/users/role/{role}
     */
    public function byRole(Request $request, string $role): JsonResponse
    {
        $query = User::query()->withCount('ownedStores');

        match ($role) {
            'administrator' => $query->role('administrator'),
            'seller' => $query->role('seller'),
            'customer' => $query->role('customer'),
            'logistics_operator' => $query->role('logistics_operator'),
            default => null,
        };

        if ($status = $request->query('status')) {
            match ($status) {
                'active' => $query->where('is_banned', false),
                'banned' => $query->where('is_banned', true),
                default => null,
            };
        }

        $perPage = min((int) $request->query('per_page', 50), 100);
        $users = $query
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'pagination' => [
                'page' => $users->currentPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'totalPages' => $users->lastPage(),
                'hasMore' => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * POST /api/admin/users
     * Creates an internal user (logistics_operator or administrator).
     * Email is marked as verified immediately — no OTP flow.
     */
    public function createInternal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:logistics_operator,administrator',
            'send_welcome' => 'sometimes|boolean',
        ]);

        $username = Str::slug($data['name'], '_');
        $base = $username;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$i++;
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'],
            'nicename' => Str::slug($data['name']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        $this->auditService->record(
            event: 'users.created',
            module: 'users',
            description: 'Usuario interno creado con rol: ' . $data['role'],
            auditable: $user,
            source: AuditService::SOURCE_WEB,
            metadata: ['role' => $data['role'], 'created_by' => auth()->id()],
        );

        if ($data['send_welcome'] ?? true) {
            Mail::to($user->email)->queue(
                new WelcomeInternalUserMail($user->name, $user->email, $data['password'], $data['role'])
            );
        }

        return response()->json(new UserResource($user->fresh()->loadCount('ownedStores')), 201);
    }

    /**
     * PUT /api/users/profile
     * Update authenticated user's profile
     */
    public function updateProfile(UpdateSellerProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
            unset($data['display_name']);
        }

        if (isset($data['avatar']) && str_starts_with($data['avatar'], 'data:image/')) {
            $base64 = $data['avatar'];
            $imageData = base64_decode(explode(',', $base64)[1] ?? '');
            $extension = explode('/', explode(';', $base64)[0])[1] ?? 'png';
            $filename = 'avatars/' . $user->id . '_' . time() . '.' . $extension;
            Storage::disk('public')->put($filename, $imageData);
            $data['avatar'] = '/storage/' . $filename;
        }

        $user->update($data);

        // Si el usuario acaba de poner (o cambiar) su cumpleaños y coincide con hoy,
        // enviar email inmediatamente en lugar de esperar al scheduler diario
        if (isset($data['birthday'])) {
            $birthday = \Carbon\Carbon::parse($data['birthday']);
            $today    = today();
            $cacheKey = "birthday_sent_{$today->format('m-d')}_{$user->id}";

            if ($birthday->month === $today->month && $birthday->day === $today->day) {
                Cache::forget($cacheKey);
                Cache::put($cacheKey, true, $today->endOfDay());
                try {
                    $user->fresh()->notifyNow(new BirthdayNotification);
                } catch (\Exception $e) {
                    \Log::warning('BirthdayNotification broadcast failed (Reverb down?): ' . $e->getMessage());
                }
            }
        }

        return response()->json(new UserResource($user->fresh()));
    }

    /**
     * POST /api/users/avatar
     * Upload avatar as file (multipart)
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $url = '/storage/' . $path;

        $user->update(['avatar' => $url]);

        $this->auditService->record(
            event: 'users.avatar.changed',
            module: 'users',
            description: 'Avatar de usuario actualizado',
            auditable: $user,
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id],
        );

        return response()->json([
            'avatar' => $url,
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * PUT /api/users/profile/password
     * Update authenticated user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actual' => 'required|string|min:1',
            'nueva' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (! Hash::check($data['actual'], $user->password)) {
            return $this->error('La contraseña actual no es correcta.', 422);
        }

        $user->update(['password' => Hash::make($data['nueva'])]);

        $this->auditService->record(
            event: 'auth.password.changed',
            module: 'auth',
            description: 'Contraseña de usuario actualizada',
            auditable: $user,
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id],
        );

        return $this->success(null, 'Contraseña actualizada correctamente.');
    }

    /**
     * GET /api/users/settings
     * Get authenticated user's notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_order' => true,
                'email_promotions' => true,
                'email_newsletter' => false,
                'push_notifications' => true,
            ]
        );

        return $this->success($settings);
    }

    /**
     * PUT /api/users/settings
     * Update authenticated user's notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email_order' => 'sometimes|boolean',
            'email_promotions' => 'sometimes|boolean',
            'email_newsletter' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_order' => true,
                'email_promotions' => true,
                'email_newsletter' => false,
                'push_notifications' => true,
            ]
        );

        $oldValues = $settings->getOriginal();
        $settings->update($data);

        $this->auditService->record(
            event: 'users.settings.changed',
            module: 'users',
            description: 'Configuración de notificaciones actualizada',
            auditable: $user,
            oldValues: $oldValues,
            newValues: $data,
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id],
        );

        return $this->success($settings->fresh());
    }

    /**
     * PUT /api/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'avatar' => 'sometimes|nullable|string',
        ]);

        if (isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
            unset($data['display_name']);
        }

        $original = $user->getOriginal();
        $user->update($data);

        $this->auditService->record(
            event: 'users.updated',
            module: 'users',
            description: 'Usuario actualizado por administrador',
            auditable: $user,
            oldValues: $original,
            newValues: $data,
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id, 'updated_by' => auth()->id()],
        );

        return response()->json(new UserResource($user->fresh()));
    }

    /**
     * PUT /api/users/{id}/role
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:administrator,seller,customer,logistics_operator',
        ]);

        $user = User::findOrFail($id);
        $oldRoles = $user->getRoleNames()->toArray();
        $user->syncRoles([$validated['role']]);

        $this->auditService->record(
            event: 'users.role.changed',
            module: 'users',
            description: 'Rol de usuario actualizado',
            auditable: $user,
            oldValues: ['roles' => $oldRoles],
            newValues: ['roles' => [$validated['role']]],
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id, 'assigned_by' => auth()->id()],
        );

        return response()->json(new UserResource($user->fresh()->loadCount('ownedStores')));
    }

    /**
     * PUT /api/users/{id}/ban
     */
    public function toggleBan(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $wasBanned = $user->is_banned;
        $user->update(['is_banned' => ! $wasBanned]);

        $isBanned = !$wasBanned;
        $this->auditService->record(
            event: $isBanned ? 'users.banned' : 'users.unbanned',
            module: 'users',
            description: $isBanned ? 'Usuario bloqueado' : 'Usuario desbloqueado',
            auditable: $user,
            oldValues: ['is_banned' => $wasBanned],
            newValues: ['is_banned' => $isBanned],
            source: AuditService::SOURCE_WEB,
            metadata: ['user_id' => $user->id, 'banned_by' => auth()->id()],
        );

        return response()->json(new UserResource($user->fresh()->loadCount('ownedStores')));
    }

    /**
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['success' => true]);
    }
}
