# Plan Completo: Backend Laravel 12 — Lyrium BioMarketplace

## Resumen del Proyecto

**Lyrium BioMarketplace** es un marketplace multi-vendedor para productos bio/salud en Peru.

| Componente | Tecnologia | URL Local |
|-----------|------------|-----------|
| Frontend | Next.js + React + TypeScript | http://localhost:3000 |
| Backend API | Laravel 12 + Sanctum + Spatie | http://127.0.0.1:8000/api |
| Base de datos | MySQL (XAMPP) | db-lyriumv1 |
| Deploy | Railway (backend + worker) + Vercel (frontend) | - |

**Multi-tenancy:** Single database con `store_id` por tienda (no base de datos separada).

**Autenticacion:** Laravel Sanctum con Bearer tokens. Cookie `auth_token` httpOnly en el frontend.

---

## Estado General de Fases

| Fase | Descripcion | Estado |
|------|-------------|--------|
| Fase 1 | Fundacion (auth, tiendas, productos, categorias) | ✅ Completada |
| Fase 2 | Comercio (ordenes, carrito, inventario, reviews, facturas, cupones) | ✅ Completada |
| Fase 3 | Financiero (pagos vendedor, planes/Izipay, WebSockets/Reverb) | ✅ Completada |
| Fase 4 | Servicios (citas, logistica/envios, devoluciones, disputas) | ✅ Completada |
| Fase 5 | Comunicacion (tickets, notificaciones) | ✅ Completada |
| Fase 6 | Fidelizacion + extras admin (loyalty, suppliers, contracts) | ✅ Completada |
| Fase 7 | Funcionalidades avanzadas (analytics, blog, foro) | 🔜 Pendiente |

---

## FASE 1 — Fundacion ✅ COMPLETADA

### Que incluye
- Proyecto Laravel 12 + configuracion base
- Auth (login/register/logout/validate) con Sanctum
- OTP email verification al registrarse (via Resend)
- Google OAuth (via `SocialAuthController`)
- Users + Roles (administrator, seller, customer, logistics_operator)
- Stores (vendedores) + CRUD admin
- Categorias CRUD con jerarquia (`parent_id`) y tipo (`product` | `service`)
- Productos CRUD (seller + admin approval)
- Middleware de seguridad (ForceJson, EnsureRole, EnsureStoreApproved, EnsureEmailVerified)
- Registro diferenciado: vendedor (con RUC/tienda) y cliente (solo nombre/email/password)

### Paquetes instalados
- `laravel/sanctum` v4.3.1 — autenticacion API
- `spatie/laravel-permission` v6.24.1 — roles y permisos
- `spatie/laravel-query-builder` v6.4.3 — filtros API
- `spatie/laravel-medialibrary` — upload imagenes (productos, tiendas, categorias)

### Migraciones (Fase 1)
```
0001_01_01_000000_create_users_table
0001_01_01_000001_create_cache_table
0001_01_01_000002_create_jobs_table
2026_03_04_054735_create_permission_tables         (Spatie)
2026_03_04_072635_create_personal_access_tokens_table (Sanctum)
2026_03_04_100001_create_plans_table
2026_03_04_100002_create_stores_table
2026_03_04_100003_create_store_members_table
2026_03_04_100004_create_subscriptions_table
2026_03_04_100005_create_categories_table
2026_03_04_100006_create_products_table
2026_03_04_100007_create_product_attributes_table
2026_03_04_100008_create_category_product_table
2026_03_09_013022_create_email_verification_codes_table
2026_03_09_041306_add_google_id_to_users_table
2026_03_19_213955_create_media_table               (Spatie MediaLibrary)
2026_03_20_042447_add_type_to_categories_table
```

