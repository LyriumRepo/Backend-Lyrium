
<?php

use App\Http\Controllers\Api\Admin\CommissionTierController;
use App\Http\Controllers\Api\AdminSellerController;
use App\Http\Controllers\Api\AdminTicketController;
use App\Http\Controllers\Api\AdminVendedorController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BenefitController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartServiceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CulqiController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\IzipayBookingController;
use App\Http\Controllers\Api\IzipayController;
use App\Http\Controllers\Api\LiriosController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NubefactController;
use App\Http\Controllers\Api\OperationalRoleController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlanRequestController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileRequestController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReviewModerationController;
use App\Http\Controllers\Api\SellerApplicationController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StoreReviewController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SystemConfigController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth (público)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-customer', [AuthController::class, 'registerCustomer']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');
    Route::post('/google', [AuthController::class, 'googleAuth']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp-reset', [AuthController::class, 'verifyOtpReset']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/register-seller-fallback', [AuthController::class, 'registerSellerFallback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/validate', [AuthController::class, 'validateToken']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
    });
});

// Endpoint interno usado por el servicio RPA para disparar el OTP
Route::post('/internal/trigger-otp', [AuthController::class, 'triggerOtp']);

Route::prefix('cart')->group(function () {
    // Obtener el carrito (R19/R20)
    Route::get('/', [CartController::class, 'index']);

    // Agregar producto al carrito (R19)
    Route::post('items', [CartController::class, 'addItem']);

    // Actualizar cantidad (R20)
    Route::put('items/{productId}', [CartController::class, 'updateItem']);

    // Eliminar un producto (R20)
    Route::delete('items/{productId}', [CartController::class, 'removeItem']);

    // Vaciar todo el carrito
    Route::delete('clear', [CartController::class, 'clear']);

    // ── Service Slot Holds ──────────────────────────────────────────────
    Route::post('add-service', [CartServiceController::class, 'addServiceHold']);
    Route::get('service-holds', [CartServiceController::class, 'verifyHolds']);
    Route::delete('service-holds/{holdId}', [CartServiceController::class, 'removeServiceHold']);
    Route::patch('service-holds/{holdId}', [CartServiceController::class, 'updateServiceHold']);
});

