# Integración Rapifac — Facturación Electrónica SUNAT

## Variables de Entorno (`.env`)

| Variable | Formato | Ejemplo (Testing) | Dónde obtenerla |
|---|---|---|---|
| `RAPIFAC_AUTH_URL` | URL completa de autenticación OAuth2 | `https://wsoauth-p1.rapifac.com/oauth2/token` | Documentación API de Rapifac — **Testing** vs **Producción** tienen URLs distintas |
| `RAPIFAC_SALES_URL` | URL base del API de Ventas | `https://wsventas-p1.rapifac.com/v0/comprobantes` | Documentación API de Rapifac — misma distinción Testing/Producción |
| `RAPIFAC_PDF_URL` | URL base para descarga de PDF | `https://wsventas-p1.rapifac.com/v0/comprobantes` | Normalmente igual que `RAPIFAC_SALES_URL`. Si Rapifac da una URL específica para PDF, úsala aquí |
| `RAPIFAC_RUC` | 11 dígitos, sin guiones ni espacios | `20123456789` | Panel Rapifac > Configuración > Datos de la empresa > RUC |
| `RAPIFAC_USER` | String (usuario del API) | `micorreo@ejemplo.com` | Panel Rapifac > Configuración > API > Usuario |
| `RAPIFAC_PASSWORD` | String (contraseña del API) | `•••••••••••••` | Panel Rapifac > Configuración > API > Contraseña |
| `RAPIFAC_BRANCH_ID` | Entero (código de sucursal) | `1` | Panel Rapifac > Sucursales > Código de sucursal |

### Variables opcionales con valor por defecto

| Variable | Default | Descripción |
|---|---|---|
| `RAPIFAC_TIMEOUT` | `30` | Tiempo máximo de espera por respuesta HTTP (segundos) |
| `RAPIFAC_CONNECT_TIMEOUT` | `10` | Tiempo máximo para establecer conexión TCP (segundos) |
| `RAPIFAC_RETRY_ATTEMPTS` | `3` | Reintentos automáticos ante errores transitorios (timeout, 5xx) |

## URLs por Entorno

### Testing
```
RAPIFAC_AUTH_URL=https://wsoauth-p1.rapifac.com/oauth2/token
RAPIFAC_SALES_URL=https://wsventas-p1.rapifac.com/v0/comprobantes
RAPIFAC_PDF_URL=https://wsventas-p1.rapifac.com/v0/comprobantes
```

### Producción
```
RAPIFAC_AUTH_URL=https://wsoauth.rapifac.com/oauth2/token
RAPIFAC_SALES_URL=https://wsventas.rapifac.com/v0/comprobantes
RAPIFAC_PDF_URL=https://wsventas.rapifac.com/v0/comprobantes
```

## Pasos para obtener las credenciales

1. **Inicia sesión** en https://rapifac.com con tu cuenta
2. **Ve a Configuración > API** (o "Desarrolladores" según la versión del panel)
3. **Copia los valores:**
   - **Usuario** → `RAPIFAC_USER`
   - **Contraseña** → `RAPIFAC_PASSWORD`
   - **RUC asociado** → `RAPIFAC_RUC`
4. **Verifica las URLs** en la documentación de Rapifac según tu entorno (testing vs producción)
5. **Sucursal**: si usas una sola, `RAPIFAC_BRANCH_ID=1`. Si tienes varias, cada sucursal tiene su propio código

## Flujo de la integración

```
Vendedor (Frontend)
  │ POST /api/seller/invoices/emit
  ▼
InvoiceController::sellerEmit()
  │
  ├─ Crea Invoice local (sunat_status = DRAFT)
  │
  ├─ RapifacService::emitInvoice()
  │   ├─ ¿Token en caché?   ─NO→ RapifacService::getToken()
  │   │                              └─ POST {auth_url} (grant_type=password)
  │   │                                    └─ Cachea token por 55 min
  │   │
  │   ├─ POST {sales_url} con token Bearer + payload del comprobante
  │   │     └─ Si 401 → flush cache → getToken() → reintenta 1 vez
  │   │
  │   └─ Retorna response de Rapifac (id, pdf_url, authorization_code, etc.)
  │
  ├─ Actualiza Invoice: sunat_status = SENT_WAIT_CDR, pdf_url, etc.
  │
  └─ Retorna InvoiceResource al frontend

Cliente (Frontend)
  │ GET /api/customer/invoices
  ▼
InvoiceController::customerInvoices()
  │
  ├─ Filtra invoices del usuario autenticado (sunat_status = ACCEPTED | SENT_WAIT_CDR)
  │
  └─ Retorna InvoiceResource[] con pdf_url y rapifac_pdf_url

  "Ver PDF" → abre rapifac_pdf_url en nueva pestaña
```

## Manejo de Errores

| Escenario | Excepción | Código HTTP | Mensaje amigable |
|---|---|---|---|
| Credenciales inválidas (401 en auth) | `RapifacException::authError` | 401 | "Error de autenticación con Rapifac. Verifica las credenciales en .env" |
| Token expirado (401 en sales) | Auto-flush + reintento | — | Transparente para el usuario |
| Datos inválidos (422) | `RapifacException::validationError` | 422 | "Datos inválidos enviados a Rapifac: {detalle}" |
| Error interno Rapifac (5xx) | `RapifacException::serverError` | 502 | "Error interno del servidor de Rapifac. Intenta nuevamente." |
| Timeout / conexión fallida | `RapifacException::connectionError` | 503 | "No se pudo conectar con Rapifac. Verifica tu conexión a internet." |

## Cache del Token

- El token OAuth2 se cachea en `cache` (DB/file/redis según `CACHE_STORE`)
- TTL: 3300 segundos (55 min) — el token de Rapifac dura 1 hora
- Si se recibe un 401 al emitir, el servicio automáticamente:
  1. Invalida el cache (`flushToken()`)
  2. Solicita un nuevo token
  3. Reintenta la operación una vez
- Para forzar invalidación manual: `php artisan tinker` → `app(RapifacService::class)->flushToken()`

## Prueba de conexión

Puedes verificar la conexión con Rapifac usando tinker:

```bash
php artisan tinker
```

```php
$service = app(App\Services\RapifacService::class);
$token = $service->getToken(); // Lanza excepción si falla
echo "Token OK: " . substr($token, 0, 20) . "...\n";
```