### Modelos
| Modelo | Relaciones clave |
|--------|-----------------|
| User | hasMany(Store), belongsToMany(Store via store_members), HasApiTokens, HasRoles, hasOne(UserLoyaltyAccount) |
| Store | belongsTo(User), hasMany(Product), hasMany(Service), hasMany(StoreBranch), hasOne(Subscription) |
| StoreMember | belongsTo(Store), belongsTo(User) |
| Category | hasMany(Category via parent_id), belongsToMany(Product); campo `type`: product/service |
| Product | belongsTo(Store), belongsToMany(Category), hasMany(ProductAttribute); imagenes via MediaLibrary |
| ProductAttribute | belongsTo(Product) |
| Plan | hasMany(Subscription) |
| Subscription | belongsTo(Store), belongsTo(Plan) |
| EmailVerificationCode | belongsTo(User) |

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| AuthController | login, register, registerCustomer, verifyOtp, resendOtp, googleAuth, logout, validateToken, refreshToken |
| SocialAuthController | Google OAuth callback |
| UserController | me, updateProfile, show, index, byRole, update, destroy |
| StoreController | index, show, me, store, update, updateStatus, updateVisual, uploadLogo, uploadBanner, uploadGallery, deleteGalleryImage, branches, updateBranches |
| CategoryController | index, megaMenu, show, store, update, uploadImage, destroy |
| ProductController | index, adminIndex, show, store, update, destroy, updateStock, updateStatus |

### Middleware
| Middleware | Proposito |
|-----------|----------|
| ForceJson | Fuerza `Accept: application/json` en todas las API requests |
| EnsureRole | Verifica rol del usuario |
| EnsureStoreApproved | Para sellers: verifica tienda approved |
| EnsureEmailVerified | Bloquea usuarios no verificados |

### API Resources
`UserResource`, `StoreResource`, `SellerResource`, `CategoryResource`, `ProductResource`, `MediaResource`

### Seeders
| Seeder | Datos |
|--------|-------|
| RoleSeeder | administrator, seller, customer, logistics_operator |
| PlanSeeder | Emprende (5%), Crece (10%), Especial (15%) con campos detallados de beneficios |
| AdminUserSeeder | pierre@admin.com / password (admin) + seller de prueba con tienda aprobada |
| CategorySeeder | Categorias bio/organicas de productos y servicios |
| ServiceCategorySeeder | Categorias especificas para servicios |

### Endpoints Auth y publicos (Fase 1)

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | /api/auth/login | Login |
| POST | /api/auth/register | Registro vendedor |
| POST | /api/auth/register-customer | Registro cliente |
| POST | /api/auth/verify-otp | Verificar OTP email |
| POST | /api/auth/resend-otp | Reenviar OTP (throttle 3/min) |
| POST | /api/auth/google | Google OAuth |
| POST | /api/auth/logout | Logout (auth) |
| GET | /api/auth/validate | Validar token (auth) |
| POST | /api/auth/refresh | Rotar token (auth) |
| GET | /api/categories | Listar categorias |
| GET | /api/categories/mega-menu | Mega menu de categorias |
| GET | /api/categories/{id} | Detalle categoria |
| GET | /api/products | Listar productos aprobados |
| GET | /api/products/{id} | Detalle producto |

---

## FASE 2 — Comercio ✅ COMPLETADA

### Que incluye
- Carrito de compras (cart + cart_items)
- Sistema de ordenes multi-vendedor (sin sub-ordenes: `store_id` directo en `order_items`)
- Flujo de estados de ordenes e items
- Facturas (invoices)
- Cupones de descuento
- Reviews/resenas de productos
- Contenido del home: banners, heroes, marcas, beneficios, newsletter

### Migraciones (Fase 2)
```
2026_03_19_220638_add_rating_and_total_sales_to_stores_table
2026_03_19_220858_add_type_and_enhanced_fields_to_products_table
2026_03_19_221251_create_carts_table
2026_03_19_221252_create_cart_items_table
2026_03_19_221455_create_orders_table
2026_03_19_221456_create_order_items_table
2026_03_19_221720_create_invoices_table
2026_03_19_231056_drop_unit_price_from_cart_items_table
2026_03_20_002516_create_banners_table
2026_03_20_041114_create_brands_table
2026_03_20_042510_create_benefits_table
2026_03_20_042511_create_newsletter_subscriptions_table
2026_03_20_182007_add_enlace_to_banners_table
2026_03_22_100000_create_reviews_table
2026_03_22_200000_create_coupons_table
2026_03_22_200001_add_discount_fields_to_orders_table
2026_03_22_210000_update_order_items_status_default
```