/*
|--------------------------------------------------------------------------
| Público (sin auth)
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()]));

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/mega-menu', [CategoryController::class, 'megaMenu']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/slug/{slug}', [ServiceController::class, 'showBySlug']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/services/{id}/slots', [ServiceController::class, 'availableSlots']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);
Route::get('/stores/{slug}/reviews', [StoreReviewController::class, 'index']);

Route::prefix('categories')->group(function () {
    Route::get('/service-roots', [CategoryController::class, 'serviceRoots']);
    Route::get('/service-tree', [CategoryController::class, 'serviceTree']);
    Route::get('/{id}/children', [CategoryController::class, 'children']);
});

// Plans público
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{id}', [PlanController::class, 'show']);

// System Config público
Route::get('/config/colors', [SystemConfigController::class, 'colors']);
Route::get('/config/public', [SystemConfigController::class, 'publicConfigs']);

// SSE Events
Route::get('/events', [EventsController::class, 'stream']);

// Webhook Izipay (público)

Route::post('/webhooks/izipay/plan', [PlanRequestController::class, 'webhookIzipay']);
Route::post('/webhooks/izipay/order', [IzipayController::class, 'webhook']);
Route::post('/webhooks/izipay/booking', [IzipayBookingController::class, 'webhook']);

Route::post('/webhooks/culqi', [CulqiController::class, 'webhook']);

// ranking
Route::prefix('rankings')->group(function () {
    Route::get('/products', [RankingController::class, 'products']);
    Route::get('/stores', [RankingController::class, 'stores']);
    Route::get('/services', [RankingController::class, 'services']);
});

// Shipping público
Route::get('/shipping/methods', [ShippingController::class, 'methods']);
Route::get('/shipping/zones', [ShippingController::class, 'zones']);
Route::get('/shipping/calculate', [ShippingController::class, 'calculate']);

// Returns público
Route::get('/orders/{orderId}/returns', [ReturnController::class, 'orderReturns']);

// Disputes público
Route::get('/orders/{orderId}/disputes', [DisputeController::class, 'orderDisputes']);

/*
|--------------------------------------------------------------------------
| Home (público)
|--------------------------------------------------------------------------
*/
Route::get('/home/heroes', [HomeController::class, 'heroes']);
Route::get('/home/banners-pub', [HomeController::class, 'banners']);
Route::get('/home/section/{slug}', [HomeController::class, 'categorySection']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/benefits', [BenefitController::class, 'index']);
Route::post('/newsletter', [NewsletterController::class, 'subscribe']);

/*
|--------------------------------------------------------------------------
| Blog (público)
|--------------------------------------------------------------------------
*/
Route::get('/blog/categories', [BlogController::class, 'categories']);
Route::get('/blog/posts', [BlogController::class, 'posts']);
Route::get('/blog/posts/recent', [BlogController::class, 'recent']);
Route::get('/blog/posts/featured', [BlogController::class, 'featured']);
Route::get('/blog/posts/{slug}', [BlogController::class, 'show']);
Route::get('/blog/comments', [BlogController::class, 'comments']);
Route::post('/blog/comments', [BlogController::class, 'storeComment']);
Route::get('/blog/podcasts', [BlogController::class, 'podcasts']);
Route::get('/blog/videos', [BlogController::class, 'videos']);
Route::get('/blog/shorts', [BlogController::class, 'shorts']);

/*
|--------------------------------------------------------------------------
| Foro (público)
|--------------------------------------------------------------------------
*/
Route::get('/foro/categorias', [ForumController::class, 'categories']);
Route::get('/foro/temas', [ForumController::class, 'topics']);
Route::get('/foro/temas/{id}', [ForumController::class, 'topic']);
Route::post('/foro/temas', [ForumController::class, 'createTopic']);
Route::get('/foro/temas/{id}/respuestas', [ForumController::class, 'posts']);
Route::post('/foro/respuestas', [ForumController::class, 'createPost']);
Route::post('/foro/votos', [ForumController::class, 'vote']);
Route::get('/foro/estadisticas', [ForumController::class, 'stats']);

Route::get('/google/callback', [GoogleCalendarController::class, 'callback']);

// Invoice PDF (público para permitir apertura en nueva pestaña)
Route::get('/invoices/{id}/pdf', [\App\Http\Controllers\Api\InvoicePdfController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Autenticado (cualquier rol)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('payments/culqi')->group(function () {
        Route::post('/charge', [CulqiController::class, 'charge']);
        Route::get('/status/{orderId}', [CulqiController::class, 'status']);
    });

    Route::prefix('payments/izipay')->group(function () {
        Route::post('/create-session', [IzipayController::class, 'createSession']);
        Route::get('/status/{orderId}', [IzipayController::class, 'status']);

        Route::post('/create-booking-session', [IzipayBookingController::class, 'createSession']);
        Route::put('/booking-data/{transactionId}', [IzipayBookingController::class, 'updateBookingData']);
        Route::post('/confirm-booking', [IzipayBookingController::class, 'confirmBooking']);
        Route::get('/booking-status/{transactionId}', [IzipayBookingController::class, 'status']);
    });

    Route::post('/reviews/{id}/report', [ReviewController::class, 'report']);

    Route::post('/operations/request-2fa', [OperationsController::class, 'request2FA']);
    Route::post('/operations/verify-2fa', [OperationsController::class, 'verify2FA']);

    Route::get('/operations/stats', [OperationsController::class, 'stats']);

    Route::apiResource('suppliers', SupplierController::class);

    // ── Gestión de Gastos / Recibos ───────────────────────────────────────
    Route::get('/expenses/stats', [ExpenseController::class, 'stats']);
    Route::post('/expenses/upload', [ExpenseController::class, 'upload']);
    Route::post('/expenses/scan', [ExpenseController::class, 'scan']);
    Route::post('/expenses/scan/batch-store', [ExpenseController::class, 'scanBatchStore']);
    Route::apiResource('expenses', ExpenseController::class);

    // ── Roles Operativos ──────────────────────────────────────────────────
    // Sólo administrators pueden crear/modificar roles
    Route::prefix('operational-roles')->name('operational-roles.')->group(function () {
        Route::get('/', [OperationalRoleController::class, 'index'])->name('index');
        Route::get('/{id}', [OperationalRoleController::class, 'show'])->name('show');

        Route::middleware('role:administrator')->group(function () {
            Route::post('/', [OperationalRoleController::class, 'store'])->name('store');
            Route::put('/{id}', [OperationalRoleController::class, 'update'])->name('update');
            Route::put('/{id}/toggle', [OperationalRoleController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}', [OperationalRoleController::class, 'destroy'])->name('destroy');

            // Gestión de usuarios en roles
            Route::post('/{id}/users', [OperationalRoleController::class, 'assignUser'])->name('users.assign');
            Route::delete('/{id}/users/{userId}', [OperationalRoleController::class, 'removeUser'])->name('users.remove');
        });
    });

    // ── Log de Auditoría Técnica (RF-13) ──────────────────────────────────
    // Sólo lectura — sólo administrators
    Route::middleware('role:administrator')->prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/modules', [AuditLogController::class, 'modules'])->name('modules');
        Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
    });

    // Users
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/profile/password', [UserController::class, 'updatePassword']);

    // Stores - propia del usuario autenticado
    Route::get('/stores/me', [StoreController::class, 'me']);
    Route::put('/stores/me/visual', [StoreController::class, 'updateVisual']);
    Route::post('/stores/me/media/logo', [StoreController::class, 'uploadLogo']);
    Route::post('/stores/me/media/banner', [StoreController::class, 'uploadBanner']);
    Route::post('/stores/me/media/gallery', [StoreController::class, 'uploadGallery']);
    Route::delete('/stores/me/media/gallery/{index}', [StoreController::class, 'deleteGalleryImage']);

    // Reseñas Tiendas
    Route::post('/stores/{slug}/reviews', [StoreReviewController::class, 'store']);
    Route::put('/stores/reviews/{id}', [StoreReviewController::class, 'update']);
    Route::delete('/stores/reviews/{id}', [StoreReviewController::class, 'destroy']);

    // Profile Requests - Seller
    Route::get('/stores/me/profile-request', [ProfileRequestController::class, 'me']);
    Route::post('/stores/me/profile-request', [ProfileRequestController::class, 'store']);

    // Contratos - Vendedor (ver, descargar, subir firmado y renovar)
    Route::get('/contracts/me', [ContractController::class, 'myContract']);
    Route::get('/contracts/me/download', [ContractController::class, 'downloadMyContract']);
    Route::post('/contracts/me/upload-signed', [ContractController::class, 'uploadSigned']);
    Route::post('/contracts/{id}/renew', [ContractController::class, 'renew']);

    // Plan Requests - Seller
    Route::post('/plans/requests', [PlanRequestController::class, 'store']);
    Route::get('/stores/me/plan-request', [PlanRequestController::class, 'me']);
    Route::post('/plans/izipay/init', [PlanRequestController::class, 'createIzipaySession']);

    // Tickets — Mesa de Ayuda (customer, seller, cualquier usuario autenticado)
    Route::prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{id}', [TicketController::class, 'show']);
        Route::get('/{id}/messages', [TicketController::class, 'getMessages']);
        Route::post('/{id}/messages', [TicketController::class, 'sendMessage']);
        Route::put('/{id}/close', [TicketController::class, 'close']);
        Route::post('/{id}/survey', [TicketController::class, 'submitSurvey']);
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Lirios
    Route::get('/lirios/balance', [LiriosController::class, 'balance']);
    Route::get('/lirios/checkout-eligibility', [LiriosController::class, 'checkoutEligibility']);
    Route::get('/lirios/transactions', [LiriosController::class, 'transactions']);
    Route::post('/lirios/accrue', [LiriosController::class, 'accrue']);

    // Admin Lirios
    Route::middleware('role:administrator')->prefix('lirios')->group(function () {
        Route::get('/admin/accounts', [LiriosController::class, 'adminAccounts']);
        Route::put('/admin/accounts/{userId}', [LiriosController::class, 'adminUpdateBalance']);
    });

    // Loyalty
    Route::get('/loyalty/account', [LoyaltyController::class, 'account']);
    Route::get('/loyalty/status', [LoyaltyController::class, 'status']);
    Route::get('/loyalty/rewards', [LoyaltyController::class, 'rewards']);
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeem']);
    Route::get('/loyalty/redemptions', [LoyaltyController::class, 'redemptions']);
    Route::get('/loyalty/transactions', [LoyaltyController::class, 'transactions']);
    Route::post('/loyalty/validate-code', [LoyaltyController::class, 'validateCode']);
    Route::post('/loyalty/use-code', [LoyaltyController::class, 'useCode']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/confirm', [OrderController::class, 'confirm']);
    Route::put('/orders/{orderId}/items/{itemId}/confirm', [OrderController::class, 'confirmItem']);
    Route::put('/orders/{orderId}/items/{itemId}/status', [OrderController::class, 'updateItemStatus']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/orders/{orderId}/invoice', [InvoiceController::class, 'generate']);

    // Nubefact
    Route::post('/nubefact/emitir', [NubefactController::class, 'emitir']);
    Route::get('/nubefact/comprobantes', [NubefactController::class, 'listar']);
    Route::get('/nubefact/comprobantes/{id}', [NubefactController::class, 'mostrar']);
    Route::get('/nubefact/kpis', [NubefactController::class, 'kpis']);

    // Coupons
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::get('/coupons/validate', [CouponController::class, 'validate']);
    Route::get('/coupons/{id}', [CouponController::class, 'show']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::put('/coupons/{id}', [CouponController::class, 'update']);
    Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

    // Returns (cliente)
    Route::get('/returns/my', [ReturnController::class, 'myReturns']);
    Route::post('/returns', [ReturnController::class, 'store']);
    Route::get('/returns/{id}', [ReturnController::class, 'show']);
    Route::put('/returns/{id}/cancel', [ReturnController::class, 'cancel']);

    // Disputes (cliente)
    Route::get('/disputes/my', [DisputeController::class, 'userDisputes']);
    Route::post('/disputes', [DisputeController::class, 'store']);
    Route::get('/disputes/{id}', [DisputeController::class, 'show']);
    Route::post('/disputes/{id}/messages', [DisputeController::class, 'addMessage']);

    // Services (Citas/Servicios)
    Route::get('/seler/services', [ServiceController::class, 'sellerServices']);
    Route::get('/seler/services/{id}', [ServiceController::class, 'showMyService']);

    Route::post('/services/{serviceId}/book', [ServiceController::class, 'book']);
    Route::get('/services/{serviceId}/pending-review', [ServiceController::class, 'pendingReview']);
    Route::get('/bookings/my', [ServiceController::class, 'myBookings']);
    Route::put('/bookings/{id}/cancel', [ServiceController::class, 'cancelBooking']);
    Route::post('/bookings/{id}/reschedule', [ServiceController::class, 'reschedule']);
    Route::post('/bookings/{id}/rate', [ServiceController::class, 'rateBooking']);
    Route::put('/bookings/{id}/confirm-completion', [ServiceController::class, 'confirmCompletion']);

    // Google Calendar OAuth
    Route::prefix('google')->group(function () {
        Route::get('/status', [GoogleCalendarController::class, 'status']);
        Route::get('/auth-url', [GoogleCalendarController::class, 'authUrl']);
        Route::delete('/disconnect', [GoogleCalendarController::class, 'disconnect']);
    });
    /*
    |----------------------------------------------------------------------
    | Admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:administrator')->group(function () {
        // Users management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/role/{role}', [UserController::class, 'byRole']);
        Route::post('/users/internal', [UserController::class, 'createInternal']);
        Route::put('/users/{id}/role', [UserController::class, 'assignRole']);
        Route::put('/users/{id}/ban', [UserController::class, 'toggleBan']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Gestion Puntuacion
        Route::get('/admin/reviews', [ReviewModerationController::class, 'index']);
        Route::get('/admin/reviews/reported', [ReviewModerationController::class, 'reported']);
        Route::put('/admin/reviews/{id}/moderate', [ReviewModerationController::class, 'moderate']);
        Route::delete('/admin/reviews/{id}', [ReviewModerationController::class, 'destroy']);

        Route::prefix('admin/sellers')->group(function () {

            // GET  /api/admin/sellers/stats   → cards del dashboard
            Route::get('/stats', [AdminSellerController::class, 'stats']);

            // GET  /api/admin/sellers         → lista paginada con filtros
            //   ?search=  ?status=active|pending|banned|alert  ?per_page=20
            Route::get('/', [AdminSellerController::class, 'index']);

            // GET  /api/admin/sellers/{id}    → detalle completo de un vendedor
            Route::get('/{id}', [AdminSellerController::class, 'show']);

            // PUT  /api/admin/sellers/{id}/ban          → toggle ban del usuario
            Route::put('/{id}/ban', [AdminSellerController::class, 'toggleBan']);

            // PUT  /api/admin/sellers/{storeId}/store-status → cambiar estado tienda
            Route::put('/{storeId}/store-status', [AdminSellerController::class, 'updateStoreStatus']);
        });

        Route::prefix('admin/seller-applications')->group(function () {
            Route::get('/', [SellerApplicationController::class, 'index']);
            Route::get('/{id}', [SellerApplicationController::class, 'show']);
            Route::put('/{id}/estado', [SellerApplicationController::class, 'updateEstado']);
        });

        // Stores management
        Route::get('/stores', [StoreController::class, 'index']);
        Route::get('/stores/{id}', [StoreController::class, 'show']);
        Route::put('/stores/{id}/status', [StoreController::class, 'updateStatus']);

        // Categories CRUD
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::post('/categories/{id}/image', [CategoryController::class, 'uploadImage']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Products: aprobar/rechazar
        Route::put('/products/{id}/status', [ProductController::class, 'updateStatus']);

        // Services: aprobar/rechazar
        Route::get('/admin/services', [ServiceController::class, 'adminIndex']);
        Route::put('/services/{id}/status', [ServiceController::class, 'updateStatus']);

        // Products: Admin - obtener todos los productos incluyendo pendientes
        Route::get('/admin/products', [ProductController::class, 'adminIndex']);

        // Profile Requests - Admin
        Route::get('/admin/profile-requests', [ProfileRequestController::class, 'index']);
        Route::get('/admin/profile-requests/{id}', [ProfileRequestController::class, 'show']);
        Route::put('/admin/profile-requests/{id}/approve', [ProfileRequestController::class, 'approve']);
        Route::put('/admin/profile-requests/{id}/reject', [ProfileRequestController::class, 'reject']);
        Route::get('/admin/profile-requests/stream', [ProfileRequestController::class, 'stream']);

        // Plan Requests - Admin
        Route::get('/admin/plan-requests', [PlanRequestController::class, 'index']);
        Route::get('/admin/plan-requests/{id}', [PlanRequestController::class, 'show']);
        Route::put('/admin/plan-requests/{id}/approve', [PlanRequestController::class, 'approve']);
        Route::put('/admin/plan-requests/{id}/reject', [PlanRequestController::class, 'reject']);
        Route::get('/admin/plan-requests/stream', [PlanRequestController::class, 'stream']);

        // Planes - Admin (CRUD) — bind por slug en lugar de id
        Route::get('/admin/plans', [PlanController::class, 'adminIndex']);
        Route::post('/admin/plans', [PlanController::class, 'store']);
        Route::get('/admin/plans/{plan:slug}', [PlanController::class, 'adminShow']);
        Route::put('/admin/plans/{plan:slug}', [PlanController::class, 'update']);
        Route::delete('/admin/plans/{plan:slug}', [PlanController::class, 'destroy']);
        Route::put('/admin/plans/{plan:slug}/toggle-active', [PlanController::class, 'toggleActive']);
        Route::put('/admin/plans/{plan:slug}/icon', [PlanController::class, 'updateIcon']);

        // Colores botones planes - Admin
        Route::get('/admin/plan-colors', [PlanController::class, 'getColors']);
        Route::put('/admin/plan-colors', [PlanController::class, 'saveColors']);
        Route::delete('/admin/plan-colors', [PlanController::class, 'resetColors']);

        // Vendedores - Admin
        Route::get('/admin/vendedores', [AdminVendedorController::class, 'index']);
        Route::get('/admin/vendedores/stats', [AdminVendedorController::class, 'stats']);
        Route::get('/admin/vendedores/{id}', [AdminVendedorController::class, 'show']);

        // Pagos planes - Admin
        Route::get('/admin/pagos', [PagoController::class, 'adminHistory']);
        Route::get('/admin/pagos/vendedor/{storeId}', [PagoController::class, 'adminVendedorPagos']);

        // System Config - Admin
        Route::get('/admin/config', [SystemConfigController::class, 'index']);
        Route::get('/admin/config/{key}', [SystemConfigController::class, 'show']);
        Route::post('/admin/config', [SystemConfigController::class, 'store']);
        Route::put('/admin/config/{key}', [SystemConfigController::class, 'update']);
        Route::delete('/admin/config/{key}', [SystemConfigController::class, 'destroy']);

        // Suppliers CRUD
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

        // Contracts CRUD
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{id}', [ContractController::class, 'show']);
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::put('/contracts/{id}', [ContractController::class, 'update']);
        Route::put('/contracts/{id}/status', [ContractController::class, 'updateStatus']);
        Route::post('/contracts/{id}/upload', [ContractController::class, 'upload']);
        Route::get('/contracts/{id}/download', [ContractController::class, 'download']);
        Route::get('/contracts/{id}/download-signed', [ContractController::class, 'downloadSigned']);
        Route::delete('/contracts/{id}', [ContractController::class, 'destroy']);

        // Contract Template (admin gestiona el Word plantilla)
        Route::get('/admin/contracts/template/info', [ContractController::class, 'templateInfo']);
        Route::post('/admin/contracts/template', [ContractController::class, 'uploadTemplate']);
        Route::get('/admin/contracts/template/download', [ContractController::class, 'downloadTemplate']);

        // Tickets — Admin (Mesa de Ayuda)
        Route::prefix('admin/tickets')->group(function () {
            Route::get('/', [AdminTicketController::class, 'index']);
            Route::get('/{id}', [AdminTicketController::class, 'show']);
            Route::get('/{id}/messages', [AdminTicketController::class, 'getMessages']);
            Route::post('/{id}/messages', [AdminTicketController::class, 'sendMessage']);
            Route::put('/{id}/status', [AdminTicketController::class, 'updateStatus']);
            Route::put('/{id}/assign', [AdminTicketController::class, 'assign']);
            Route::put('/{id}/priority', [AdminTicketController::class, 'updatePriority']);
            Route::put('/{id}/escalate', [AdminTicketController::class, 'escalate']);
        });

        // Disputes — Admin
        Route::prefix('admin/disputes')->group(function () {
            Route::get('/', [DisputeController::class, 'index']);
            Route::get('/{id}', [DisputeController::class, 'show']);
            Route::put('/{id}/assign', [DisputeController::class, 'assign']);
            Route::put('/{id}/status', [DisputeController::class, 'updateStatus']);
            Route::put('/{id}/resolve', [DisputeController::class, 'resolve']);
            Route::put('/{id}/close', [DisputeController::class, 'close']);
            Route::put('/{id}/cancel', [DisputeController::class, 'cancel']);
        });

        // Pagos — Admin
        Route::prefix('admin/payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::put('/{id}/process', [PaymentController::class, 'process']);
            Route::put('/{id}/cancel', [PaymentController::class, 'cancel']);
            Route::put('/{id}/reschedule', [PaymentController::class, 'reschedule']);
            Route::get('/schedules', [PaymentController::class, 'schedules']);
            Route::put('/schedules/{id}', [PaymentController::class, 'updateSchedule']);
            Route::get('/is-payment-day', [PaymentController::class, 'isPaymentDayToday']);
            Route::get('/next-payment-date', [PaymentController::class, 'nextPaymentDate']);
        });

        // Transacciones Izipay — Admin
        Route::prefix('admin/transactions')->group(function () {
            Route::get('/', [TransactionController::class, 'index']);
            Route::get('/stats', [TransactionController::class, 'stats']);
            Route::get('/{id}', [TransactionController::class, 'show']);
        });

        // Finanzas — Admin (dashboard con KPIs)
        Route::get('/admin/finance', [\App\Http\Controllers\Api\AdminFinanceController::class, 'index']);

        // Commission Tiers — Admin
        Route::get('/admin/commission-tiers', [CommissionTierController::class, 'index']);
        Route::post('/admin/commission-tiers', [CommissionTierController::class, 'store']);
        Route::put('/admin/commission-tiers/{id}', [CommissionTierController::class, 'update']);
        Route::delete('/admin/commission-tiers/{id}', [CommissionTierController::class, 'destroy']);

        // Glossary Entries — Admin
        Route::prefix('glossary-entries')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'destroy']);

            // Pending terms
            Route::get('/pending/terms', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'pendingTerms']);
            Route::post('/pending/{id}/approve', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'approvePending']);
            Route::post('/pending/{id}/dismiss', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'dismissPending']);
            Route::post('/pending/dismiss-all', [\App\Http\Controllers\Api\GlossaryEntryController::class, 'dismissAllPending']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Seller
    |----------------------------------------------------------------------
    */

    Route::middleware('role:seller,administrator')->group(function () {
        // Store propio (sin requerir contrato — el vendedor necesita ver/editar su tienda antes de firmar)
        Route::post('/stores', [StoreController::class, 'store']);
        Route::put('/stores/{id}', [StoreController::class, 'update']);
        Route::get('/stores/{id}/branches', [StoreController::class, 'branches']);
        Route::put('/stores/{id}/branches', [StoreController::class, 'updateBranches']);

        Route::get('/stores/me/specialists', [\App\Http\Controllers\Api\SpecialistController::class, 'index']);
        Route::get('/stores/me/specialists/{specialist}', [\App\Http\Controllers\Api\SpecialistController::class, 'show']);
        Route::post('/stores/me/specialists', [\App\Http\Controllers\Api\SpecialistController::class, 'store']);
        Route::put('/stores/me/specialists/{id}', [\App\Http\Controllers\Api\SpecialistController::class, 'update']);
        Route::delete('/stores/me/specialists/{id}', [\App\Http\Controllers\Api\SpecialistController::class, 'destroy']);

        // Store policies (PDF uploads)
        Route::post('/stores/{id}/media/policy', [MediaController::class, 'uploadStorePolicy']);
        Route::delete('/stores/{id}/media/policy/{type}', [MediaController::class, 'deleteStorePolicy']);

        // Store logo and banner
        Route::post('/stores/{id}/media/logo', [MediaController::class, 'uploadStoreLogo']);
        Route::post('/stores/{id}/media/banner', [MediaController::class, 'uploadStoreBanner']);
        Route::post('/stores/{id}/media/banner2', [MediaController::class, 'uploadStoreBanner2']);

        // Store gallery
        Route::post('/stores/{id}/media/gallery', [MediaController::class, 'uploadStoreGallery']);
        Route::delete('/stores/{id}/media/gallery/{mediaId}', [MediaController::class, 'deleteStoreGallery']);

        // Rutas que requieren contrato activo para operar
        Route::middleware('contract.active')->group(function () {
            // Products CRUD
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);
            Route::put('/products/{id}/stock', [ProductController::class, 'updateStock']);

            // Products Media (upload image)
            Route::post('/products/{id}/media', [MediaController::class, 'uploadProductMedia']);
            Route::get('/products/{id}/media', [MediaController::class, 'getProductMedia']);
            Route::delete('/products/{id}/media/{mediaId}', [MediaController::class, 'deleteProductMedia']);
            Route::put('/products/{id}/media/reorder', [MediaController::class, 'reorderProductMedia']);

            // Services (vendedor)
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{id}', [ServiceController::class, 'update']);
            Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
            Route::post('/services/{id}/media', [MediaController::class, 'uploadServiceMedia']);
            Route::get('/bookings/seller', [ServiceController::class, 'sellerBookings']);
            Route::put('/bookings/{id}/confirm', [ServiceController::class, 'confirmBooking']);
            Route::put('/bookings/{id}/no-show', [ServiceController::class, 'markNoShow']);
            Route::put('/bookings/{id}/notes', [ServiceController::class, 'addNotes']);
            Route::put('/bookings/{id}/complete', [ServiceController::class, 'completeBooking']);
            Route::put('/bookings/{id}/on-the-way', [ServiceController::class, 'markOnTheWay']);
        });

        // Shipping (vendedor)
        Route::get('/store/shipping/methods', [ShippingController::class, 'storeMethods']);
        Route::post('/store/shipping/configure', [ShippingController::class, 'configureStore']);
        Route::get('/shipments', [ShippingController::class, 'sellerShipments']);
        Route::put('/shipments/{id}/tracking', [ShippingController::class, 'updateTracking']);
        Route::put('/shipments/{id}/ship', [ShippingController::class, 'markShipped']);
        Route::put('/shipments/{id}/deliver', [ShippingController::class, 'markDelivered']);
        Route::put('/shipments/{id}/status', [ShippingController::class, 'updateStatus']);
        Route::post('/shipments/{id}/event', [ShippingController::class, 'addEvent']);

        // Returns (vendedor)
        Route::get('/returns', [ReturnController::class, 'sellerReturns']);
        Route::put('/returns/{id}/approve', [ReturnController::class, 'approve']);
        Route::put('/returns/{id}/reject', [ReturnController::class, 'reject']);
        Route::put('/returns/{id}/received', [ReturnController::class, 'markReceived']);
        Route::put('/returns/{id}/refund', [ReturnController::class, 'refund']);
        Route::put('/returns/{id}/tracking', [ReturnController::class, 'updateTracking']);

        // Subscriptions (vendedor)
        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
        Route::put('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
        Route::put('/subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);

        // Disputes (vendedor)
        Route::get('/disputes', [DisputeController::class, 'storeDisputes']);
        Route::put('/disputes/{id}/status', [DisputeController::class, 'updateStatus']);

        // Pagos (vendedor)
        Route::get('/payments', [PaymentController::class, 'sellerPayments']);
        Route::get('/payments/pending', [PaymentController::class, 'sellerPendingPayments']);
        Route::get('/payments/completed', [PaymentController::class, 'sellerCompletedPayments']);
        Route::get('/payments/pending-total', [PaymentController::class, 'sellerPendingTotal']);

        Route::post('/url-metadata', [\App\Http\Controllers\Api\UrlMetadataController::class, 'preview']);

        // ── BioBlog ──────────────────────────────────────────────────
        Route::prefix('blog')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\BlogDashboardController::class, 'index']);

            Route::get('/articles', [\App\Http\Controllers\Api\BlogArticleController::class, 'index']);
            Route::get('/articles/{id}', [\App\Http\Controllers\Api\BlogArticleController::class, 'show']);
            Route::post('/articles', [\App\Http\Controllers\Api\BlogArticleController::class, 'store']);
            Route::put('/articles/{id}', [\App\Http\Controllers\Api\BlogArticleController::class, 'update']);
            Route::delete('/articles/{id}', [\App\Http\Controllers\Api\BlogArticleController::class, 'destroy']);

            Route::get('/podcasts/{id}', [\App\Http\Controllers\Api\BlogPodcastController::class, 'show']);
            Route::post('/podcasts', [\App\Http\Controllers\Api\BlogPodcastController::class, 'store']);
            Route::put('/podcasts/{id}', [\App\Http\Controllers\Api\BlogPodcastController::class, 'update']);
            Route::delete('/podcasts/{id}', [\App\Http\Controllers\Api\BlogPodcastController::class, 'destroy']);

            Route::get('/videos/{id}', [\App\Http\Controllers\Api\BlogVideoController::class, 'show']);
            Route::post('/videos', [\App\Http\Controllers\Api\BlogVideoController::class, 'store']);
            Route::put('/videos/{id}', [\App\Http\Controllers\Api\BlogVideoController::class, 'update']);
            Route::delete('/videos/{id}', [\App\Http\Controllers\Api\BlogVideoController::class, 'destroy']);

            Route::post('/shorts', [\App\Http\Controllers\Api\BlogShortController::class, 'store']);
            Route::get('/shorts/{id}', [\App\Http\Controllers\Api\BlogShortController::class, 'show']);
            Route::put('/shorts/{id}', [\App\Http\Controllers\Api\BlogShortController::class, 'update']);
            Route::delete('/shorts/{id}', [\App\Http\Controllers\Api\BlogShortController::class, 'destroy']);

            Route::get('/media', [\App\Http\Controllers\Api\BlogMediaController::class, 'index']);
            Route::post('/media/upload', [\App\Http\Controllers\Api\BlogMediaController::class, 'upload']);
            Route::delete('/media/{id}', [\App\Http\Controllers\Api\BlogMediaController::class, 'destroy']);
        });

        // ── BioForo ──────────────────────────────────────────────────
        Route::prefix('forum')->group(function () {
            Route::get('/topics', [\App\Http\Controllers\Api\ForumTopicController::class, 'index']);
            Route::get('/topics/{id}', [\App\Http\Controllers\Api\ForumTopicController::class, 'show']);
            Route::post('/topics', [\App\Http\Controllers\Api\ForumTopicController::class, 'store']);
            Route::put('/topics/{id}', [\App\Http\Controllers\Api\ForumTopicController::class, 'update']);
            Route::delete('/topics/{id}', [\App\Http\Controllers\Api\ForumTopicController::class, 'destroy']);

            Route::get('/topics/{topicId}/replies', [\App\Http\Controllers\Api\ForumTopicController::class, 'replies']);
            Route::post('/topics/{topicId}/replies', [\App\Http\Controllers\Api\ForumTopicController::class, 'storeReply']);
            Route::post('/topics/{topicId}/replies/{postId}/hide', [\App\Http\Controllers\Api\ForumTopicController::class, 'hideReply']);
            Route::delete('/topics/{topicId}/replies/{postId}', [\App\Http\Controllers\Api\ForumTopicController::class, 'deleteReply']);
        });

        // ── Content Reports ──────────────────────────────────────────
        Route::post('/content-reports', [\App\Http\Controllers\Api\ContentReportController::class, 'store']);
        Route::middleware('role:administrator')->group(function () {
            Route::get('/content-reports', [\App\Http\Controllers\Api\ContentReportController::class, 'index']);
            Route::post('/content-reports/{id}/resolve', [\App\Http\Controllers\Api\ContentReportController::class, 'resolve']);
            Route::post('/content-reports/{id}/dismiss', [\App\Http\Controllers\Api\ContentReportController::class, 'dismiss']);
        });
    });
});

// ── Público: Tiendas ────────────────────────────────────────────────────
Route::get('/stores', [StoreController::class, 'publicIndex']);
Route::get('/store/{slug}', [StoreController::class, 'publicShow']);
