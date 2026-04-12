# Railway Produccion Completa

Si quieres probar en produccion algo mas cercano a tu entorno local, no basta con desplegar solo `app`.

Servicios recomendados:

- `app`: API publica
- `worker`: procesa colas
- `cron`: ejecuta scheduler
- `reverb`: opcional, pero necesario si quieres chat o notificaciones en tiempo real

## 1. Base de datos

Crea un servicio MySQL en Railway.

Si el servicio se llama `MySQL`, usa:

```env
DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
```

No mezcles eso con variables locales como estas:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db-lyriumv1
DB_USERNAME=root
DB_PASSWORD=
```

Si usas `DB_URL`, esas variables no hacen falta para Railway y es mejor no ponerlas para evitar confusion.

## 2. Servicio app

### Paso a paso en Railway

1. Entra a tu servicio `app`.
2. Abre la pestaña `Settings`.
3. En `Build`, deja `Builder = Railpack`.
4. En `Build Command`, pega:

```bash
php artisan storage:link --force && php artisan config:cache && php artisan view:cache && npm run build
```

5. Busca `Root Directory` y coloca:

```text
Backend-Lyrium
```

6. En `Deploy -> Pre-deploy Command`, pega:

```bash
php artisan migrate --force && php artisan db:seed --force
```

7. En `Deploy -> Custom Start Command`, dejalo vacio.
8. En `Healthcheck Path`, coloca:

```text
/up
```

9. Ve a `Variables`.
10. Abre `Raw Editor`.
11. Pega el bloque de variables que aparece mas abajo en este archivo.
12. Elimina variables locales si las hubieras puesto antes:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

13. Vuelve a `Settings`.
14. Entra a `Networking -> Public Networking`.
15. Haz clic en `Generate Domain`.
16. Cuando el dominio ya exista, haz `Deploy` o `Redeploy`.
17. Al terminar, prueba:

- `https://tu-dominio/up`

18. Si `/up` responde correctamente, prueba tu frontend local contra el backend en Railway.

### Settings

- `Root Directory`: `Backend-Lyrium`
- `Build Command`: `php artisan storage:link --force && php artisan config:cache && php artisan view:cache && npm run build`
- `Pre-deploy Command`: `php artisan migrate --force && php artisan db:seed --force`
- `Healthcheck Path`: `/up`
- `Start Command`: dejar vacio o autodetect

### Variables

```env
APP_NAME=Lyrium BioMarketplace
APP_ENV=production
APP_DEBUG=false
APP_KEY=PEGA_AQUI_TU_APP_KEY
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
FRONTEND_URL=http://localhost:3000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_PE
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}
RAILPACK_PHP_EXTENSIONS=exif,gd

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public
BROADCAST_CONNECTION=log

MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@flexishopp.site
MAIL_FROM_NAME=Lyrium BioMarketplace
RESEND_API_KEY=PEGA_AQUI_TU_RESEND_API_KEY

GOOGLE_CLIENT_ID=PEGA_AQUI_TU_GOOGLE_CLIENT_ID
```

No hace falta usar `NIXPACKS_BUILD_CMD` si ya configuraste el `Build Command` en Railway.
Si te falla el build por `ext-exif`, deja tambien `RAILPACK_PHP_EXTENSIONS=exif` en Railway para forzar la instalacion de esa extension.

## 3. Servicio worker

Duplica el servicio y usa:

- Al crear el servicio en Railway, elige `GitHub Repository`
- Selecciona el mismo repo del backend
- `Root Directory`: `Backend-Lyrium`
- `Build Command`: dejar vacio
- `Start Command`: `chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh`
- sin dominio publico

Debe compartir las mismas variables de `app`.

## 4. Servicio cron

Duplica el servicio y usa:

- Al crear el servicio en Railway, elige `GitHub Repository`
- Selecciona el mismo repo del backend
- `Root Directory`: `Backend-Lyrium`
- `Build Command`: dejar vacio
- `Start Command`: `chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh`
- sin dominio publico

Debe compartir las mismas variables de `app`.

## 5. Servicio reverb

Agregalo solo si necesitas tiempo real.

- Al crear el servicio en Railway, elige `GitHub Repository`
- Selecciona el mismo repo del backend
- `Root Directory`: `Backend-Lyrium`
- `Build Command`: dejar vacio
- `Start Command`: `chmod +x ./railway/run-reverb.sh && sh ./railway/run-reverb.sh`
- con dominio publico propio

