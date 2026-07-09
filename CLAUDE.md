# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Backend Lyrium** — Laravel 12 REST API for a multi-vendor biomarketplace (Peru). PHP 8.2+, MySQL (`db-lyriumv1`), Sanctum auth, Spatie roles/permissions.

Frontend is a separate Next.js repo at `../Frontend-Lyrium/frontapp/` (runs at `http://localhost:3000`).

**Frontend architecture:**
- `src/app/` — App Router pages (dashboard, admin panel, store pages)
- `src/features/` — feature-based modules grouped by domain (admin, store, auth, landing)
- `src/components/` — shared UI components (BaseModal, BaseTable, Icon, etc.)
- `src/shared/` — shared utilities, hooks, types, lib
- Admin sidebar defined via `menuItems` array in layout — uses `usePathname()` to highlight active item. Each admin section (dashboard, finance, clients, products, orders, etc.) has its own sidebar config with icon, label, path, subItems.
- `PAGE_SIZE` and `PAGINATION_MAX` constants in shared config (16, 5).

## Commands

```bash
composer run setup              # Install deps + migrate + seed + build
composer run dev                # Start server, queue, log viewer, and Vite in parallel
composer run test               # Run all PHPUnit tests
php artisan test --filter=TestName          # Run a single test by name
php artisan test tests/Feature/AuthTest.php # Run a single test file
php artisan migrate --seed                  # Run migrations with seeders
php artisan migrate:fresh --seed            # Reset DB and reseed (dev)
php artisan db:seed --class=PlanSeeder      # Run a single seeder class
php artisan reverb:start --debug    # Start WebSocket server (Reverb) on port 8080
php artisan queue:work              # Process queued jobs (email, invoices, FCM) — needed for order flow
php artisan queue:failed            # List failed jobs
php artisan queue:retry all         # Retry all failed jobs
php artisan schedule:work           # Run scheduler in foreground (dev)
vendor/bin/pint                     # Code style fixer (Laravel Pint)
vendor/bin/pint --test              # Check style without modifying files
```

Default queue driver is `database` — always run `php artisan queue:work` alongside `composer run dev` when testing order/notification flows. In tests (`phpunit.xml`) it is overridden to `sync` so jobs run inline.

## Architecture

### Auth & Roles
- **Sanctum** bearer tokens for API auth
- **Spatie Permission** with 4 roles: `administrator`, `seller`, `customer`, `logistics_operator`
- OTP email verification on registration (via Resend); Google OAuth via `SocialAuthController`
- `EnsureEmailVerified` middleware guards verified-only routes

### Middleware (registered in `bootstrap/app.php`)
- `ForceJson` — forces `Accept: application/json` on all API requests
- `EnsureRole` — role-based route guard (`role:seller,administrator`)
- `EnsureStoreApproved` — blocks sellers with unapproved stores
- `EnsureEmailVerified` — blocks unverified users
- `EnsureContractActive` (alias: `contract.active`) — blocks sellers without an `ACTIVE` contract from mutating products and services (create/update/delete product/service, upload product media, manage bookings); admins are exempt
- `TrackCustomerPanelVisit` — prepended to all API middleware; silently records the last time a customer visited the dashboard (used by scheduled reminder emails)

### Models & Relationships
- **User** → owns many Stores, member of many Stores (via StoreMember pivot); has LoyaltyAccount
- **Store** → has many Products, Services, Branches, Members, Shipments; one latest Subscription; status workflow: `pending → approved/rejected/banned`; profile changes via `StoreProfileRequest`
- **Product** → belongs to Store, many-to-many Categories, has many ProductAttributes; status workflow: `draft → pending_review → approved/rejected`; images via Spatie MediaLibrary
- **Category** — self-referencing hierarchy via `parent_id`; `type` field: `product` | `service`
- **Service** → belongs to Store and Category (type=service); has ServiceSchedules and ServiceBookings; Google Calendar integration via `GoogleCalendarService`
- **Plan/Subscription** — store subscription plans with commission rates; plan upgrades via `PlanRequest` (paid via Izipay webhook); commission deducted at order time by `CommissionCalculatorService`
- **Order/OrderItem** → multi-store orders; each OrderItem has its own `store_id`; has Shipment, Invoice, Returns, Disputes
- **ServiceBooking** → belongs to Service (no direct store_id; store accessed via `service.store_id`)
- **Shipment** → tracks shipping events, zones, methods; carrier resolved by `CarrierResolver`
- **Dispute/DisputeMessage** — order dispute resolution workflow
- **Ticket/TicketMessage** — customer support tickets; attachments via `TicketAttachmentService`
- **LoyaltyProgram/LoyaltyTier/LoyaltyTransaction/UserLoyaltyAccount** — points & rewards system
- **LiriosAccount/LiriosTransaction** — internal Lirios wallet (points currency); each store has `lirios_percent` controlling max redemption; `LiriosService` handles checkout eligibility; routes under `/lirios/*` (authenticated)
- **Contract/ContractAuditTrail** — admin-managed seller contracts with file uploads via `ContractDocumentService`; PDF/Word via `barryvdh/laravel-dompdf` + `phpoffice/phpword`
- **Supplier** — internal supplier management (admin)
- **SystemConfig** — key/value config store for dynamic settings
- **ServiceHold** — temporary slot reservation during service booking checkout (added 2026-06-11)
- **BlockedIp** — IP addresses blocked/flagged/whitelisted by the security module (Fase 7)
- **SecurityAlert** — security alerts generated from critical audit events (Fase 7)
- Soft deletes on User, Store, Product

