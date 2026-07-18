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
use App\Models\AdminSession;
use App\Models\LoginAttempt;
use App\Models\SellerApplication;
use App\Models\User;
use App\Notifications\NewSellerRegistrationNotification;
use App\Notifications\RpaDiagnosticoNotification;
use App\Services\AuditService;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

final class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly GoogleAuthService $googleAuthService,
        private readonly AuditService $auditService,
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
            LoginAttempt::create([
                'email' => $credentials['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            $this->auditService->record(
                event: 'auth.login.failed',
                module: 'auth',
                description: 'Intento de inicio de sesión fallido — credenciales inválidas',
                success: false,
                source: AuditService::SOURCE_WEB,
                metadata: ['email' => $credentials['email'], 'reason' => 'invalid_credentials'],
            );

            return response()->json([
                'success' => false,
                'error' => 'Credenciales inválidas.',
            ], 401);
        }

        if ($user->is_banned) {
            LoginAttempt::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            $this->auditService->record(
                event: 'auth.login.failed',
                module: 'auth',
                description: 'Intento de inicio de sesión de usuario suspendido',
                success: false,
                source: AuditService::SOURCE_WEB,
                metadata: ['email' => $user->email, 'user_id' => $user->id, 'reason' => 'account_suspended'],
            );

            return response()->json([
                'success' => false,
                'error' => 'Tu cuenta ha sido suspendida. Contacta al soporte para más información.',
                'code' => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        // Verificar email
        if (! $user->hasVerifiedEmail()) {
            $this->otpService->generate($user);

            LoginAttempt::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
            ]);

            $this->auditService->record(
                event: 'auth.login.failed',
                module: 'auth',
                description: 'Intento de inicio de sesión de email no verificado',
                success: false,
                source: AuditService::SOURCE_WEB,
                metadata: ['email' => $user->email, 'user_id' => $user->id, 'reason' => 'email_not_verified'],
            );

            return response()->json([
                'success' => false,
                'error' => 'Debes verificar tu correo electrónico. Te enviamos un nuevo código.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        $this->auditService->record(
            event: 'auth.login.success',
            module: 'auth',
            description: 'Inicio de sesión exitoso',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: ['email' => $user->email, 'user_id' => $user->id],
        );

        AdminSession::where('user_id', $user->id)->delete();
        AdminSession::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now()->timestamp,
            'payload' => '[]',
        ]);

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

            $store = Store::create([
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

            $this->auditService->record(
                event: 'auth.register',
                module: 'auth',
                description: 'Registro de vendedor',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: ['email' => $user->email],
            );

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso. Revisa tu correo para el código de verificación.',
                'requires_verification' => true,
                'email' => $user->email,
                'user_id' => $user->id,
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

        $this->auditService->record(
            event: 'auth.register.customer',
            module: 'auth',
            description: 'Registro de cliente',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: ['email' => $user->email],
        );

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
            $this->auditService->record(
                event: 'auth.email.verified',
                module: 'auth',
                description: 'Verificación de correo',
                success: true,
                source: AuditService::SOURCE_WEB,
                metadata: ['email' => $user->email],
            );

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

        $this->auditService->record(
            event: 'auth.password.reset.requested',
            module: 'auth',
            description: 'Solicitud de restablecimiento de contraseña',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: ['email' => $user->email],
        );

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

        $this->auditService->record(
            event: 'auth.password.reset.completed',
            module: 'auth',
            description: 'Contraseña restablecida',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: ['email' => $user->email],
        );

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

        $this->auditService->record(
            event: 'auth.oauth.login',
            module: 'auth',
            description: 'Inicio de sesión con Google',
            success: true,
            source: AuditService::SOURCE_WEB,
            metadata: [
                'email' => $user->email,
                'is_new_user' => $result['is_new_user'],
            ],
        );

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => new UserResource($user),
            'is_new_user' => $result['is_new_user'],
        ]);
    }

    /**
     * POST /api/internal/trigger-otp
     */
    public function triggerOtp(Request $request): JsonResponse
    {
        $secret = config('app.internal_rpa_secret', 'CAMBIAR_EN_ENV');

        if ($request->input('secret') !== $secret) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->otpService->generate($user);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/auth/send-diagnostico
     * Envía el diagnóstico de una solicitud al correo del vendedor.
     */
    public function sendDiagnostico(Request $request): JsonResponse
    {
        $data = $request->validate([
            'application_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
        ]);

        try {
            $application = SellerApplication::where('id', $data['application_id'])
                ->where('correo', $data['email'])
                ->first();

            if (! $application) {
                return response()->json(['error' => 'Solicitud no encontrada.'], 404);
            }

            $user = User::where('email', $data['email'])->first();

            if ($user) {
                $user->notify(new RpaDiagnosticoNotification($application));
            }

            return response()->json([
                'success' => true,
                'message' => 'Diagnóstico enviado a tu correo.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar diagnóstico RPA', [
                'application_id' => $data['application_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'No se pudo enviar el diagnóstico.'], 500);
        }
    }

    /**
     * POST /api/auth/register-seller-fallback
     */
    public function registerSellerFallback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'storeName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'ruc' => 'required|string|size:11',
            'dni' => 'required|string|size:8',
            'phone' => 'required|string|max:20',
            'categoria' => 'nullable|string|max:100',
        ]);

        $username = Str::slug($validated['storeName'], '_');
        $baseUsername = $username;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter++;
        }

        $user = User::create([
            'name' => $validated['storeName'],
            'username' => $username,
            'email' => $validated['email'],
            'nicename' => Str::slug($validated['storeName']),
            'phone' => $validated['phone'],
            'document_type' => 'RUC',
            'document_number' => $validated['ruc'],
            'password' => $validated['password'],
        ]);

        $user->assignRole('seller');

        $application = SellerApplication::create([
            'user_id' => $user->id,
            'store_id' => null,
            'nombre_comercial' => $validated['storeName'],
            'ruc' => $validated['ruc'],
            'dni' => $validated['dni'],
            'telefono' => $validated['phone'],
            'correo' => $validated['email'],
            'categoria' => $validated['categoria'] ?? null,
            'razon_social' => null,
            'sunat_data' => null,
            'tipo_evidencia' => 'sin_evaluacion',
            'evidencia_valor' => null,
            'etapa' => 1,
            'score' => 0,
            'riesgo' => 'medio',
            'estado' => 'REVISION',
            'diagnostico' => [
                'Solicitud registrada sin evaluaci\u00f3n autom\u00e1tica.',
                'El servicio RPA no estaba disponible al momento del registro.',
                'Esta solicitud requiere revisi\u00f3n manual por parte del administrador.',
            ],
        ]);

        if (! $user->hasVerifiedEmail()) {
            $this->otpService->generate($user);
        }

        return response()->json([
            'success' => true,
            'fallback' => true,
            'estado' => 'REVISION',
            'score' => 0,
            'riesgo' => 'medio',
            'etapa' => 1,
            'diagnostico' => [
                'Tu solicitud fue registrada pero no pudo ser evaluada autom\u00e1ticamente.',
                'Nuestro equipo la revisar\u00e1 manualmente y te notificaremos por correo.',
            ],
            'application_id' => $application->id,
            'store_id' => null,
            'email' => $user->email,
        ], 201);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        // Marcar sesión como inactiva inmediatamente
        AdminSession::where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes(15)->timestamp)
            ->update(['last_activity' => now()->subMinutes(16)->timestamp]);

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