### Modelos
| Modelo | Relaciones clave |
|--------|-----------------|
| Cart | belongsTo(User), hasMany(CartItem) |
| CartItem | belongsTo(Cart), belongsTo(Product) |
| Order | belongsTo(User customer_id), hasMany(OrderItem), hasOne(Invoice) |
| OrderItem | belongsTo(Order), belongsTo(Product), belongsTo(Store via store_id) |
| Invoice | belongsTo(Order) |
| Coupon | hasMany(CouponUsage) |
| CouponUsage | belongsTo(Coupon), belongsTo(User) |
| Review | belongsTo(User), belongsTo(Product) |
| Banner | - |
| Brand | - |
| Benefit | - |
| NewsletterSubscription | - |

> **Nota arquitectural:** No existe tabla `sub_orders`. Los items de cada tienda se gestionan directamente via `order_items.store_id`. Cada `OrderItem` tiene su propio `status` para tracking por tienda.

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| CartController | ver y gestionar carrito |
| OrderController | index, store, show, updateStatus, confirm, confirmItem, updateItemStatus |
| InvoiceController | index, show, generate |
| CouponController | index, validate, show, store, update, destroy |
| ReviewController | index, show, store, update, destroy |
| HomeController | heroes, banners, categorySection |
| BrandController | index |
| BenefitController | index |
| NewsletterController | subscribe |
| MediaController | uploadProductMedia, uploadStoreLogo, uploadStoreBanner, uploadStoreBanner2, uploadStoreGallery, deleteStoreGallery, uploadStorePolicy, deleteStorePolicy |

### Endpoints (seleccion)

#### Publicos
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/reviews | Listar resenas |
| GET | /api/reviews/{id} | Detalle resena |
| GET | /api/home/heroes | Heroes/slides del home |
| GET | /api/home/banners-pub | Banners publicitarios |
| GET | /api/home/section/{slug} | Seccion de categorias del home |
| GET | /api/brands | Listar marcas |
| GET | /api/benefits | Listar beneficios |
| POST | /api/newsletter | Suscribirse al newsletter |

#### Autenticados
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/orders | Mis ordenes |
| POST | /api/orders | Crear orden |
| GET | /api/orders/{id} | Detalle orden |
| PUT | /api/orders/{id}/status | Actualizar estado orden |
| PUT | /api/orders/{id}/confirm | Confirmar orden |
| PUT | /api/orders/{orderId}/items/{itemId}/confirm | Confirmar item |
| PUT | /api/orders/{orderId}/items/{itemId}/status | Estado de item |
| GET | /api/invoices | Mis facturas |
| GET | /api/invoices/{id} | Detalle factura |
| POST | /api/orders/{orderId}/invoice | Generar factura |
| GET | /api/coupons/validate | Validar cupon |
| POST | /api/reviews | Crear resena |
| PUT | /api/reviews/{id} | Editar resena |
| DELETE | /api/reviews/{id} | Eliminar resena |

#### Seller + Admin (productos/media)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | /api/products | Crear producto |
| PUT | /api/products/{id} | Editar producto |
| DELETE | /api/products/{id} | Eliminar producto |
| PUT | /api/products/{id}/stock | Actualizar stock |
| POST | /api/products/{id}/media | Subir imagen producto |
| POST | /api/stores/{id}/media/logo | Subir logo tienda |
| POST | /api/stores/{id}/media/banner | Subir banner tienda |
| POST | /api/stores/{id}/media/banner2 | Subir banner secundario |
| POST | /api/stores/{id}/media/gallery | Subir galeria tienda |
| DELETE | /api/stores/{id}/media/gallery/{mediaId} | Eliminar imagen galeria |
| POST | /api/stores/{id}/media/policy | Subir politica PDF |
| DELETE | /api/stores/{id}/media/policy/{type} | Eliminar politica PDF |

#### Admin (productos admin)
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/admin/products | Todos los productos (incluyendo pendientes) |
| PUT | /api/products/{id}/status | Aprobar/rechazar producto |
| POST | /api/categories/{id}/image | Subir imagen de categoria |

---

## FASE 3 — Financiero y WebSockets ✅ COMPLETADA