### Observers
Two model observers auto-maintain denormalized counters — never update these fields manually:
- `ExpenseObserver` (`app/Observers/ExpenseObserver.php`) — syncs `suppliers.total_gastado` and `total_recibos` whenever an `Expense` is created, updated, or deleted
- `ReviewObserver` (`app/Observers/ReviewObserver.php`) — recalculates and updates the average rating on the parent Store (or Product) whenever a Review is created, updated, or deleted

### Security Listeners (Fase 7)
Two listeners react to audit events to keep security tables in sync:
- `LogBlockedIpListener` — listens to `AuditLogCreated` and reacts to `security.ip.*` events (block/unblock/flag/whitelist), upserting `BlockedIp` records accordingly
- `LogSecurityAlertListener` — listens to `CriticalSecurityEvent` and creates `SecurityAlert` records for every critical event detected by the audit system

### Request/Response Pattern
- Validation in `app/Http/Requests/` (FormRequest classes)
- JSON transformation in `app/Http/Resources/` (API Resources)
- Controllers in `app/Http/Controllers/Api/`
- Services in `app/Services/` — business logic layer
- List endpoints use `spatie/laravel-query-builder` for filtering, sorting, and eager-loading via query params (`filter[field]`, `sort`, `include`)

### Media Uploads
- **Spatie MediaLibrary** handles all file uploads (product images, store logos/banners/gallery, policy docs)
- Dedicated `MediaController` for store and product media operations

### Routes (`routes/api.php`) — Four access tiers
1. **Public** (no auth): auth, categories, products, services, reviews, plans, shipping methods, home/brands/benefits, SSE events, Izipay webhook
2. **Authenticated** (`auth:sanctum`): profile, notifications, loyalty, cart, orders, bookings, returns, disputes, tickets, search
3. **Admin only** (`role:administrator`): user management, store approval, product status, plan/subscription admin, system config, suppliers, contracts, admin tickets/disputes/payments, loyalty program management, operational roles (`/admin/roles`), audit logs (`/admin/audit-logs`), expenses (`/admin/expenses`), operations 2FA (`/admin/operations/2fa/*`)
4. **Seller + Admin** (`role:seller,administrator`): store CRUD, product CRUD, service CRUD, shipping config, shipment tracking, return handling, seller payments, seller tickets, specialists management (`/stores/me/specialists` — professional staff assigned to services)

#### Admin Plans Routes (key subset)
```
GET    /admin/plans                        PlanController@adminIndex
POST   /admin/plans                        PlanController@store
GET    /admin/plans/{plan}                 PlanController@adminShow
PUT    /admin/plans/{plan}                 PlanController@update
DELETE /admin/plans/{plan}                 PlanController@destroy
POST   /admin/plans/{plan}/status          PlanController@setActive
POST   /admin/plans/{plan}/icon            PlanController@updateIcon
GET    /admin/plan-colors                  PlanController@getColors
PUT    /admin/plan-colors                  PlanController@saveColors
DELETE /admin/plan-colors                  PlanController@resetColors
POST   /admin/config/colors                SystemConfigController@updateColors
GET    /admin/plan-payments                PlanRequestController@paymentHistory
GET    /admin/vendedores                   AdminSellerController@vendedores
GET    /admin/vendedores/{id}/historial    AdminSellerController@vendedorHistorial
GET    /admin/sellers                      AdminSellerController@index
GET    /admin/sellers/stats                AdminSellerController@stats
GET    /admin/sellers/{id}                 AdminSellerController@show
```

> `{plan}` uses Laravel's route model binding by primary key (numeric `id`). `/admin/sellers/*` and `/admin/vendedores/*` are **different** endpoint groups on the same `AdminSellerController`. The `/sellers` group returns user-centric data; `/vendedores` returns store-centric subscription data for the plans admin panel.

