<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use App\Notifications\NewSellerRegistrationNotification;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])
            ->orWhere('username', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Credenciales inválidas.',
            ], 401);
        }

        // Verificar email
        if (! $user->hasVerifiedEmail()) {
            $this->otpService->generate($user);

            return response()->json([
                'success' => false,
                'error' => 'Debes verificar tu correo electrónico. Te enviamos un nuevo código.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data): JsonResponse {
            $username = Str::slug($data['storeName'], '_');
            $baseUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername.'_'.$counter++;
            }

            $user = User::create([
                'name' => $data['storeName'],
                'username' => $username,
                'email' => $data['email'],
                'nicename' => Str::slug($data['storeName']),
                'phone' => $data['phone'],
                'document_type' => 'RUC',
                'document_number' => $data['ruc'],
                'password' => $data['password'],
            ]);

            $user->assignRole('seller');

            Store::create([
                'owner_id' => $user->id,
                'ruc' => $data['ruc'],
                'trade_name' => $data['storeName'],
                'corporate_email' => $data['email'],
                'slug' => Str::slug($data['storeName']),
                'status' => 'pending',
            ]);

            $this->otpService->generate($user);

            $store->load('owner');
            $admins = User::role('administrator')->get();
            if ($admins->isNotEmpty()) {
                NotificationFacade::send($admins, new NewSellerRegistrationNotification($user, $store));
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso. Revisa tu correo para el código de verificación.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 201);
        });
    }

    /**
     * POST /api/auth/register-customer
     */
    public function registerCustomer(RegisterCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $username = Str::slug($data['name'], '_');
        $baseUsername = $username;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter++;
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'],
            'nicename' => Str::slug($data['name']),
            'phone' => $data['phone'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'password' => $data['password'],
        ]);

        $user->assignRole('customer');

        $this->otpService->generate($user);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada. Revisa tu correo para el código de verificación.',
            'requires_verification' => true,
            'email' => $user->email,
        ], 201);
    }

    /**
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado.',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'El email ya está verificado.',
                'already_verified' => true,
                'user_type' => $user->hasRole('seller') ? 'seller' : 'customer',
            ]);
        }

        $result = $this->otpService->verify($user, $request->validated('code'));

        if ($result['success']) {
            $userType = $user->hasRole('seller') ? 'seller' : 'customer';
            $result['user_type'] = $userType;
            $result['message'] = $userType === 'seller'
                ? 'Tu correo fue verificado. Te notificaremos cuando tu tienda sea aprobada.'
                : 'Tu correo fue verificado. Ya puedes iniciar sesión.';
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/auth/resend-otp
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado.',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'El email ya está verificado.',
            ]);
        }

        if (! $this->otpService->canResend($user)) {
            return response()->json([
                'success' => false,
                'error' => 'Espera 60 segundos antes de solicitar otro código.',
            ], 429);
        }

        $this->otpService->generate($user);

        return response()->json([
            'success' => true,
            'message' => 'Código reenviado a tu correo.',
        ]);
    }

    /**
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado',
            ], 404);

        }
        $this->otpService->generate($user);

        return response()->json([
            'success' => true,
            'message' => 'Codigo enviado al correo',
        ]);
    }

    /**
     * POST /api/auth/resetPassword
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado',
            ], 404);
        }
        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'error' => 'Token invalido',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            return response()->json([
                'success' => false,
                'error' => 'Token expirado',
            ], 422);

        }

        if (! Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'error' => 'Token invalido',
            ], 422);
        }

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
        ]);

    }

    /**
     * POST /api/auth/verifyotpReset
     */
    public function verifyOtpReset(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado',
            ], 404);
        }

        $verify = $this->otpService->verifyOnly($user, $request->code);

        if (! $verify['success']) {
            return response()->json($verify, 422);
        }

        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'reset_token' => $plainToken,
        ]);
    }

    /**
     * POST /api/auth/google
     */
    public function googleAuth(GoogleAuthRequest $request): JsonResponse
    {
        $googleData = $this->googleAuthService->verifyToken($request->validated('credential'));

        if (! $googleData) {
            return response()->json([
                'success' => false,
                'error' => 'Token de Google inválido.',
            ], 401);
        }

        $result = $this->googleAuthService->findOrCreateUser($googleData);
        $user = $result['user'];

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => new UserResource($user),
            'is_new_user' => $result['is_new_user'],
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/auth/validate
     */
    public function validateToken(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    /**
     * POST /api/auth/refresh
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
