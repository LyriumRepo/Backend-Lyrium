# Reporte de Prueba: Flujo Tienda -> Convenio -> Activación

- Fecha de ejecución: 2026-04-05 20:59 -05:00
- Entorno: frontend local `http://localhost:3000` + backend local `http://127.0.0.1:8000/api`
- Alcance: validación end-to-end del flujo de registro/aprobación/generación de convenio/subida de firmado/activación

## Caso probado

Se validó el flujo con una tienda recién registrada en entorno local y una cuenta admin local.

Entidades observadas durante la prueba:

- Tienda: `id=12`, nombre `MI CODEX`
- Usuario seller: `id=20`
- Contrato generado: `CTR-2026-003` (`dbId=3`)

## Flujo ejecutado

1. Se ingresó a `/seller/contrato` antes de la aprobación.
2. Se confirmó que la tienda estaba en estado `pending`.
3. Se intentó aprobar la tienda desde admin.
4. La aprobación fue rechazada con `422` por perfil incompleto.
5. Se completaron los campos mínimos requeridos del perfil de tienda:
   - `razon_social`
   - `rep_legal_nombre`
   - `rep_legal_dni`
   - `direccion_fiscal`
6. Se volvió a aprobar la tienda desde admin.
7. Se verificó la generación automática del convenio.
8. Se descargó el documento Word del convenio.
9. Se subió un archivo firmado de prueba.
10. Se activó el convenio desde admin.
11. Se verificó nuevamente `/seller/contrato`.

## Resultados observados

### 1. Antes de aprobar la tienda

- `GET /api/stores/me`: `status = pending`
- `GET /api/contracts/me`: `404 No tienes un contrato generado aún`
- UI en `/seller/contrato`: muestra `Convenio no disponible aún`

### 2. Intento de aprobación con perfil incompleto

- `PUT /api/stores/12/status`
- Resultado: `422 Unprocessable Content`
- Respuesta:
  - `message: No se puede aprobar la tienda: el perfil esta incompleto.`
  - `missing_fields: ["Razon social", "Nombre del representante legal", "DNI del representante legal", "Direccion fiscal"]`

### 3. Aprobación con perfil completo

- La tienda pasó a `status = approved`
- Se generó automáticamente el convenio `CTR-2026-003`
- Auditoría inicial:
  - `Contrato generado automáticamente por aprobación de tienda`

### 4. Convenio pendiente de firma

- `GET /api/contracts/me`: `200`
- Estado observado:
  - `status = PENDING`
  - `has_signed_doc = false`
- UI en `/seller/contrato`:
  - muestra `Pendiente de Firma`
  - permite descargar `.docx`
  - permite subir convenio firmado

### 5. Subida de convenio firmado

- `POST /api/contracts/me/upload-signed`: `200`
- Estado observado:
  - `status = PENDING`
  - `has_signed_doc = true`
  - `signed_file_path` con archivo persistido
- UI en `/seller/contrato`:
  - muestra `Documento firmado recibido`
  - indica espera de validación por admin

### 6. Activación final por admin

- `PUT /api/contracts/3/status` con `ACTIVE`: `200`
- `GET /api/contracts/me`: `200`
- Estado final observado:
  - `status = ACTIVE`
  - `has_signed_doc = true`
- UI en `/seller/contrato`:
  - muestra `Convenio Activo`
  - muestra historial con activación realizada por admin

## Hallazgos detectados durante la prueba

1. El flujo funciona correctamente en local cuando la tienda completa el perfil mínimo antes de ser aprobada.
2. La aprobación de tienda ya no puede ocurrir con perfil incompleto; esto es comportamiento esperado por la validación nueva.
3. Durante la prueba se detectaron dos errores secundarios de frontend:
   - `GET /api/health` devolviendo `404`
   - requests a `/seller/undefined/products?per_page=1` devolviendo `404`
4. Esos dos errores no bloquearon el flujo del convenio, pero sí ensuciaron la consola y el indicador visual de conectividad.

## Estado final de la tienda probada

- Tienda `12`: `approved`
- Contrato `CTR-2026-003`: `ACTIVE`
- Documento firmado: subido
- Historial del contrato:
  - generado automáticamente
  - firmado subido por vendedor
  - activado por admin