#### StorePlanRequest — validation notes
- `commission_rate` is **nullable** (DB default: `0.1500`) — never send from frontend
- `features` must be sent as an array of objects `[{text: string, active: bool}]`, not strings
- `detailed_benefits` must be `[{emoji, title, description, color}]`

### Real-time (WebSockets — Laravel Reverb)
- **Reverb** instalado (`laravel/reverb v1.9`, `pusher/pusher-php-server v7.2`)
- Puerto: `ws://localhost:8080`
- `BROADCAST_CONNECTION=reverb` en `.env`
- Canales en `routes/channels.php`:
  - `private-user.{id}` — notificaciones, mensajes de ticket (auth: usuario mismo)
  - `private-store.{id}` — órdenes, bookings, estado de tienda/producto, planes (auth: dueño de tienda o admin)
  - `categories` (público) — cambios de categorías
- Eventos en `app/Events/`: `NewOrderReceived`, `NewBookingReceived`, `StoreStatusChanged`, `ProductStatusChanged`, `TicketMessageReceived`, `CategoryUpdated`, `NotificationCreated`, `PlanStatusChanged`
- `BroadcastNotificationCreated` listener en `app/Listeners/` — dispara `NotificationCreated` automáticamente para cualquier notificación con canal `database`
- **NOTA**: El SSE legacy (`/api/events` via `EventsController`) sigue activo para el módulo de planes

### Notification Channels
- **Email** — `Resend` (OTP, order confirmations, notifications)
- **Push** — Firebase FCM HTTP v1 (`FCM_PROJECT_ID`, `FCM_CREDENTIALS_JSON`); device tokens registered via `DeviceController`
- **WhatsApp** — `WhatsAppService` (partial integration, not production-ready)
- **Real-time in-app** — Reverb + `BroadcastNotificationCreated` listener

## Database

### Key Seeders (run order via DatabaseSeeder)
- `RoleSeeder` → 4 roles
- `PlanSeeder` → 3 plans (Emprende 5%, Crece 10%, Especial 15% commission)
- `AdminUserSeeder` → pierre@admin.com / password
- `CategorySeeder` → categorías bio/organic de productos y servicios
- Additional: `BannerSeeder`, `BenefitSeeder`, `BrandSeeder`, `HomeSeeder`, `LoyaltyAndPaymentSeeder`, `ShippingSeeder`, `SystemConfigSeeder`

### Testing
PHPUnit 11 configured with in-memory SQLite (`phpunit.xml`). Test suites: `tests/Feature/`, `tests/Unit/`. The test env forces `QUEUE_CONNECTION=sync` and `BROADCAST_CONNECTION=null` so jobs run inline and no WebSocket server is needed. SQLite may not support all MySQL-specific migrations — check `phpunit.xml` for skip/override directives if migrations fail in test mode.

`tests/Traits/WithRoles.php` provides role/user factory helpers; use it in Feature tests instead of manually seeding roles.

### Custom Artisan Commands (`app/Console/Commands/`)
- `ReleaseExpiredSlotHolds` — releases `ServiceHold` records past expiry; run via scheduler or manually during dev
- `CheckPendingStoresSLA` — flags stores pending review past SLA; scheduled every 6 hours
- `SendBirthdayNotifications` / `SendBirthdayAdvanceReminders` — daily at 08:00
- `SendCustomerPanelReminders` — daily at 09:00
- `CreateTestUsers` — generates test user fixtures (dev only)
- `TestNotifications` / `TestQueue` — manual smoke-test helpers

## Environment
Copy `.env.example` to `.env`. Key vars:
- `DB_DATABASE=db-lyriumv1` (MySQL via XAMPP)
- `FRONTEND_URL=http://localhost:3000` (used by CORS config)
- `RESEND_API_KEY` — email (OTP, notifications)
- `GOOGLE_CLIENT_ID` — OAuth
- `IZIPAY_*` — payment gateway for plan subscriptions
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` — WebSocket server
- `REVERB_HOST=localhost`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`

## Response Shape Conventions

Backend responses are NOT always wrapped in `{success: true, data: ...}`. Know the shapes:
- `PlanController@adminIndex` / `adminShow` → wraps in `AdminPlanResource` collection; **no top-level `success` key** — check `Array.isArray(response.data)`
- `PlanController@store|update|destroy|setActive` → `{success: true, message: "...", data: AdminPlanResource}`
- `AdminSellerController@vendedores` → `{success: true, data: [...], pagination: {...}}`
- `PlanRequestController@paymentHistory` → `{data: VendedorPago[], totales: {...}}` (no `success` key)
- `GET /config/colors` → `{data: {primary_color, success_color, ...}}` (no `success` key)

When consuming admin API responses on the frontend, **do not rely on `response.success`** as a trueness check — always verify the payload shape directly (e.g., `Array.isArray(response.data)`).

## Unconnected Controllers / Missing Routes