### Que incluye
- Planes de suscripcion con beneficios detallados
- Solicitudes de cambio de plan (PlanRequests) con pago via Izipay
- Webhook de Izipay para confirmar pagos
- Pagos a vendedores (SellerPayments) y schedules de pago
- Solicitudes de cambio de perfil de tienda (StoreProfileRequests, requieren aprobacion admin)
- Sucursales de tienda (StoreBranches)
- Subscriptions CRUD
- WebSockets con **Laravel Reverb** (puerto 8080)
- SSE legacy para modulo de planes (`/api/events`)
- SystemConfig (configuracion dinamica clave/valor para admin)

### Migraciones (Fase 3)
```
2026_03_23_000005_create_payment_schedules_tables
2026_03_24_173040_create_store_branches_table
2026_03_24_233141_add_banner2_to_stores_table
2026_03_25_000000_add_layout_to_stores_table
2026_03_25_000001_add_detailed_benefits_to_plans_table
2026_03_25_000002_create_store_profile_requests_table
2026_03_25_000003_add_profile_status_to_stores_table
2026_03_25_000004_create_plan_requests_table
2026_03_25_000005_create_system_configs_table
2026_03_25_013038_add_activity_to_stores_table
2026_03_25_024640_add_social_fields_to_stores_table
2026_03_26_000001_add_months_to_plan_requests_table
2026_03_26_120000_alter_products_image_column
2026_03_22_000002_add_business_and_store_fields_to_stores_table
```

### Modelos
| Modelo | Relaciones |
|--------|-----------|
| PaymentSchedule | - |
| SellerPayment | belongsTo(Store) |
| PlanRequest | belongsTo(Store), belongsTo(Plan) |
| StoreProfileRequest | belongsTo(Store) |
| StoreBranch | belongsTo(Store) |
| SystemConfig | key/value store |

### WebSockets — Laravel Reverb
- Puerto: `ws://localhost:8080`
- `BROADCAST_CONNECTION=reverb`
- Canales en `routes/channels.php`:
  - `private-user.{id}` — notificaciones, mensajes de ticket (auth: usuario mismo)
  - `private-store.{id}` — ordenes, bookings, estado tienda/producto, planes (auth: owner o admin)
  - `categories` (publico) — cambios de categorias
- Eventos en `app/Events/`:
  - `NewOrderReceived`, `NewBookingReceived`
  - `StoreStatusChanged`, `ProductStatusChanged`
  - `TicketMessageReceived`, `TicketMessagesRead`, `TicketInboxUpdated`
  - `CategoryUpdated`, `NotificationCreated`, `PlanStatusChanged`
- `BroadcastNotificationCreated` listener — dispara `NotificationCreated` automaticamente para notificaciones con canal `database`

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| PlanController | index, show |
| PlanRequestController | store, me, index (admin), show (admin), approve, reject, stream, webhookIzipay |
| ProfileRequestController | me, store, index (admin), show (admin), approve, reject, stream |
| SubscriptionController | index, current, store, show, cancel, renew |
| PaymentController | sellerPayments, sellerPendingPayments, sellerCompletedPayments, sellerPendingTotal, index (admin), show (admin), process, cancel, reschedule, schedules, updateSchedule, isPaymentDayToday, nextPaymentDate |
| SystemConfigController | colors, publicConfigs, index (admin), show, store, update, destroy |
| EventsController | stream (SSE legacy) |

### Endpoints (seleccion)

#### Publicos
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/plans | Listar planes |
| GET | /api/plans/{id} | Detalle plan |
| GET | /api/config/colors | Colores del sistema |
| GET | /api/config/public | Configs publicas |
| GET | /api/events | SSE stream (planes) |
| POST | /api/webhooks/izipay | Webhook Izipay |

#### Autenticados
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | /api/plans/requests | Solicitar cambio de plan |
| GET | /api/stores/me/plan-request | Mi solicitud de plan activa |
| GET | /api/stores/me/profile-request | Mi solicitud de perfil activa |
| POST | /api/stores/me/profile-request | Crear solicitud de cambio de perfil |
| GET | /api/subscriptions/current | Suscripcion actual |
| GET | /api/payments | Pagos del vendedor |
| GET | /api/payments/pending | Pagos pendientes |
| GET | /api/payments/completed | Pagos completados |
| GET | /api/payments/pending-total | Total pendiente |

