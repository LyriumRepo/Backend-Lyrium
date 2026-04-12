<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Mail\WelcomeInternalUserMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class UserController extends Controller
{
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
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8',
            'role'         => 'required|in:logistics_operator,administrator',
            'send_welcome' => 'sometimes|boolean',
        ]);

        $username = Str::slug($data['name'], '_');
        $base = $username;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$i++;
        }

        $user = User::create([
            'name'              => $data['name'],
            'username'          => $username,
            'email'             => $data['email'],
            'nicename'          => Str::slug($data['name']),
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        if ($data['send_welcome'] ?? true) {
            Mail::to($user->email)->queue(
                new WelcomeInternalUserMail($user->name, $user->email, $data['password'], $data['role'])
            );
        }

        return response()->json(new UserResource($user->fresh()->loadCount('ownedStores')), 201);
    }

    /**
     * PUT /api/users/profile
     * Seller profile update with new fields
     */
    public function updateProfile(UpdateSellerProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update($data);

        return response()->json(new UserResource($user->fresh()));
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

        $user->update($data);

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
        $user->syncRoles([$validated['role']]);

        return response()->json(new UserResource($user->fresh()->loadCount('ownedStores')));
    }

    /**
     * PUT /api/users/{id}/ban
     */
    public function toggleBan(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_banned' => ! $user->is_banned]);

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
