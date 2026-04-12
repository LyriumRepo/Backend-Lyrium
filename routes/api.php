
<?php

use App\Http\Controllers\Api\AdminTicketController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BenefitController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
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
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{id}', [ReviewController::class, 'show']);

// Plans público
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{id}', [PlanController::class, 'show']);

// System Config público
Route::get('/config/colors', [SystemConfigController::class, 'colors']);
Route::get('/config/public', [SystemConfigController::class, 'publicConfigs']);

// SSE Events
Route::get('/events', [EventsController::class, 'stream']);

// Webhook Izipay (público)
Route::post('/webhooks/izipay', [PlanRequestController::class, 'webhookIzipay']);

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


/*
|--------------------------------------------------------------------------
| Autenticado (cualquier rol)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Users
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    // Stores - propia del usuario autenticado
    Route::get('/stores/me', [StoreController::class, 'me']);
    Route::put('/stores/me/visual', [StoreController::class, 'updateVisual']);
    Route::post('/stores/me/media/logo', [StoreController::class, 'uploadLogo']);
    Route::post('/stores/me/media/banner', [StoreController::class, 'uploadBanner']);
    Route::post('/stores/me/media/gallery', [StoreController::class, 'uploadGallery']);
    Route::delete('/stores/me/media/gallery/{index}', [StoreController::class, 'deleteGalleryImage']);

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
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::get('/services/{id}/slots', [ServiceController::class, 'availableSlots']);
    Route::post('/services/{serviceId}/book', [ServiceController::class, 'book']);
    Route::get('/bookings/my', [ServiceController::class, 'myBookings']);
    Route::put('/bookings/{id}/cancel', [ServiceController::class, 'cancelBooking']);
    Route::post('/bookings/{id}/reschedule', [ServiceController::class, 'reschedule']);

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
    });
});