Variables adicionales:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=PEGA_AQUI_TU_REVERB_APP_ID
REVERB_APP_KEY=PEGA_AQUI_TU_REVERB_APP_KEY
REVERB_APP_SECRET=PEGA_AQUI_TU_REVERB_APP_SECRET

REVERB_HOST=${{reverb.RAILWAY_PUBLIC_DOMAIN}}
REVERB_PORT=443
REVERB_SCHEME=https
```

Si activas `reverb`, esas variables deben existir tambien en `app`.

### Cuando ya generaste el dominio de Reverb

Si tu dominio publico de Reverb es, por ejemplo:

```text
servicio-reverb-production-60eb.up.railway.app
```

entonces no debes usar `:8080` en las variables publicas.

Usa asi:

```env
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

El puerto `8080` queda como puerto interno del proceso. El cliente externo debe entrar por `443`.

### Variables minimas en el servicio reverb

```env
APP_NAME=Lyrium BioMarketplace
APP_ENV=production
APP_DEBUG=false
APP_KEY=TU_APP_KEY

APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

REVERB_APP_ID=TU_REVERB_APP_ID
REVERB_APP_KEY=TU_REVERB_APP_KEY
REVERB_APP_SECRET=TU_REVERB_APP_SECRET
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

### Cambios que debes hacer tambien en app

Cuando `reverb` ya exista, en el servicio `app` cambia:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=TU_REVERB_APP_ID
REVERB_APP_KEY=TU_REVERB_APP_KEY
REVERB_APP_SECRET=TU_REVERB_APP_SECRET
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

Despues haz `Redeploy` en `reverb` y tambien en `app`.

### Frontend local apuntando a Railway

En tu frontend local `F:\FRONTEND\fe-001-marketplace-admin\frontapp\.env.local`, deja al menos:

```env
NEXT_PUBLIC_API_MODE=laravel
NEXT_PUBLIC_API_BACKEND=laravel
NEXT_PUBLIC_LARAVEL_API_URL=https://TU-DOMINIO-DEL-APP.up.railway.app/api
NEXT_PUBLIC_GOOGLE_CLIENT_ID=TU_GOOGLE_CLIENT_ID

NEXT_PUBLIC_REVERB_APP_KEY=TU_REVERB_APP_KEY
NEXT_PUBLIC_REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
NEXT_PUBLIC_REVERB_PORT=443
NEXT_PUBLIC_REVERB_SCHEME=https
```

Luego reinicia tu frontend local para que tome las nuevas variables.

## Caso comun: copiar variables de app en reverb

Si en el servicio `reverb` copiaste casi exactamente las variables de `app`, entonces esta mal.

Errores tipicos:

- `APP_URL=https://https://...` esta mal formado
- `DB_URL` usando `hopper.proxy.rlwy.net` no es lo ideal dentro de Railway
- `BROADCAST_CONNECTION=log` esta mal para `reverb`
- faltan todas las variables `REVERB_*`

### Bloque correcto para el servicio reverb

Pega esto en `Raw Editor` del servicio `reverb`, sin comillas:

