# Plan: Módulo /admin/users + Mejoras Operations

**Fecha:** 2026-04-05  
**Repo backend:** `F:\TEST\Backend-Lyrium`  
**Repo frontend:** `F:\FRONTEND\fe-001-marketplace-admin\frontapp`

---

## Contexto

El panel admin tiene 3 pendientes de conexión/creación:

1. **`/admin/users`** — módulo nuevo para gestionar usuarios del marketplace (los 4 roles fijos)
2. **Tab "Roles y Permisos"** en `/admin/operations` — actualmente muestra roles ficticios mock; debe mostrar los 4 roles reales
3. **Tab "Directorio"** en `/admin/operations` — actualmente usa `MOCK_OPERATIONS_DATA`; debe conectarse al modelo `Supplier` del backend

Los roles son **fijos y no dinámicos**: `administrator`, `seller`, `customer`, `logistics_operator`.  
El frontend ya tiene vistas separadas por rol (`/admin`, `/seller`, `/customer`, `/logistics`).

---

## Tarea 1 — Módulo `/admin/users`

### 1.1 Backend — Endpoints faltantes

El `UserController` ya tiene `index`, `byRole`, `show`, `update`, `destroy`.  
Le faltan dos acciones críticas para el módulo de usuarios:

**Agregar a `UserController`:**

```php
// PUT /api/admin/users/{id}/role
public function assignRole(Request $request, int $id): JsonResponse
{
    $request->validate([
        'role' => 'required|in:administrator,seller,customer,logistics_operator',
    ]);
    $user = User::findOrFail($id);
    $user->syncRoles([$request->role]);
    return response()->json(new UserResource($user->fresh()));
}

// PUT /api/admin/users/{id}/ban  (toggle)
public function toggleBan(Request $request, int $id): JsonResponse
{
    // Requiere campo `is_banned` (bool) en la tabla users
    $user = User::findOrFail($id);
    $user->update(['is_banned' => !$user->is_banned]);
    return response()->json(new UserResource($user->fresh()));
}
```

**Agregar a `UserResource`:**
```php
'is_banned'       => (bool) $this->is_banned,
'email_verified'  => !is_null($this->email_verified_at),
'created_at'      => $this->created_at?->toIso8601String(),
'stores_count'    => $this->when($this->role === 'seller', fn() => $this->stores()->count()),
```

**Migración nueva:**
```bash
php artisan make:migration add_is_banned_to_users_table
# $table->boolean('is_banned')->default(false);
```

**Registrar rutas en `routes/api.php`** (dentro del grupo `role:administrator`):
```php
Route::put('/users/{id}/role', [UserController::class, 'assignRole']);
Route::put('/users/{id}/ban',  [UserController::class, 'toggleBan']);
```

---

### 1.2 Frontend — Estructura de archivos a crear

```
src/
├── app/admin/users/
│   └── page.tsx                          ← Server component (solo metadata + wrapper)
│
├── features/admin/users/
│   ├── UsersPageClient.tsx               ← Componente raíz del módulo
│   ├── types.ts                          ← AdminUser interface
│   └── hooks/
│       └── useAdminUsers.ts              ← Estado + llamadas API
│
├── components/admin/users/
│   ├── UsersTable.tsx                    ← Tabla principal con paginación
│   ├── UsersFilters.tsx                  ← Search + filtro por rol + filtro ban
│   ├── UserDetailModal.tsx               ← Modal detalle/edición
│   └── UserRoleBadge.tsx                 ← Badge de color por rol
│
└── lib/api/
    └── userRepository.ts                 ← Llamadas HTTP a /api/users/*
```

---

### 1.3 Frontend — Tipos (`features/admin/users/types.ts`)

```typescript
export type SystemRole = 'administrator' | 'seller' | 'customer' | 'logistics_operator';

export interface AdminUser {
  id: number;
  username: string;
  email: string;
  display_name: string;
  role: SystemRole;
  avatar: string | null;
  phone: string | null;
  document_type: string | null;
  document_number: string | null;
  is_banned: boolean;
  email_verified: boolean;
  created_at: string;
  stores_count?: number;
}

export interface UserFilters {
  search: string;
  role: SystemRole | 'ALL';
  status: 'ALL' | 'active' | 'banned';
}

export interface UsersPagination {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
  hasMore: boolean;
}
```

---

### 1.4 Frontend — Repository (`lib/api/userRepository.ts`)