#### Admin
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/admin/plan-requests | Listar solicitudes de plan |
| PUT | /api/admin/plan-requests/{id}/approve | Aprobar solicitud |
| PUT | /api/admin/plan-requests/{id}/reject | Rechazar solicitud |
| GET | /api/admin/plan-requests/stream | SSE stream de solicitudes |
| GET | /api/admin/profile-requests | Listar solicitudes de perfil |
| PUT | /api/admin/profile-requests/{id}/approve | Aprobar cambio de perfil |
| PUT | /api/admin/profile-requests/{id}/reject | Rechazar cambio de perfil |
| GET | /api/admin/payments | Todos los pagos |
| PUT | /api/admin/payments/{id}/process | Procesar pago |
| GET | /api/admin/config | Config del sistema |
| PUT | /api/admin/config/{key} | Actualizar config |

---

## FASE 4 — Servicios y Logistica ✅ COMPLETADA

### Que incluye
- Servicios (no solo productos) con categorias, horarios y reservas (bookings)
- Agenda/citas con slots disponibles, confirmacion, cancelacion, reagendado
- Logistica: zonas, metodos, tarifas, envios con tracking y eventos
- Devoluciones (Returns) con flujo de estados
- Disputas (Disputes) entre compradores y vendedores

### Migraciones (Fase 4)
```
2026_03_21_060112_add_service_fields_to_products_table
2026_03_23_000001_create_services_tables       (services, service_schedules, service_bookings)
2026_03_23_000002_create_shipping_tables       (shipping_zones, shipping_methods, shipping_rates, store_shipping_methods, shipments, shipment_events)
2026_03_23_000003_create_returns_tables        (product_returns, return_items)
2026_03_23_000004_create_disputes_tables       (disputes, dispute_messages, dispute_attachments)
2026_03_30_004800_add_category_id_to_services_table
```

### Modelos
| Modelo | Relaciones |
|--------|-----------|
| Service | belongsTo(Store), belongsTo(Category), hasMany(ServiceSchedule), hasMany(ServiceBooking) |
| ServiceSchedule | belongsTo(Service) |
| ServiceBooking | belongsTo(Service), belongsTo(User customer) |
| ShippingZone | hasMany(ShippingMethod), hasMany(ShippingRate) |
| ShippingMethod | belongsTo(ShippingZone) |
| ShippingRate | belongsTo(ShippingZone), belongsTo(ShippingMethod) |
| StoreShippingMethod | belongsTo(Store), belongsTo(ShippingMethod) |
| Shipment | belongsTo(Order), hasMany(ShipmentEvents) |
| ProductReturn | belongsTo(Order), belongsTo(User), hasMany(ReturnItem) |
| ReturnItem | belongsTo(ProductReturn), belongsTo(OrderItem) |
| Dispute | belongsTo(Order), hasMany(DisputeMessage), hasMany(DisputeAttachment) |
| DisputeMessage | belongsTo(Dispute), belongsTo(User) |
| DisputeAttachment | belongsTo(Dispute) |

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| ServiceController | index, show, store, update, destroy, availableSlots, book, myBookings, cancelBooking, reschedule, sellerBookings, confirmBooking, markNoShow, addNotes |
| ShippingController | methods, zones, calculate, storeMethods, configureStore, sellerShipments, updateTracking, markShipped, markDelivered, updateStatus, addEvent |
| ReturnController | orderReturns, myReturns, store, show, cancel, sellerReturns, approve, reject, markReceived, refund, updateTracking |
| DisputeController | orderDisputes, userDisputes, store, show, addMessage, storeDisputes, updateStatus (seller), index (admin), assign, resolve, close, cancel |

### Endpoints (seleccion)

#### Publicos
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/services | Listar servicios aprobados |
| GET | /api/services/{id} | Detalle servicio |
| GET | /api/shipping/methods | Metodos de envio disponibles |
| GET | /api/shipping/zones | Zonas de envio |
| GET | /api/shipping/calculate | Calcular costo de envio |
| GET | /api/orders/{orderId}/returns | Devoluciones de una orden |
| GET | /api/orders/{orderId}/disputes | Disputas de una orden |

