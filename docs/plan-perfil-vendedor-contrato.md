# Plan: Completar Perfil del Vendedor antes de Generar Contrato

## Contexto

Al registrarse, el vendedor solo proporciona: nombre de tienda, email, RUC y password.
El contrato digital generado al aprobar la tienda requiere campos adicionales que actualmente son `nullable` en la DB y pueden quedar como `—` en el documento legal.

**Campos críticos para el contrato (ya existen en DB y frontend, pero no se validan):**
- `razon_social` — Razón social de la empresa
- `rep_legal_nombre` — Nombre del representante legal
- `rep_legal_dni` — DNI del representante legal
- `direccion_fiscal` — Dirección fiscal registrada ante SUNAT

---

## Tarea 1 — Backend: `isProfileComplete()` en Store

**Archivo:** `app/Models/Store.php`

Añadir método que retorna `true` si los 4 campos críticos están rellenos:

```php
public function isProfileComplete(): bool
{
    return filled($this->razon_social)
        && filled($this->rep_legal_nombre)
        && filled($this->rep_legal_dni)
        && filled($this->direccion_fiscal);
}
```

Añadir también un helper que retorna qué campos faltan:

```php
public function missingProfileFields(): array
{
    $missing = [];
    if (!filled($this->razon_social))    $missing[] = 'Razón Social';
    if (!filled($this->rep_legal_nombre)) $missing[] = 'Nombre del Representante Legal';
    if (!filled($this->rep_legal_dni))    $missing[] = 'DNI del Representante Legal';
    if (!filled($this->direccion_fiscal)) $missing[] = 'Dirección Fiscal';
    return $missing;
}
```

---

## Tarea 2 — Backend: Bloquear aprobación si perfil incompleto

**Archivo:** `app/Http/Controllers/Api/StoreController.php` — método `updateStatus()`

Antes de generar el contrato, verificar perfil:

```php
if ($status === 'approved') {
    if (!$store->isProfileComplete()) {
        return response()->json([
            'message' => 'No se puede aprobar la tienda: el perfil está incompleto.',
            'missing_fields' => $store->missingProfileFields(),
        ], 422);
    }
    $this->generateContractForStore($store->fresh());
}
```

---

## Tarea 3 — Backend: Exponer `profile_complete` en StoreResource

**Archivo:** `app/Http/Resources/StoreResource.php`

Añadir al array de respuesta:

```php
'profile_complete'     => $this->isProfileComplete(),
'missing_profile_fields' => $this->missingProfileFields(),
```

Esto permite que el admin panel muestre el estado de completitud sin hacer llamadas extras.

---

## Tarea 4 — Admin Panel: Indicador visual en lista de tiendas pendientes

**Archivo:** `src/features/admin/sellers/` (componente de lista/tabla de tiendas)

En la fila de cada tienda con `status = 'pending'`, mostrar una etiqueta:

- ✅ **Perfil completo** — Admin puede aprobar, generará contrato correcto
- ⚠️ **Perfil incompleto** — Mostrar los campos que faltan en tooltip/badge

Datos ya disponibles via `store.profile_complete` y `store.missing_profile_fields` desde el `StoreResource`.

Si el admin intenta aprobar una tienda con perfil incompleto, mostrar el error del backend con los campos faltantes en un toast/modal.

---

## Tarea 5 — Seller Panel: Checklist de pasos de incorporación

**Archivo nuevo:** `src/app/seller/pending/page.tsx` (ya existe — ampliar)

Convertir la página de espera en un checklist de 4 pasos:

```
[✅] 1. Registro completado
[🔲] 2. Completa tu perfil empresarial  →  [Ir a Mi Perfil]
[🔲] 3. Revisión por Lyrium (72h hábiles)
[🔲] 4. Firma el convenio digital
```

El paso 2 se marca como completado cuando `store.profile_complete === true`.
El paso 3 se marca cuando `store.status !== 'pending'`.
El paso 4 se marca cuando `contract.status === 'ACTIVE'`.

---

## Tarea 6 — Seller Panel: Banner de alerta en rutas libres

**Archivo:** `src/app/seller/SellerLayoutClient.tsx`

Ya existe un banner amber para contrato pendiente. Añadir un banner similar para perfil incompleto que sea visible en `/seller/store` y `/seller/profile`:

```
⚠️ Tu perfil está incompleto. 
Lyrium no podrá aprobar tu tienda ni generar tu convenio hasta que completes:
• Razón Social  • Representante Legal  • DNI  • Dirección Fiscal
[Completar ahora →]
```

---

## Orden de ejecución

1. **Tarea 1** — `Store::isProfileComplete()` + `missingProfileFields()`
2. **Tarea 2** — Bloqueo en `StoreController::updateStatus()`
3. **Tarea 3** — `StoreResource` expone `profile_complete`
4. **Tarea 4** — Admin panel: badge en lista de tiendas pendientes
5. **Tarea 5** — Seller panel: checklist en `/seller/pending`
6. **Tarea 6** — Seller panel: banner de perfil incompleto

---

## Verificación

- Registrar tienda nueva → sin llenar perfil → admin intenta aprobar → recibe 422 con campos faltantes
- Vendedor llena perfil → admin aprueba → contrato se genera con todos los datos correctos
- `/seller/pending` muestra los 4 pasos con estado real
- Admin ve badge "perfil incompleto" en tiendas pendientes sin datos

---

## Archivos a modificar/crear

### Backend — `F:\TEST\Backend-Lyrium`

| Archivo | Tarea | Tipo |
|---|---|---|
| `app/Models/Store.php` | 1 | Modificar — añadir `isProfileComplete()` y `missingProfileFields()` |
| `app/Http/Controllers/Api/StoreController.php` | 2 | Modificar — bloquear `updateStatus()` si perfil incompleto |
| `app/Http/Resources/StoreResource.php` | 3 | Modificar — exponer `profile_complete` y `missing_profile_fields` |

### Frontend — `F:\FRONTEND\fe-001-marketplace-admin\frontapp`

| Archivo | Tarea | Tipo |
|---|---|---|
| `src/features/admin/sellers/SellersPageClient.tsx` | 4 | Modificar — badge de perfil completo/incompleto en tabla de tiendas pendientes + manejar error 422 al aprobar |
| `src/shared/lib/api/sellerRepository.ts` | 4, 5 | Modificar — asegurar que `profile_complete` y `missing_profile_fields` se incluyen en el tipo de respuesta de store |
| `src/features/seller/store/hooks/useSellerStore.ts` | 5, 6 | Modificar — exponer `profile_complete` al componente para checklist y banner |
| `src/app/seller/pending/page.tsx` | 5 | Modificar — convertir en checklist de 4 pasos con estado real |
| `src/app/seller/SellerLayoutClient.tsx` | 6 | Modificar — añadir banner de perfil incompleto junto al banner de contrato pendiente |
