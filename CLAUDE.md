# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Backend Lyrium** — Laravel 12 REST API for a multi-vendor biomarketplace (Peru). PHP 8.2+, MySQL (`db-lyriumv1`), Sanctum auth, Spatie roles/permissions.

Frontend is a separate Next.js repo at `F:\FRONTEND\fe-001-marketplace-admin\frontapp` (expected at `http://localhost:3000`).

## Commands

```bash
composer run setup          # Install deps + migrate + seed + build
composer run dev            # Start server, queue, log viewer, and Vite in parallel
composer run test           # Run PHPUnit tests
php artisan migrate --seed  # Run migrations with seeders
php artisan test --filter=TestName  # Run a single test
php artisan reverb:start --debug    # Start WebSocket server (Reverb) on port 8080
vendor/bin/pint             # Code style fixer (Laravel Pint)
```

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

### Models & Relationships
- **User** → owns many Stores, member of many Stores (via StoreMember pivot); has LoyaltyAccount
- **Store** → has many Products, Services, Branches, Members, Shipments; one latest Subscription; status workflow: `pending → approved/rejected/banned`; profile changes via `StoreProfileRequest`
- **Product** → belongs to Store, many-to-many Categories, has many ProductAttributes; status workflow: `draft → pending_review → approved/rejected`; images via Spatie MediaLibrary
- **Category** — self-referencing hierarchy via `parent_id`; `type` field: `product` | `service`
- **Service** → belongs to Store and Category (type=service); has ServiceSchedules and ServiceBookings
- **Plan/Subscription** — store subscription plans with commission rates; plan upgrades via `PlanRequest` (paid via Izipay webhook)
- **Order/OrderItem** → multi-store orders; each OrderItem has its own `store_id`; has Shipment, Invoice, Returns, Disputes
- **ServiceBooking** → belongs to Service (no direct store_id; store accessed via `service.store_id`)
- **Shipment** → tracks shipping events, zones, methods
- **Dispute/DisputeMessage** — order dispute resolution workflow
- **Ticket/TicketMessage** — customer support tickets
- **LoyaltyProgram/LoyaltyTier/LoyaltyTransaction/UserLoyaltyAccount** — points & rewards system
- **Contract/ContractAuditTrail** — admin-managed seller contracts with file uploads
- **Supplier** — internal supplier management (admin)
- **SystemConfig** — key/value config store for dynamic settings
- Soft deletes on User, Store, Product

### Request/Response Pattern
- Validation in `app/Http/Requests/` (FormRequest classes)
- JSON transformation in `app/Http/Resources/` (API Resources)
- Controllers in `app/Http/Controllers/Api/`
- Services in `app/Services/` — business logic layer

### Media Uploads
- **Spatie MediaLibrary** handles all file uploads (product images, store logos/banners/gallery, policy docs)
- Dedicated `MediaController` for store and product media operations

### Routes (`routes/api.php`) — Four access tiers
1. **Public** (no auth): auth, categories, products, services, reviews, plans, shipping methods, home/brands/benefits, SSE events, Izipay webhook
2. **Authenticated** (`auth:sanctum`): profile, notifications, loyalty, cart, orders, bookings, returns, disputes, tickets, search
3. **Admin only** (`role:administrator`): user management, store approval, product status, plan/subscription admin, system config, suppliers, contracts, admin tickets/disputes/payments, loyalty program management
4. **Seller + Admin** (`role:seller,administrator`): store CRUD, product CRUD, service CRUD, shipping config, shipment tracking, return handling, seller payments, seller tickets

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

## Database

### Key Seeders (run order via DatabaseSeeder)
- `RoleSeeder` → 4 roles
- `PlanSeeder` → 3 plans (Emprende 5%, Crece 10%, Especial 15% commission)
- `AdminUserSeeder` → pierre@admin.com / password
- `CategorySeeder` → categorías bio/organic de productos y servicios
- Additional: `BannerSeeder`, `BenefitSeeder`, `BrandSeeder`, `HomeSeeder`, `LoyaltyAndPaymentSeeder`, `ShippingSeeder`, `SystemConfigSeeder`

### Testing
PHPUnit configured with in-memory SQLite (`phpunit.xml`). Test suites: `tests/Feature/`, `tests/Unit/`.

## Environment
Copy `.env.example` to `.env`. Key vars:
- `DB_DATABASE=db-lyriumv1` (MySQL via XAMPP)
- `FRONTEND_URL=http://localhost:3000` (used by CORS config)
- `RESEND_API_KEY` — email (OTP, notifications)
- `GOOGLE_CLIENT_ID` — OAuth
- `IZIPAY_*` — payment gateway for plan subscriptions
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` — WebSocket server
- `REVERB_HOST=localhost`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`

## Project Status
- **Fase 1 — Fundación:** ✅ Completada
- **Fase 2 — Comercio (órdenes, inventario, comisiones):** ✅ Completada
- **Fase 3 — WebSockets (Reverb):** ✅ Infraestructura + eventos implementados
- **Fase 4 — Funcionalidades avanzadas:** 🔜 Pendiente