#### Autenticados
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/services/{id}/slots | Slots disponibles |
| POST | /api/services/{serviceId}/book | Reservar cita |
| GET | /api/bookings/my | Mis reservas |
| PUT | /api/bookings/{id}/cancel | Cancelar reserva |
| POST | /api/bookings/{id}/reschedule | Reagendar |
| GET | /api/returns/my | Mis devoluciones |
| POST | /api/returns | Crear devolucion |
| GET | /api/returns/{id} | Detalle devolucion |
| PUT | /api/returns/{id}/cancel | Cancelar devolucion |
| POST | /api/disputes | Abrir disputa |
| GET | /api/disputes/my | Mis disputas |
| GET | /api/disputes/{id} | Detalle disputa |
| POST | /api/disputes/{id}/messages | Mensaje en disputa |

#### Seller + Admin
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | /api/services | Crear servicio |
| PUT | /api/services/{id} | Editar servicio |
| DELETE | /api/services/{id} | Eliminar servicio |
| GET | /api/bookings/seller | Reservas de mi tienda |
| PUT | /api/bookings/{id}/confirm | Confirmar reserva |
| PUT | /api/bookings/{id}/no-show | Marcar no-show |
| GET | /api/store/shipping/methods | Metodos configurados |
| POST | /api/store/shipping/configure | Configurar envios |
| GET | /api/shipments | Envios de mi tienda |
| PUT | /api/shipments/{id}/tracking | Actualizar tracking |
| PUT | /api/shipments/{id}/ship | Marcar enviado |
| PUT | /api/shipments/{id}/deliver | Marcar entregado |
| POST | /api/shipments/{id}/event | Agregar evento envio |
| GET | /api/returns | Devoluciones de mi tienda |
| PUT | /api/returns/{id}/approve | Aprobar devolucion |
| PUT | /api/returns/{id}/reject | Rechazar devolucion |
| PUT | /api/returns/{id}/received | Marcar recibido |
| PUT | /api/returns/{id}/refund | Confirmar reembolso |
| GET | /api/disputes | Disputas de mi tienda |

#### Admin
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/admin/disputes | Todas las disputas |
| PUT | /api/admin/disputes/{id}/assign | Asignar disputa |
| PUT | /api/admin/disputes/{id}/resolve | Resolver disputa |
| PUT | /api/admin/disputes/{id}/close | Cerrar disputa |

---

## FASE 5 — Comunicacion ✅ COMPLETADA

### Que incluye
- Sistema de tickets (helpdesk) con mensajes, attachments, encuestas de satisfaccion
- Panel admin de tickets: asignacion, prioridad, escalado
- Notificaciones (sistema nativo Laravel + canal `database`)
- Broadcasting automatico de notificaciones via Reverb

### Migraciones (Fase 5)
```
2026_03_21_100001_create_tickets_table
2026_03_21_100002_create_ticket_messages_table
2026_03_21_100003_create_ticket_attachments_table
2026_03_21_173622_create_notifications_table
```

### Modelos
| Modelo | Relaciones |
|--------|-----------|
| Ticket | belongsTo(User), hasMany(TicketMessage), hasMany(TicketAttachment) |
| TicketMessage | belongsTo(Ticket), belongsTo(User) |
| TicketAttachment | belongsTo(Ticket) |

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| TicketController | index, store, show, getMessages, sendMessage, close, submitSurvey |
| AdminTicketController | index, show, getMessages, sendMessage, updateStatus, assign, updatePriority, escalate |
| NotificationController | index, show, read, readAll, destroy |

### Endpoints

#### Autenticados
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/tickets | Mis tickets |
| POST | /api/tickets | Crear ticket |
| GET | /api/tickets/{id} | Detalle ticket |
| GET | /api/tickets/{id}/messages | Mensajes del ticket |
| POST | /api/tickets/{id}/messages | Enviar mensaje |
| PUT | /api/tickets/{id}/close | Cerrar ticket |
| POST | /api/tickets/{id}/survey | Encuesta de satisfaccion |
| GET | /api/notifications | Mis notificaciones |
| GET | /api/notifications/{id} | Detalle notificacion |
| PUT | /api/notifications/{id}/read | Marcar como leida |
| POST | /api/notifications/read-all | Marcar todas leidas |
| DELETE | /api/notifications/{id} | Eliminar notificacion |