```env
APP_NAME=Lyrium BioMarketplace
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:base64:HrWGIL7FjNIssHUFDhc2PGuEj+XZl2N5JZIShyNvSlo=

APP_URL=https://servicio-reverb-production-60eb.up.railway.app
FRONTEND_URL=http://localhost:3000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_PE
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_URL=mysql://root:FeXLXTtsdFTaCCYbWhURZyRZmUiCoDLJ@hopper.proxy.rlwy.net:52381/railway

RAILPACK_PHP_EXTENSIONS=exif,gd

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=383068
REVERB_APP_KEY=qzg5p7xmovk4uthurig0
REVERB_APP_SECRET=j9k5e41ilpf0z0owdyei
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https

MAIL_MAILER="resend"
MAIL_FROM_ADDRESS="noreply@flexishopp.site"
MAIL_FROM_NAME="Lyrium BioMarketplace"
RESEND_API_KEY="re_Ff1xHwG1_Hw9YNgRchuYyQrYr8rgyeDm7"
GOOGLE_CLIENT_ID="363073868682-tvls3e7t1lmr101js5sah0phn5aueeja.apps.googleusercontent.com"

### Nota importante

Despues de corregir `reverb`, tambien debes actualizar el servicio `app` para que use:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=TU_REVERB_APP_ID
REVERB_APP_KEY=TU_REVERB_APP_KEY
REVERB_APP_SECRET=TU_REVERB_APP_SECRET
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

Y luego hacer `Redeploy` en ambos servicios.

## De donde salen REVERB_APP_ID, REVERB_APP_KEY y REVERB_APP_SECRET

Railway no genera esos valores por ti.

Tienes dos opciones:

1. Reusar los mismos valores que ya usabas en local.
2. Generar nuevos valores para produccion.

Lo importante es esto:

- `app` y `reverb` deben tener exactamente los mismos valores
- el frontend solo necesita `NEXT_PUBLIC_REVERB_APP_KEY`
- nunca pongas `REVERB_APP_SECRET` en el frontend

Si quieres reutilizar los de tu entorno local, puedes tomar los mismos que ya usabas antes.

Ejemplo:

```env
REVERB_APP_ID=383068
REVERB_APP_KEY=qzg5p7xmovk4uthurig0
REVERB_APP_SECRET=j9k5e41ilpf0z0owdyei
```

## Bloque minimo para app con Reverb activo

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=383068
REVERB_APP_KEY=qzg5p7xmovk4uthurig0
REVERB_APP_SECRET=j9k5e41ilpf0z0owdyei
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

## Bloque minimo para reverb

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=383068
REVERB_APP_KEY=qzg5p7xmovk4uthurig0
REVERB_APP_SECRET=j9k5e41ilpf0z0owdyei
REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
REVERB_PORT=443
REVERB_SCHEME=https
```

## Bloque minimo para el frontend local

En `F:\FRONTEND\fe-001-marketplace-admin\frontapp\.env.local`:

```env
NEXT_PUBLIC_API_MODE=laravel
NEXT_PUBLIC_API_BACKEND=laravel
NEXT_PUBLIC_LARAVEL_API_URL=https://TU-DOMINIO-DEL-APP.up.railway.app/api
NEXT_PUBLIC_GOOGLE_CLIENT_ID=363073868682-tvls3e7t1lmr101js5sah0phn5aueeja.apps.googleusercontent.com

NEXT_PUBLIC_REVERB_APP_KEY=qzg5p7xmovk4uthurig0
NEXT_PUBLIC_REVERB_HOST=servicio-reverb-production-60eb.up.railway.app
NEXT_PUBLIC_REVERB_PORT=443
NEXT_PUBLIC_REVERB_SCHEME=https
```

Despues:

1. guarda variables en `reverb`
2. haz `Redeploy` en `reverb`
3. guarda variables en `app`
4. haz `Redeploy` en `app`
5. reinicia el frontend local

## 6. APP_URL y dominio

Puedes dejar:

```env
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
```

pero antes de probar desde fuera debes generar el dominio publico del servicio:

1. `Settings`
2. `Networking`
3. `Public Networking`
4. `Generate Domain`

Si generas el dominio despues y notas valores cacheados, haz redeploy.

## 7. Storage

Para archivos publicos, monta un `Volume` en el servicio `app` para persistir:

```text
storage/app/public
```

Sin eso, los archivos pueden perderse al redeploy.

## Nota sobre la UI de Railway

En la pantalla `Settings` del servicio:

- `Build -> Build Command`: pega el comando de build
- `Deploy -> Pre-deploy Command`: pega `php artisan migrate --force`
- `Deploy -> Custom Start Command`: dejalo vacio para `app`

Y si ves `npm run migrate` ahora mismo, cambialo. Ese script no existe en [package.json](/F:/TEST/Backend-Lyrium/package.json#L5).

Tampoco recomiendo usar `storage:link`, `chmod` o caches dentro de `Pre-deploy Command`, porque Railway indica que el pre-deploy corre en un contenedor separado y los cambios de filesystem no persisten.

## Que opcion elegir al crear servicios

Cuando crees `worker`, `cron` o `reverb` en Railway, elige:

- `GitHub Repository`

No elijas:

- `Database`
- `Template`
- `Docker Image`
- `Function`
- `Bucket`
- `Volume`
- `Empty Service`

La razon es que `worker`, `cron` y `reverb` salen del mismo codigo Laravel del backend.
