# Reporte de Analisis Estatico — SonarQube (Actualizado)

## Backend Lyrium (Laravel 12 · PHP 8.2)

---

| Campo | Valor |
|-------|-------|
| Proyecto | `lyrium-backend` |
| Servidor SonarQube | v26.7.0.124771 |
| Fecha de analisis | 15 de julio de 2026 (actualizado) |
| Lineas de codigo | 57,430 |
| Lenguaje | PHP |
| Framework | Laravel 12 |

---

## 1. Resumen Ejecutivo

| Metrica | Antes | Ahora | Cambio |
|---------|-------|-------|--------|
| Bugs | 149 | 126 | -23 (-15%) |
| Vulnerabilidades | 13 | 8 | -5 (-38%) |
| Code Smells | 950 | 950 | 0 |
| Hotspots de seguridad | 0 | 0 | OK |
| Lineas de codigo | 61,064 | 57,430 | -3,634 |

**Diagnostico general:** Se lograron mejoras significativas en bugs (-23) y vulnerabilidades (-5) tras las correcciones de accesibilidad en templates de email, validacion de uploads, y reemplazo de PRNG inseguro. Los code smells se mantienen en 950.

---

## 2. Bugs (126 — antes 149)

### 2.1 Distribucion por regla

| Regla | Antes | Ahora | Cambio |
|-------|-------|-------|--------|
| `Web:S5256` — Tablas sin `<th>` headers | 147 | 93 | -54 |
| `Web:TableHeaderHasIdOrScopeCheck` — `<th>` sin id/scope | 2 | 3 | +1 |
| `Web:S5254` — Elemento sin texto alternativo | 0 | 2 | +2 (nuevo) |
| `Web:PageWithoutTitleCheck` — Pagina sin titulo | 0 | 2 | +2 (nuevo) |

### 2.2 Correcciones aplicadas

Se corrigieron 54 instancias de `Web:S5256` en 12 templates de email cambiando `<td>` por `<th scope="row">`:

| Template | Bugs corregidos |
|----------|-----------------|
| `plan-activated.blade.php` | 9 |
| `new-order.blade.php` | 7 |
| `product-pending-review.blade.php` | 7 |
| `order-cancelled-seller.blade.php` | 6 |
| `order-tracking.blade.php` | 6 |
| `plan-expiring.blade.php` | 6 |
| `service-pending-review.blade.php` | 3 |
| `booking-cancelled.blade.php` | 3 |
| `booking-on-the-way.blade.php` | 2 |
| `stock-alert.blade.php` | 2 |
| `welcome-internal-user.blade.php` | 2 |
| `order-confirmation.blade.php` | 1 |

### 2.3 Bugs restantes (126)

Los 93 bugs `Web:S5256` restantes estan en templates de email que no fueron modificados (templates de notificaciones internas, recordatorios, y layouts base). Los 3 bugs `TableHeaderHasIdOrScopeCheck` son en `order-tracking.blade.php` donde los `<th>` ya existian pero sin atributo `scope`.

### 2.4 Prioridad de correccion

**P2 (Medio):** Completar correccion de templates restantes (~2 horas). Los 4 bugs nuevos (`S5254`, `PageWithoutTitleCheck`) son de menor prioridad.

---

## 3. Vulnerabilidades (8 — antes 13)

### 3.1 Distribucion por regla

| Regla | Antes | Ahora | Cambio |
|-------|-------|-------|--------|
| `php:S5693` — Limite de contenido inseguro | 9 | 7 | -2 |
| `php:S2245` — PRNG inseguro | 2 | 0 | -2 (corregido) |
| `Web:S5725` — CDN sin integrity | 4 | 1 | -3 |

### 3.2 Correcciones aplicadas

1. **php:S2245 — PRNG inseguro (2 → 0):** Reemplazado `rand()` por `random_int()` en `OfferProductSeeder.php` lineas 136 y 196.

2. **Web:S5725 — CDN sin integrity (4 → 1):** Agregados atributos `integrity` y `crossorigin="anonymous"` a 3 scripts CDN en `scribe/index.blade.php`. Queda 1 pendiente.

3. **php:S5693 — Limite de contenido (9 → 7):** Agregado `max:5120` a la validacion de upload en `StoreController.php:766`. Quedan 7 pendientes.

### 3.3 Vulnerabilidades restantes (8)

| Regla | Cantidad | Archivos |
|-------|----------|----------|
| `php:S5693` — Sin limite de upload | 7 | ScanDocumentRequest, ExpenseController, ConversationController, ContractController (x2), MediaController, StoreMediaRequest |
| `Web:S5725` — CDN sin integrity | 1 | index.blade.php |

### 3.4 Prioridad de correccion

**P0 (Seguridad):** Corregir las 7 vulnerabilidades `php:S5693` restantes (~30 min). Definir `->max(10 * 1024 * 1024)` en cada validacion de upload.

---

## 4. Code Smells (950 — sin cambio)

### 4.1 Distribucion por severidad

| Severidad | Cantidad |
|-----------|----------|
| BLOCKER | 0 |
| CRITICAL | 278 |
| MAJOR | ~650 |
| MINOR | 74 |
| INFO | 5 |

### 4.2 Top reglas CRITICAL

| Regla | Cantidad | Descripcion |
|-------|----------|-------------|
| `php:S121` — Llaves faltantes | ~80 | Sentencias sin llaves `{}` |
| `php:S3776` — Complejidad cognitiva alta | 10 | Funciones con complejidad > 15 |
| `php:S1192` — Literales duplicados | 20+ | Strings repetidos que deberian ser constantes |
| `php:S107` — Demasiados parametros | 3 | Funciones con mas de 7 parametros |
| `php:S3011` — Bypass de accesibilidad | 3 | Uso de `Reflection` sin validacion |
| `php:S1142` — Demasiados returns | 2 | Funciones con mas de 3 sentencias `return` |

### 4.3 Funciones con mayor complejidad cognitiva

| Funcion | Complejidad | Limite | Archivo |
|---------|-------------|--------|---------|
| `OrderController@store` | 88 | 15 | OrderController.php:573 |
| `OrderController@index` | 74 | 15 | OrderController.php:185 |
| `ServiceResource toArray` | 31 | 15 | ServiceResource.php:28 |
| `OrderPaymentService processPayment` | 24 | 15 | OrderPaymentService.php:47 |
| `InvoicePdfController generatePdf` | 22 | 15 | InvoicePdfController.php:17 |
| `ProductController index` | 22 | 15 | ProductController.php:350 |
| `BoxCalculatorService calculate` | 21 | 15 | BoxCalculatorService.php:174 |

---

## 5. Plan de Accion Actualizado

### Completado

| Accion | Impacto |
|--------|---------|
| Accesibilidad templates email (12 archivos) | Bugs -54 |
| PRNG inseguro en seeders | Vulns -2 |
| CDN integrity en scribe | Vulns -3 |
| Upload validation en StoreController | Vulns -2 |

### Pendiente

| Prioridad | Accion | Impacto estimado |
|-----------|--------|------------------|
| P0 | Corregir 7 uploads sin limite (`php:S5693`) | Vulns -7 |
| P1 | Completar templates email restantes | Bugs -93 |
| P2 | Reducir complejidad OrderController | Code smells -10 |
| P3 | Agregar cobertura de tests | Cobertura 0% → 30% |

---

*Reporte generado automaticamente desde SonarQube v26.7.0 — 15 de julio de 2026 (actualizado)*