#### Admin
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/admin/tickets | Todos los tickets |
| GET | /api/admin/tickets/{id} | Detalle ticket |
| POST | /api/admin/tickets/{id}/messages | Responder ticket |
| PUT | /api/admin/tickets/{id}/status | Cambiar estado |
| PUT | /api/admin/tickets/{id}/assign | Asignar a agente |
| PUT | /api/admin/tickets/{id}/priority | Cambiar prioridad |
| PUT | /api/admin/tickets/{id}/escalate | Escalar ticket |

---

## FASE 6 — Fidelizacion y Modulos Admin ✅ COMPLETADA

### Que incluye
- Programa de fidelizacion completo (puntos, tiers, recompensas, canjes, codigos)
- Proveedores internos (Suppliers) — admin
- Contratos digitales de vendedores con archivos adjuntos — admin
- Busqueda global

### Migraciones (Fase 6)
```
2026_03_23_000006_create_loyalty_tables
(loyalty_programs, loyalty_tiers, loyalty_rewards, loyalty_transactions, user_loyalty_accounts, user_redeemed_rewards)
2026_03_20_021957_create_suppliers_table
2026_03_20_021959_create_supplier_store_table
2026_03_20_031801_create_contracts_table
2026_03_20_031803_create_contract_audit_trails_table
2026_03_20_100000_refactor_suppliers_table
```

### Modelos
| Modelo | Relaciones |
|--------|-----------|
| LoyaltyProgram | hasMany(LoyaltyTier), hasMany(LoyaltyReward) |
| LoyaltyTier | belongsTo(LoyaltyProgram) |
| LoyaltyReward | belongsTo(LoyaltyProgram) |
| LoyaltyTransaction | belongsTo(User), belongsTo(UserLoyaltyAccount) |
| UserLoyaltyAccount | belongsTo(User), hasMany(LoyaltyTransaction) |
| UserRedeemedReward | belongsTo(User), belongsTo(LoyaltyReward) |
| Supplier | belongsToMany(Store via supplier_store) |
| Contract | belongsTo(Store), belongsTo(User), hasMany(ContractAuditTrail) |
| ContractAuditTrail | belongsTo(Contract) |

### Controllers
| Controller | Metodos clave |
|-----------|---------------|
| LoyaltyController | account, status, rewards, redeem, redemptions, transactions, validateCode, useCode |
| SupplierController | index, show, store, update, destroy |
| ContractController | index, show, store, update, updateStatus, upload, download, destroy |
| SearchController | busqueda global |

### Endpoints

#### Autenticados
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/loyalty/account | Mi cuenta de puntos |
| GET | /api/loyalty/status | Estado y tier actual |
| GET | /api/loyalty/rewards | Recompensas disponibles |
| POST | /api/loyalty/redeem | Canjear recompensa |
| GET | /api/loyalty/redemptions | Mis canjes |
| GET | /api/loyalty/transactions | Historial de puntos |
| POST | /api/loyalty/validate-code | Validar codigo de canje |
| POST | /api/loyalty/use-code | Usar codigo de canje |

#### Admin
| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | /api/suppliers | Listar proveedores |
| POST | /api/suppliers | Crear proveedor |
| PUT | /api/suppliers/{id} | Editar proveedor |
| DELETE | /api/suppliers/{id} | Eliminar proveedor |
| GET | /api/contracts | Listar contratos |
| POST | /api/contracts | Crear contrato |
| PUT | /api/contracts/{id}/status | Cambiar estado contrato |
| POST | /api/contracts/{id}/upload | Subir archivo contrato |
| GET | /api/contracts/{id}/download | Descargar contrato |

---

## FASE 7 — Funcionalidades Avanzadas 🔜 PENDIENTE

### Objetivo
Analytics/reportes de ventas, blog, foro comunitario.

### Componentes pendientes

#### Analytics/Reportes
```
# Reportes calculados (queries sobre datos existentes, no tablas nuevas)
- Ventas por periodo (dia, semana, mes)
- Productos mas vendidos
- Vendedores con mas ventas
- Comisiones generadas
- Usuarios nuevos por periodo
- Ordenes por estado
```

#### Endpoints analytics
```
GET  /api/admin/analytics/sales         → Reporte de ventas
GET  /api/admin/analytics/products      → Productos top
GET  /api/admin/analytics/sellers       → Vendedores top
GET  /api/seller/analytics/dashboard    → Dashboard del vendedor
```