```typescript
import { apiClient } from './apiClient';
import { AdminUser, SystemRole, UsersPagination } from '@/features/admin/users/types';

interface UsersResponse {
  data: AdminUser[];
  pagination: UsersPagination;
}

export const userRepository = {
  list: (params: { search?: string; role?: string; page?: number; per_page?: number }) =>
    apiClient<UsersResponse>(`/users?${new URLSearchParams(params as any)}`),

  show: (id: number) =>
    apiClient<{ data: AdminUser }>(`/users/${id}`),

  assignRole: (id: number, role: SystemRole) =>
    apiClient<{ data: AdminUser }>(`/users/${id}/role`, {
      method: 'PUT',
      body: { role },
    }),

  toggleBan: (id: number) =>
    apiClient<{ data: AdminUser }>(`/users/${id}/ban`, { method: 'PUT' }),

  delete: (id: number) =>
    apiClient<{ success: boolean }>(`/users/${id}`, { method: 'DELETE' }),
};
```

---

### 1.5 Frontend — UI del módulo

**KPIs (4 tarjetas):** Total usuarios | Sellers activos | Customers | Logistics  
**Filtros:** Buscador (nombre/email) + Selector de rol + Toggle ban/activo  
**Tabla columnas:** Avatar+nombre | Email | Rol (badge color) | Estado (activo/baneado) | Fecha registro | Acciones  
**Acciones por fila:** Ver detalle | Cambiar rol | Ban/Unban | Eliminar  
**Modal detalle:** Info del usuario + historial de stores si es seller

**Colores de rol:**
```
administrator     → purple
seller            → sky
customer          → emerald
logistics_operator→ orange
```

---

## Tarea 2 — Tab "Roles y Permisos" en Operations

### Cambio de enfoque

El tab actualmente muestra roles mock operativos ficticios con botón "Nuevo Rol".  
Como los roles son fijos, se convierte en una vista **informativa** de los 4 roles reales.

### Qué mostrar

Reemplazar `CredentialsTab` para que consuma datos reales del endpoint `GET /api/users/role/{role}` (solo para contar usuarios por rol):

```typescript
// En useGestionOperativa.ts — agregar al fetchData:
const [roleCounts, setRoleCounts] = useState<Record<string, number>>({});

// Llamar los 4 roles en paralelo:
const [admins, sellers, customers, logistics] = await Promise.all([
  userRepository.list({ role: 'administrator', per_page: 1 }),
  userRepository.list({ role: 'seller', per_page: 1 }),
  userRepository.list({ role: 'customer', per_page: 1 }),
  userRepository.list({ role: 'logistics_operator', per_page: 1 }),
]);
setRoleCounts({
  administrator:      admins.pagination.total,
  seller:             sellers.pagination.total,
  customer:           customers.pagination.total,
  logistics_operator: logistics.pagination.total,
});
```

### Nueva UI del tab

4 tarjetas (una por rol) con:
- **Nombre del rol** + ícono
- **Número de usuarios** actuales (dato real de la API)
- **Descripción breve** de qué puede hacer
- **Módulos a los que accede** (lista de badges)
- Sin botón "Nuevo Rol" ni acciones de edición

| Rol | Acceso a módulos |
|-----|-----------------|
| `administrator` | Todos los módulos del panel admin |
| `seller` | Dashboard vendedor, catálogo, órdenes, finanzas, servicios |
| `customer` | Órdenes, wishlist, perfil, soporte, direcciones |
| `logistics_operator` | Helpdesk, chat con vendedores |

### Archivos a modificar

- `src/components/admin/operations/OperationsTabs.tsx` — reemplazar `CredentialsTab`
- `src/features/admin/operations/hooks/useGestionOperativa.ts` — agregar fetch de `roleCounts`
- `src/features/admin/operations/types.ts` — agregar `RoleInfo` type, remover `Credential`

---

## Tarea 3 — Tab "Directorio" conectado al backend (Supplier)

### Estado actual

`useGestionOperativa.ts` usa `MOCK_OPERATIONS_DATA` con un `setTimeout(600ms)`.  
`saveProvider` y `deleteProvider` solo mutan el estado local en memoria.

### Mapeo de campos

El tipo `Provider` del frontend ya coincide casi exactamente con `SupplierResource`:

