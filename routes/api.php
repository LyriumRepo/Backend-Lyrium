
<?php

use App\Http\Controllers\Api\AdminFinanceController;
use App\Http\Controllers\Api\AdminTicketController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BenefitController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ChatBotController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ServiceHoldController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\FinanceAnalyticsController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\IzipayPaymentController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlanRequestController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileRequestController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SystemConfigController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminSellerController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CulqiController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\OperationalRoleController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReviewModerationController;
use App\Http\Controllers\Api\StoreReviewController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IzipayController;


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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/validate', [AuthController::class, 'validateToken']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
    });
});

/*
|--------------------------------------------------------------------------
| Público (sin auth)
|--------------------------------------------------------------------------
*/
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/mega-menu', [CategoryController::class, 'megaMenu']);
Route::get('/categories/slug/{slug}', [CategoryController::class, 'getBySlug']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/services/{id}/slots', [ServiceController::class, 'availableSlots']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);
Route::get('/stores/slug/{slug}', [StoreController::class, 'showBySlug']);
Route::get('/stores/{slug}/reviews', [StoreReviewController::class, 'index']);

// Plans público
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{id}', [PlanController::class, 'show']);

// System Config público
Route::get('/config/colors', [SystemConfigController::class, 'colors']);
Route::get('/config/public', [SystemConfigController::class, 'publicConfigs']);

// SSE Events
Route::get('/events', [EventsController::class, 'stream']);

// Webhook Izipay (público)
Route::post('/webhooks/izipay/plan',  [PlanRequestController::class, 'webhookIzipay']);
Route::post('/webhooks/izipay/order', [IzipayController::class, 'webhook']);

Route::post('/webhooks/culqi', [CulqiController::class, 'webhook']);

// ranking
Route::prefix('rankings')->group(function () {
    Route::get('/products', [RankingController::class, 'products']);
    Route::get('/stores',   [RankingController::class, 'stores']);
});

// ChatBot — Asistente Virtual Lyrium (público)
Route::post('/chatbot/ask', [ChatBotController::class, 'ask']);

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

Route::get('/google/callback',    [GoogleCalendarController::class, 'callback']);

// Cart (público — soporta invitados vía X-Session-ID)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'addItem']);
Route::put('/cart/items/{productId}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{productId}', [CartController::class, 'removeItem']);
Route::delete('/cart/clear', [CartController::class, 'clear']);

// Service holds (temporary cart slots for service bookings — public, by cart_token)
Route::get('/cart/service-holds', [ServiceHoldController::class, 'index']);
Route::post('/cart/add-service', [ServiceHoldController::class, 'store']);
Route::patch('/cart/service-holds/{id}', [ServiceHoldController::class, 'update']);
Route::delete('/cart/service-holds/{id}', [ServiceHoldController::class, 'destroy']);

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
    });


    Route::post('/reviews/{id}/report', [ReviewController::class, 'report']);

    Route::post('/operations/request-2fa', [OperationsController::class, 'request2FA']);
    Route::post('/operations/verify-2fa', [OperationsController::class, 'verify2FA']);

    Route::get('/operations/stats', [OperationsController::class, 'stats']);

    Route::apiResource('suppliers', SupplierController::class);

    // ── Gestión de Gastos / Recibos ───────────────────────────────────────
    Route::get('/expenses/stats', [ExpenseController::class, 'stats']);  // Antes del resource
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
    Route::put('/users/profile/password', [UserController::class, 'updatePassword']);
    Route::get('/users/settings', [UserController::class, 'getSettings']);
    Route::put('/users/settings', [UserController::class, 'updateSettings']);
    Route::post('/users/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    // Stores - propia del usuario autenticado
    Route::get('/stores/me', [StoreController::class, 'me']);
    Route::put('/stores/me/visual', [StoreController::class, 'updateVisual']);
    Route::post('/stores/me/media/logo', [StoreController::class, 'uploadLogo']);
    Route::post('/stores/me/media/banner', [StoreController::class, 'uploadBanner']);
    Route::post('/stores/me/media/gallery', [StoreController::class, 'uploadGallery']);
    Route::delete('/stores/me/media/gallery/{index}', [StoreController::class, 'deleteGalleryImage']);

    //Reseñas Tiendas
    Route::post('/stores/{slug}/reviews',   [StoreReviewController::class, 'store']);
    Route::put('/stores/reviews/{id}',      [StoreReviewController::class, 'update']);
    Route::delete('/stores/reviews/{id}',   [StoreReviewController::class, 'destroy']);

    // Profile Requests - Seller
    Route::get('/stores/me/profile-request', [ProfileRequestController::class, 'me']);
    Route::post('/stores/me/profile-request', [ProfileRequestController::class, 'store']);

    // Contratos - Vendedor (ver, descargar y subir firmado)
    Route::get('/contracts/me', [ContractController::class, 'myContract']);
    Route::get('/contracts/me/download', [ContractController::class, 'downloadMyContract']);
    Route::post('/contracts/me/upload-signed', [ContractController::class, 'uploadSigned']);

    // Plan Requests - Seller
    Route::post('/plans/requests', [PlanRequestController::class, 'store']);
    Route::get('/stores/me/plan-request', [PlanRequestController::class, 'me']);

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

    // Loyalty
    Route::get('/loyalty/account', [LoyaltyController::class, 'account']);
    Route::get('/loyalty/status', [LoyaltyController::class, 'status']);
    Route::get('/loyalty/rewards', [LoyaltyController::class, 'rewards']);
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeem']);
    Route::get('/loyalty/redemptions', [LoyaltyController::class, 'redemptions']);
    Route::get('/loyalty/transactions', [LoyaltyController::class, 'transactions']);
    Route::post('/loyalty/validate-code', [LoyaltyController::class, 'validateCode']);
    Route::post('/loyalty/use-code', [LoyaltyController::class, 'useCode']);

    // Devices (FCM push notification tokens)
    Route::post('/devices', [DeviceController::class, 'register'])->middleware('throttle:10,1');
    Route::delete('/devices', [DeviceController::class, 'unregister'])->middleware('throttle:10,1');

    // Payment Methods (tokenize must be before {id} to avoid route conflict)
    Route::post('/payment-methods/tokenize', [PaymentMethodController::class, 'tokenize']);
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

    // Addresses
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{id}', [AddressController::class, 'show']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::put('/addresses/{id}/default', [AddressController::class, 'setDefault']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/active-count', [OrderController::class, 'activeCount']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/confirm', [OrderController::class, 'confirm']);
    Route::get('/orders/{id}/receipt', [OrderController::class, 'downloadReceipt']);
    Route::get('/orders/{id}/payment-confirmation', [OrderController::class, 'downloadPaymentConfirmation']);
    Route::post('/orders/{id}/request-receipt', [OrderController::class, 'requestReceipt']);
    Route::put('/orders/{orderId}/items/{itemId}/confirm', [OrderController::class, 'confirmItem']);
    Route::put('/orders/{orderId}/items/{itemId}/status', [OrderController::class, 'updateItemStatus']);
    Route::post('/orders/{id}/resend-notification', [OrderController::class, 'resendNotification']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('/orders/{orderId}/invoice', [InvoiceController::class, 'generate']);
    Route::get('/customer/invoices', [InvoiceController::class, 'customerInvoices']);
    Route::get('/customer/payment-confirmations', [OrderController::class, 'customerPaymentConfirmations']);

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

    // Chat con Vendedores
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/stores', [ConversationController::class, 'stores']);
    Route::get('/conversations/customers', [ConversationController::class, 'customers']);
    Route::get('/conversations/my-stores', [ConversationController::class, 'myStores']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages/attachments', [ConversationController::class, 'sendMessageWithAttachment']);
    Route::put('/conversations/{id}/read', [ConversationController::class, 'markRead']);
    Route::put('/conversations/{id}/archive', [ConversationController::class, 'archive']);

    // Chat file attachments
    Route::get('/chat/attachments/{id}/download', [ConversationController::class, 'downloadAttachment']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::get('/wishlist/check', [WishlistController::class, 'check']);
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);

    // Services (Citas/Servicios)
    //Route::get('/services', [ServiceController::class, 'index']); - Se quito por error en la carga de menu
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    
    Route::get('/seller/services', [ServiceController::class, 'sellerServices']);
    Route::get('/seller/services/{id}', [ServiceController::class, 'showMyService']);

    Route::post('/services/{serviceId}/book', [ServiceController::class, 'book']);
    Route::get('/bookings/my', [ServiceController::class, 'myBookings']);
    Route::put('/bookings/{id}/cancel', [ServiceController::class, 'cancelBooking']);
    Route::post('/bookings/{id}/reschedule', [ServiceController::class, 'reschedule']);

    // Izipay Payment
    Route::prefix('payments/izipay')->group(function () {
        Route::post('/init/{order}', [IzipayPaymentController::class, 'init']);
        Route::post('/confirm/{order}', [IzipayPaymentController::class, 'confirm']);
        Route::post('/charge-with-token', [IzipayPaymentController::class, 'chargeWithToken']);
    });

    // Google Calendar OAuth
    Route::prefix('google')->group(function () {
        Route::get('/status',      [GoogleCalendarController::class, 'status']);
        Route::get('/auth-url',    [GoogleCalendarController::class, 'authUrl']);
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

        //Gestion Puntuacion
        Route::get('/admin/reviews',              [ReviewModerationController::class, 'index']);
        Route::get('/admin/reviews/reported',     [ReviewModerationController::class, 'reported']);
        Route::put('/admin/reviews/{id}/moderate', [ReviewModerationController::class, 'moderate']);
        Route::delete('/admin/reviews/{id}',      [ReviewModerationController::class, 'destroy']);

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

        // Admin Plans CRUD
        Route::get('/admin/plans', [PlanController::class, 'adminIndex']);
        Route::post('/admin/plans', [PlanController::class, 'store']);
        Route::get('/admin/plans/{plan}', [PlanController::class, 'adminShow']);
        Route::put('/admin/plans/{plan}', [PlanController::class, 'update']);
        Route::delete('/admin/plans/{plan}', [PlanController::class, 'destroy']);
        Route::post('/admin/plans/{plan}/status', [PlanController::class, 'setActive']);
        Route::post('/admin/plans/{plan}/icon', [PlanController::class, 'updateIcon']);

        // Admin Plan Colors
        Route::get('/admin/plan-colors', [PlanController::class, 'getColors']);
        Route::put('/admin/plan-colors', [PlanController::class, 'saveColors']);
        Route::delete('/admin/plan-colors', [PlanController::class, 'resetColors']);
        Route::post('/admin/config/colors', [SystemConfigController::class, 'updateColors']);

        // Admin Vendedores con info de suscripción
        Route::get('/admin/vendedores', [AdminSellerController::class, 'vendedores']);
        Route::get('/admin/vendedores/{id}/historial', [AdminSellerController::class, 'vendedorHistorial']);

        // Admin Historial de pagos de planes
        Route::get('/admin/plan-payments', [PlanRequestController::class, 'paymentHistory']);

        // System Config - Admin
        Route::get('/admin/config', [SystemConfigController::class, 'index']);
        Route::get('/admin/config/{key}', [SystemConfigController::class, 'show']);
        Route::post('/admin/config', [SystemConfigController::class, 'store']);
        Route::put('/admin/config/{key}', [SystemConfigController::class, 'update']);
        Route::delete('/admin/config/{key}', [SystemConfigController::class, 'destroy']);

        // Admin Finance Analytics
        Route::get('/admin/finance', [AdminFinanceController::class, 'index']);

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
        Route::post('/stores/{id}/rep-photo',[StoreController::class, 'uploadRepLegalPhoto']);

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
        Route::middleware('auth:sanctum', 'contract.active')->group(function () {
            // Products CRUD
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);
            Route::put('/products/{id}/stock', [ProductController::class, 'updateStock']);

            // Products Media (upload image)
            Route::post('/products/{id}/media', [MediaController::class, 'uploadProductMedia']);

            // Services (vendedor)
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{id}', [ServiceController::class, 'update']);
            Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
            Route::get('/bookings/seller', [ServiceController::class, 'sellerBookings']);
            Route::put('/bookings/{id}/confirm', [ServiceController::class, 'confirmBooking']);
            Route::put('/bookings/{id}/no-show', [ServiceController::class, 'markNoShow']);
            Route::put('/bookings/{id}/notes', [ServiceController::class, 'addNotes']);
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

        // Analytics financieros
        Route::get('/seller/finance/analytics', [FinanceAnalyticsController::class, 'analytics']);

        // Facturación / Comprobantes (vendedor - SOLO CONSULTA)
        // La emisión es automática post-pago vía webhook Izipay
        Route::prefix('seller/invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'sellerInvoices']);
            Route::get('/kpis', [InvoiceController::class, 'sellerKpis']);
            Route::get('/series', [InvoiceController::class, 'sellerSeries']);
            Route::get('/orders/{orderId}', [InvoiceController::class, 'sellerOrderData']);
        });
    });
});