#### Blog (opcional)
```
posts
- id, author_id, title, slug, content (longtext), excerpt, featured_image
- status (draft, published), published_at, timestamps, soft_deletes

post_categories (pivot)
```

#### Foro (opcional)
```
forum_topics
- id, user_id, category_id, title, content, is_pinned, is_locked, timestamps

forum_replies
- id, topic_id, user_id, content, timestamps
```

---

## Resumen de Tablas por Modulo

| Modulo | Tablas principales |
|--------|-------------------|
| Auth/Users | users, personal_access_tokens, email_verification_codes, permission_tables |
| Stores | stores, store_members, store_branches, store_profile_requests |
| Products | products, product_attributes, category_product |
| Categories | categories |
| Plans/Subs | plans, subscriptions, plan_requests |
| Orders | orders, order_items, invoices, coupons, coupon_usages |
| Cart | carts, cart_items |
| Services | services, service_schedules, service_bookings |
| Shipping | shipping_zones, shipping_methods, shipping_rates, store_shipping_methods, shipments |
| Returns | product_returns, return_items |
| Disputes | disputes, dispute_messages, dispute_attachments |
| Payments | payment_schedules, seller_payments (via PaymentSchedule model) |
| Tickets | tickets, ticket_messages, ticket_attachments |
| Notifications | notifications |
| Reviews | reviews |
| Loyalty | loyalty_programs, loyalty_tiers, loyalty_rewards, loyalty_transactions, user_loyalty_accounts, user_redeemed_rewards |
| Suppliers | suppliers, supplier_store |
| Contracts | contracts, contract_audit_trails |
| Media | media (Spatie) |
| Admin/Config | system_configs, banners, brands, benefits, newsletter_subscriptions |

**Total estimado: ~55+ tablas**

---

## Seeders Actuales

| Seeder | Proposito |
|--------|----------|
| RoleSeeder | 4 roles |
| PlanSeeder | 3 planes con beneficios detallados |
| AdminUserSeeder | pierre@admin.com / password + seller de prueba |
| CategorySeeder | Categorias bio/organicas de productos |
| ServiceCategorySeeder | Categorias de servicios |
| BannerSeeder | Banners del home |
| BenefitSeeder | Beneficios del marketplace |
| BrandSeeder | Marcas de ejemplo |
| HomeSeeder | Contenido del home |
| LoyaltyAndPaymentSeeder | Programa de fidelizacion y pagos |
| ShippingSeeder | Zonas y metodos de envio |
| SystemConfigSeeder | Configuracion inicial del sistema |
| ProductSeeder | Productos de ejemplo |
| OfferProductSeeder | Productos en oferta |
| PlanRequestSeeder | Solicitudes de planes de prueba |

---

## Entorno y Despliegue

### Local
```bash
# Backend
php artisan serve          # → http://127.0.0.1:8000
php artisan queue:work     # Worker para jobs y notificaciones
php artisan reverb:start   # WebSocket server → ws://localhost:8080

# Comandos utiles
composer run setup          # Install deps + migrate + seed + build
composer run dev            # Server + queue + log viewer + Vite en paralelo
php artisan migrate:fresh --seed
vendor/bin/pint             # Code style fixer
```

### Variables de entorno clave
```
DB_DATABASE=db-lyriumv1
FRONTEND_URL=http://localhost:3000
BROADCAST_CONNECTION=reverb
REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET
REVERB_HOST=localhost, REVERB_PORT=8080, REVERB_SCHEME=http
RESEND_API_KEY              # Email OTP y notificaciones
GOOGLE_CLIENT_ID            # OAuth
IZIPAY_*                    # Pasarela de pago para planes
```

### Railway (Produccion)
- **Servicio web:** GitHub repo → `Root Directory: Backend-Lyrium` → `Start Command: php artisan serve`
- **Servicio worker:** mismo repo → `Start Command: chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh`
- Sin dominio publico en el worker
- Variables de entorno configuradas en el dashboard de Railway

---

## Usuarios de Prueba

| Email | Password | Rol | Notas |
|-------|----------|-----|-------|
| pierre@admin.com | password | administrator | Admin principal |
| (seeder seller) | password | seller | Tienda aprobada de ejemplo |