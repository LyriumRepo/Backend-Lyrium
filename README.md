# Backend Lyrium — BioMarketplace API

Backend API para **Lyrium BioMarketplace**, un marketplace multi-vendedor de productos bio/salud en Perú.

## Stack Tecnológico

| Componente | Tecnología |
|-----------|------------|
| Framework | Laravel 12 (PHP 8.2+) |
| Autenticación | Laravel Sanctum (Bearer tokens) |
| Roles y permisos | Spatie Laravel Permission |
| Filtros API | Spatie Laravel Query Builder |
| Base de datos | MySQL (XAMPP) — `db-lyrium` |
| Frontend | Next.js 16 + React 19 + TypeScript (repo separado) |

**Multi-tenancy:** Single database con `store_id` (no base de datos separada por tienda).

## Requisitos

- PHP >= 8.2
- Composer
- MySQL
- Node.js + npm

## Instalación

```bash
# Clonar el repositorio
git clone <url-del-repo>
cd backend-markplace

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# DB_DATABASE=db-lyrium

# Ejecutar migraciones y seeders
php artisan migrate --seed

# O usar el script de setup
composer run setup
```

## Ejecución

```bash
# Servidor de desarrollo (API + queue + logs + Vite)
composer run dev

# O solo el servidor API
php artisan serve
```

La API estará disponible en `http://127.0.0.1:8000/api`

## Estructura del Proyecto

### Modelos (8)

| Modelo | Descripción |
|--------|-------------|
| `User` | Usuarios con roles (admin, seller, customer, logistics_operator) |
| `Store` | Tiendas de vendedores |
| `StoreMember` | Miembros de una tienda |
| `Category` | Categorías de productos (jerárquicas) |
| `Product` | Productos con aprobación admin |
| `ProductAttribute` | Atributos dinámicos de productos |
| `Plan` | Planes de suscripción |
| `Subscription` | Suscripciones de tiendas a planes |

### Controllers

| Controller | Métodos |
|-----------|---------|
| `AuthController` | login, register, registerCustomer, logout, validateToken, refreshToken |
| `UserController` | me, show, index, byRole, update, destroy |
| `StoreController` | index, show, store, update, updateStatus |
| `CategoryController` | index, show, store, update, destroy |
| `ProductController` | index, show, store, update, destroy, updateStock, updateStatus |

### Middleware

| Middleware | Propósito |
|-----------|---------|
| `ForceJson` | Fuerza `Accept: application/json` en todas las API requests |
| `EnsureRole` | Verifica rol del usuario |
| `EnsureStoreApproved` | Verifica que la tienda del seller esté aprobada |

## Endpoints API (29 rutas)

### Públicos (sin autenticación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/login` | Login (email/username + password) |
| POST | `/api/auth/register` | Registro vendedor (storeName, email, phone, password, ruc) |
| POST | `/api/auth/register-customer` | Registro cliente (name, email, password) |
| GET | `/api/categories` | Listar categorías |
| GET | `/api/categories/{id}` | Detalle categoría |
| GET | `/api/products` | Listar productos aprobados |
| GET | `/api/products/{id}` | Detalle producto |

### Autenticados (Bearer Token)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/auth/validate` | Validar token, retorna User |
| POST | `/api/auth/refresh` | Rotar token |
| GET | `/api/users/me` | Perfil del usuario actual |
| GET | `/api/users/{id}` | Ver usuario |
| PUT | `/api/users/{id}` | Actualizar usuario |

### Solo Admin

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/users` | Listar usuarios (paginado, filtros) |
| GET | `/api/users/role/{role}` | Usuarios por rol |
| DELETE | `/api/users/{id}` | Eliminar usuario |
| GET | `/api/stores` | Listar tiendas |
| GET | `/api/stores/{id}` | Detalle tienda |
| PUT | `/api/stores/{id}/status` | Aprobar/rechazar/banear tienda |
| POST | `/api/categories` | Crear categoría |
| PUT | `/api/categories/{id}` | Editar categoría |
| DELETE | `/api/categories/{id}` | Eliminar categoría |
| PUT | `/api/products/{id}/status` | Aprobar/rechazar producto |

### Seller + Admin

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/stores` | Crear tienda |
| PUT | `/api/stores/{id}` | Editar tienda |
| POST | `/api/products` | Crear producto |
| PUT | `/api/products/{id}` | Editar producto |
| DELETE | `/api/products/{id}` | Eliminar producto |
| PUT | `/api/products/{id}/stock` | Actualizar stock |

## Seeders

| Seeder | Datos |
|--------|-------|
| `RoleSeeder` | administrator, seller, customer, logistics_operator |
| `PlanSeeder` | Emprende (5%), Crece (10%), Especial (15%) |
| `AdminUserSeeder` | admin@lyrium.com + vendedor@lyrium.com con tienda aprobada |
| `CategorySeeder` | 8 categorías: Semillas, Fertilizantes, Herramientas, Suplementos, Alimentos Orgánicos, Cuidado Personal, Aceites Esenciales, Productos Naturales |

## Testing

```bash
composer run test
```

## Estado del Proyecto

- **Fase 1 — Fundación:** ✅ Completada
- **Fase 2 — Comercio (órdenes, inventario, comisiones):** 🔜 Pendiente