| Frontend (`Provider`) | Backend (`SupplierResource`) |
|----------------------|------------------------------|
| `id`                 | `id`                         |
| `nombre`             | `nombre`                     |
| `ruc`                | `ruc`                        |
| `tipo`               | `tipo`                       |
| `especialidad`       | `especialidad`               |
| `estado`             | `estado`                     |
| `fecha_renovacion`   | `fechaRenovacion`            |
| `proyectos`          | `proyectos`                  |
| `certificaciones`    | `certificaciones`            |
| `total_recibos`      | `totalRecibos`               |
| `total_gastado`      | `totalGastado`               |

**Acción:** Actualizar el type `Provider` para usar `fechaRenovacion`, `totalRecibos`, `totalGastado` (camelCase) alineado al resource.

### Nuevo repository (`lib/api/supplierRepository.ts`)

```typescript
import { apiClient } from './apiClient';
import { Provider } from '@/features/admin/operations/types';

interface SuppliersResponse {
  data: Provider[];
  meta: { current_page: number; last_page: number; total: number; per_page: number };
}

export const supplierRepository = {
  list: (params?: { search?: string; status?: string; type?: string; per_page?: number }) =>
    apiClient<SuppliersResponse>(`/suppliers?${new URLSearchParams(params as any ?? {})}`),

  create: (data: Partial<Provider>) =>
    apiClient<{ data: Provider }>('/suppliers', { method: 'POST', body: data as any }),

  update: (id: number, data: Partial<Provider>) =>
    apiClient<{ data: Provider }>(`/suppliers/${id}`, { method: 'PUT', body: data as any }),

  delete: (id: number) =>
    apiClient<{ success: boolean }>(`/suppliers/${id}`, { method: 'DELETE' }),
};
```

### Cambios en `useGestionOperativa.ts`

```typescript
// Reemplazar fetchData:
const fetchData = useCallback(async () => {
  setLoading(true);
  try {
    const suppliersRes = await supplierRepository.list({ per_page: 100 });
    setProveedores(suppliersRes.data);
    setError(null);
  } catch {
    setError('Error al cargar proveedores');
  } finally {
    setLoading(false);
  }
}, []);

// Reemplazar saveProvider:
const saveProvider = useCallback(async (providerData: Partial<Provider>) => {
  try {
    if (providerData.id) {
      await supplierRepository.update(providerData.id, providerData);
    } else {
      await supplierRepository.create(providerData);
    }
    await fetchData(); // refetch desde API
    setSelectedProvider(null);
  } catch {
    setError('Error al guardar proveedor');
  }
}, [fetchData]);

// Reemplazar deleteProvider:
const deleteProvider = useCallback(async (provider: Provider) => {
  if (!confirm(`¿Eliminar a "${provider.nombre}"?`)) return;
  try {
    await supplierRepository.delete(provider.id);
    await fetchData(); // refetch desde API
  } catch {
    setError('Error al eliminar proveedor');
  }
}, [fetchData]);
```

### Nota sobre Tab "Gastos"

El backend **no tiene tabla de gastos/recibos** — solo campos agregados `total_gastado` y `total_recibos` en `Supplier`.  
El tab "Gestión de Gastos" seguirá con datos mock hasta que se decida crear el modelo `SupplierExpense`.  
**Queda fuera de este plan.**

---

## Orden de ejecución recomendado

```
1. Backend:  migración is_banned + endpoints assignRole + toggleBan
2. Backend:  registrar rutas nuevas en api.php
3. Frontend: userRepository.ts
4. Frontend: módulo /admin/users (types → hook → components → page)
5. Frontend: supplierRepository.ts
6. Frontend: actualizar useGestionOperativa.ts (Directorio → API real)
7. Frontend: actualizar CredentialsTab → vista informativa 4 roles reales
```

---

## Checklist final

- [ ] Migración `add_is_banned_to_users_table` creada y ejecutada
- [ ] `UserController::assignRole()` implementado
- [ ] `UserController::toggleBan()` implementado
- [ ] Rutas `/users/{id}/role` y `/users/{id}/ban` registradas
- [ ] `UserResource` actualizado con `is_banned`, `email_verified`, `created_at`
- [ ] `userRepository.ts` creado en frontend
- [ ] Módulo `/admin/users` completo (page, hook, table, filters, modal)
- [ ] `supplierRepository.ts` creado en frontend
- [ ] `useGestionOperativa.ts` conectado a API real (Directorio)
- [ ] `CredentialsTab` reemplazado por vista de 4 roles estáticos con conteo real
- [ ] `MOCK_OPERATIONS_DATA` ya no se importa en `useGestionOperativa`