These controllers exist but their routes are **not registered** in `routes/api.php` — frontend calls will 404:

| Controller | Purpose | Frontend caller |
|-----------|---------|----------------|
| `SearchController` | Unified search, product search, suggestions | `shared/lib/api/searchRepository.ts` |
| `AgendaController` | Seller calendar — aggregates orders + bookings by month/year/type | `features/seller/agenda/` |
| `NubefactController` | SUNAT electronic invoicing via Nubefact | `features/admin/invoices/`, `shared/lib/api/nubefactRepository.ts` |
| `LiriosController` | Lirios wallet balance, checkout eligibility, transactions | `shared/lib/api/liriosRepository.ts` calls `/lirios/balance`, `/lirios/checkout-eligibility`, `/lirios/transactions` — all 404 |

`AgendaController` query params: `month`, `year`, `type` (all|orders|services), `date`, `per_page`, `page`.

`SearchController` fallback: uses DB query if Meilisearch/Scout is unavailable. Scout is configured in `config/scout.php` (`MEILISEARCH_HOST`, `MEILISEARCH_KEY` env vars).

## Frontend Features Built

### Admin Dashboard (refactor)
- Migrated from hard-coded HTML dashboard to `AdminDashboardClient` component with props: `stats, totalStores, pendingStores, totalRevenue, growthRate, chartData`
- Dynamic stats cards with loading skeleton, chart (OrdersChart), recent activity, quick actions
- Route `/admin` with page.tsx wrapper using `useAdminDashboard` — fetches from backend API `/api/admin/dashboard`

### Admin Finance Module (`rama-danmar` branch)
Location: `frontapp/src/features/admin/finance/`
- **FinancePageClient.tsx** — 4-section dashboard: KPIs, Top Buyers + Próximo Pago + Desglose + Comprobantes, Mapa de Calor (7×24 heatmap), Export button
- **Components**: `FinanceChart` (dynamic import, chart.js), `CardProxPago` (radial progress bar), `FinancialBreakdownCard` (IGV breakdown), `ComprobantesSection` (SUNAT invoice list), `KpiDetailModal` (modal with chart + per-period breakdown)
- **Hook**: `useFinanceAnalytics()` returns `data: MOCK_FINANCE_DATA` (all mock, no backend endpoint yet)
- **Types**: `FinanceData` with 18 chart datasets, `FinancialBreakdown`, `RecentInvoice`, `TopBuyer`, `HeatmapData`
- Dashboard filters via `BaseDatePicker` (imported but not wired in current UI)

### Public Store Page
- Store preview at `/store/{slug}` — loads store profile, products/services, branches, banner
- Components: `StoreHero`, `StoreBranches`, `StoreInfo`, `StoreProducts`/`StoreServices`, `StoreReviews`, `StoreContactSheet`
- Data fetched from `/api/stores/{slug}`
- "Ver tienda" button on admin store list opens preview

### Admin Store Management
- Store list with search, filter by status (pending/approved/rejected/banned), pagination
- Quick actions: approve/reject, view profile, media management
- `AdminStoreDetail` page with tabs: profile, products, members, contracts, media
- Image gallery viewer modal with navigation
- Pagination with page size selector

### Store Preview Modal
- Clicking any product in store preview opens a product detail modal (image gallery, attributes, price, description)
- Animated transitions with Framer Motion

### Bugs Fixed
- **Dashboard date filter**: changed `BaseDatePicker` `onChange` to return `Date | null` consistently
- **Modal scroll**: removed `overflow-hidden` from `BaseModal` body lock to allow page scroll when modal is open
- **Duration field**: removed `type="number"` restriction on `ServiceSchedule` duration input to allow free text like "1 hora"

## Project Status
- **Fase 1 — Fundación:** ✅ Completada
- **Fase 2 — Comercio (órdenes, inventario, comisiones):** ✅ Completada
- **Fase 3 — WebSockets (Reverb):** ✅ Infraestructura + eventos implementados
- **Fase 4 — Auditoría: Eventos de sistema:** ✅ Completada (Exception Handler, listeners, commands)
- **Fase 5 — Auditoría: Seguridad y Dashboard:** ✅ Completada (AuditSecurityMiddleware, SecurityDashboardController, AuditLog API mejorada, frontend audit page)
- **Fase 6 — Auditoría: Dashboard y retención:** ✅ Completada (archive/purge/summarize commands, audit_log_summaries, AuditCoverageTest, frontend timeline + filtros avanzados)
- **Fase 7 — Auditoría: Esqueletos IPs, Alertas:** ✅ Completada (BlockedIp model + listener, SecurityAlert model + migration + listener, EventServiceProvider registrado)
- **Admin Plans Panel:** ✅ Completado (routes, CRUD, payment history, vendedores, colors)
