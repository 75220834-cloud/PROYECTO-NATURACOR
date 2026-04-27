# 🚀 GUÍA COMPLETA: Desplegar NATURACOR a Producción + Dominio Propio

**Para ti, paso a pasito, como a un bebé 👶**

---

## 💰 RESUMEN DE COSTOS (lo que vas a gastar)

| Concepto | Costo | Frecuencia |
|---|---|---|
| **Railway.app** (hosting) | **$5 USD/mes** (~S/19) | Mensual |
| **Dominio .com** (Porkbun) | **~$10 USD/año** (~S/38) | Anual |
| **TOTAL primer mes** | **~$10.80 USD** (~S/41) | — |
| **TOTAL mensual después** | **$5 USD/mes** (~S/19) | — |

> [!TIP]
> Si quieres un dominio `.pe` (como `naturacor.pe`), cuesta ~$50-80 USD/año en registradores peruanos. Un `.com` es MUCHO más barato y se ve igual de profesional.

---

# 📋 ÍNDICE DE PASOS

```
PARTE 1: Comprar tu dominio propio              (20 min)
PARTE 2: Preparar tu proyecto para producción    (15 min)
PARTE 3: Crear cuenta en Railway y desplegar     (30 min)
PARTE 4: Conectar tu dominio personalizado       (15 min)
PARTE 5: Verificar que todo funciona             (10 min)
```

---

# PARTE 1: COMPRAR TU DOMINIO PROPIO 🌐

## ¿Dónde comprar? — Mi recomendación

| Registrador | Precio .com | Renovación | Privacidad WHOIS | ¿Recomendado? |
|---|---|---|---|---|
| **Porkbun** | ~$9.73/año | ~$9.73/año | ✅ GRATIS | ⭐ **MÁS BARATO** |
| **Namecheap** | ~$9.98/año | ~$13.98/año | ✅ GRATIS 1er año | ✅ Bueno |
| **Cloudflare** | ~$9.77/año | ~$9.77/año | ✅ GRATIS | ✅ Bueno (sin soporte) |

> [!IMPORTANT]
> Yo te recomiendo **Porkbun** — es el más barato, el precio de renovación es igual al de registro (no te suben el precio después), e incluye privacidad WHOIS gratis.

---

### PASO 1.1 — Ir a Porkbun

1. Abre tu navegador
2. Ve a: **https://porkbun.com**
3. La página está en inglés, pero no te preocupes, te guío

### PASO 1.2 — Buscar tu dominio

1. En la barra grande que dice **"Search for a domain"**, escribe el nombre que quieres. Ejemplos:
   - `naturacor.com`
   - `naturacorjauja.com`  
   - `naturacorperu.com`
   - `tiendanaturacor.com`
2. Haz clic en **"Search"** (el botón de buscar)
3. Te mostrará si está **disponible** ✅ o **no disponible** ❌

> [!NOTE]
> Si `naturacor.com` no está disponible, prueba variaciones como `naturacorpe.com`, `naturacorjauja.com`, etc.

### PASO 1.3 — Agregar al carrito

1. Cuando encuentres uno disponible, haz clic en **"Add to cart"** (agregar al carrito)
2. Te aparecerá el precio (debería ser ~$9.73 USD por 1 año)

### PASO 1.4 — Crear cuenta en Porkbun

1. Haz clic en el **carrito** (ícono arriba a la derecha)
2. Haz clic en **"Checkout"** (pagar)
3. Te pedirá crear una cuenta:
   - **Email:** tu correo personal
   - **Password:** una contraseña segura
   - **Name:** tu nombre completo
   - **Address:** tu dirección en Perú
   - **Country:** Peru
   - **Phone:** tu número con código +51
4. Haz clic en **"Create Account"**

### PASO 1.5 — Pagar el dominio

1. Selecciona método de pago:
   - **Tarjeta de débito/crédito** (Visa, Mastercard) ← la más fácil
   - **PayPal** ← si tienes
2. Ingresa los datos de tu tarjeta
3. Haz clic en **"Complete Order"** (completar orden)
4. ✅ ¡Listo! Ya tienes tu dominio comprado

> [!CAUTION]
> **GUARDA bien** tu usuario y contraseña de Porkbun. Lo vas a necesitar más adelante para configurar los DNS.

### PASO 1.6 — Verificar la compra

1. Te llegará un **email de confirmación** a tu correo
2. En Porkbun, ve a **"Domain Management"** (Gestión de dominios)
3. Deberías ver tu dominio listado ahí con estado **"Active"**

---

# PARTE 2: PREPARAR TU PROYECTO PARA PRODUCCIÓN 🔧

### PASO 2.1 — Crear el archivo Procfile

1. Abre tu proyecto en VS Code
2. En la **raíz** del proyecto (donde está [composer.json](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/composer.json)), crea un archivo nuevo
3. El archivo se llama exactamente: **`Procfile`** (sin extensión, con P mayúscula)
4. Dentro escribe **exactamente** esto:

```
web: php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
```

5. Guarda el archivo (Ctrl+S)

### PASO 2.2 — Crear el archivo nixpacks.toml

1. En la **misma carpeta raíz** del proyecto, crea otro archivo nuevo
2. El archivo se llama: **`nixpacks.toml`**
3. Dentro escribe **exactamente** esto:

```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo_mysql", "php82Extensions.pdo_sqlite", "php82Extensions.mbstring", "php82Extensions.curl", "php82Extensions.fileinfo", "php82Extensions.xml", "php82Extensions.bcmath", "php82Extensions.tokenizer"]

[phases.install]
cmds = [
    "composer install --optimize-autoloader --no-dev",
    "npm ci",
    "npm run build"
]

[phases.build]
cmds = [
    "php artisan key:generate --force",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]

[start]
cmd = "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"
```

4. Guarda el archivo (Ctrl+S)

### PASO 2.3 — Subir estos archivos a GitHub

1. Abre tu **terminal** (CMD o PowerShell) en la carpeta del proyecto
2. Ejecuta estos comandos **uno por uno**:

```bash
git add Procfile nixpacks.toml
```

```bash
git commit -m "feat: agregar archivos de despliegue para Railway"
```

```bash
git push origin main
```

3. ✅ Listo, tu código ya está actualizado en GitHub

---

# PARTE 3: CREAR CUENTA EN RAILWAY Y DESPLEGAR ☁️

### PASO 3.1 — Crear cuenta en Railway

1. Abre tu navegador
2. Ve a: **https://railway.app**
3. Haz clic en **"Login"** (arriba a la derecha)
4. Haz clic en **"Login with GitHub"** (iniciar sesión con GitHub)
5. Autoriza Railway para acceder a tu GitHub
6. ✅ Ya tienes cuenta en Railway

### PASO 3.2 — Activar el plan Hobby ($5/mes)

1. Una vez dentro, haz clic en tu **avatar/foto** (arriba a la derecha)
2. Ve a **"Account Settings"** → **"Billing"**
3. Selecciona el plan **"Hobby"** ($5/mes)
4. Ingresa los datos de tu **tarjeta de débito/crédito**
5. Confirma la suscripción
6. ✅ Plan activado

> [!IMPORTANT]
> **Sin plan Hobby no puedes desplegar ni usar dominio personalizado.** Los $5/mes incluyen $5 de crédito de uso, así que si tu app usa poco recursos, solo pagas esos $5.

### PASO 3.3 — Crear un nuevo proyecto

1. En el dashboard de Railway, haz clic en **"+ New Project"** (nuevo proyecto)
2. Selecciona **"Deploy from GitHub Repo"** (desplegar desde repo de GitHub)
3. Busca y selecciona tu repositorio: **`75220834-cloud/PROYECTO-NATURACOR`**
4. Railway empezará a configurar tu proyecto
5. **⚠️ VA A FALLAR** — es normal, porque falta configurar las variables y la base de datos

### PASO 3.4 — Agregar base de datos MySQL

1. Dentro de tu proyecto en Railway, haz clic en **"+ New"** (el botón de Nueva)
2. Selecciona **"Database"** → **"MySQL"**
3. Railway creará la base de datos automáticamente (espera ~30 segundos)
4. Haz clic en la **base de datos MySQL** que se acaba de crear
5. Ve a la pestaña **"Variables"**
6. 📝 **ANOTA estos valores** (los vas a necesitar en el siguiente paso):
   - `MYSQL_HOST` (algo como `monorail.proxy.rlwy.net`)
   - `MYSQL_PORT` (algo como `12345`)
   - `MYSQL_DATABASE` (algo como `railway`)
   - `MYSQL_USER` (algo como `root`)
   - `MYSQL_PASSWORD` (algo como `abc123xyz`)

### PASO 3.5 — Configurar las variables de entorno

1. Regresa a tu **servicio principal** (el que tiene el ícono de GitHub, NO la base de datos)
2. Haz clic en él
3. Ve a la pestaña **"Variables"**
4. Haz clic en **"Raw Editor"** (editor de texto sin formato)
5. **Borra todo** lo que haya ahí
6. Pega **exactamente** esto, pero **CAMBIANDO los valores marcados con ← CAMBIAR**:

```env
APP_NAME=NATURACOR
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-dominio.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
DB_HOST=pon_aqui_el_MYSQL_HOST
DB_PORT=pon_aqui_el_MYSQL_PORT
DB_DATABASE=pon_aqui_el_MYSQL_DATABASE
DB_USERNAME=pon_aqui_el_MYSQL_USER
DB_PASSWORD=pon_aqui_el_MYSQL_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=480
QUEUE_CONNECTION=sync
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@naturacor.pe"
MAIL_FROM_NAME=NATURACOR

GROQ_API_KEY=gsk_TU_API_KEY_AQUI

STOCK_MINIMO_DEFAULT=5
IGV_PORCENTAJE=18
FIDELIZACION_MONTO=500
FIDELIZACION_MAXIMO_PREMIO=30
FIDELIZACION_CORDIALES_MONTO=500
FIDELIZACION_INICIO=2026-01-01
FIDELIZACION_FIN=2026-12-31
```

7. **Reemplaza** los valores de `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` con los que anotaste en el paso anterior
8. **Reemplaza** `APP_URL` con tu dominio (ejemplo: `https://naturacor.com`)
9. Haz clic en **"Update Variables"** o **"Apply Changes"**

> [!CAUTION]
> En `GROQ_API_KEY` pon tu API key real. **Si tu .env se subió al historial de Git antes, esa key ya no es segura.** Ve a [console.groq.com](https://console.groq.com), revócala y genera una nueva.

### PASO 3.6 — Re-desplegar la aplicación

1. Después de guardar las variables, Railway **redesplegará automáticamente**
2. Si no lo hace, ve a la pestaña **"Deployments"**
3. Haz clic en **"Redeploy"** o **"Trigger Deploy"**
4. Espera 3-5 minutos mientras se construye
5. Mira los **logs** (registros) en la pestaña "Deployments" para ver el progreso
6. Cuando veas algo como `Server running on [http://0.0.0.0:8080]`, ¡está listo!

### PASO 3.7 — Probar con el dominio temporal de Railway

1. Ve a la pestaña **"Settings"** de tu servicio
2. En la sección **"Networking"** → **"Public Networking"**, haz clic en **"Generate Domain"**
3. Railway te dará un dominio temporal como: `naturacor-production.up.railway.app`
4. Abre esa URL en tu navegador
5. Deberías ver la **página de login de NATURACOR** 🎉

> [!NOTE]
> Este dominio temporal ya funciona. Lo usas para verificar que todo está bien ANTES de conectar tu dominio propio.

---

# PARTE 4: CONECTAR TU DOMINIO PERSONALIZADO 🔗

### PASO 4.1 — Agregar dominio en Railway

1. En Railway, ve a tu **servicio** → pestaña **"Settings"**
2. En la sección **"Networking"** → **"Custom Domains"**
3. Haz clic en **"+ Custom Domain"**
4. Escribe tu dominio: por ejemplo `naturacor.com`
5. Railway te mostrará la información de DNS que necesitas configurar:
   - Un registro **CNAME** con un valor como `cname.railway.app` o similar
   - 📝 **ANOTA** ese valor CNAME, lo necesitas para el siguiente paso

### PASO 4.2 — Configurar DNS en Porkbun

1. Abre otra pestaña del navegador
2. Ve a **https://porkbun.com** e inicia sesión con tu cuenta
3. Ve a **"Domain Management"** (Gestión de dominios)
4. Haz clic en tu dominio (ejemplo: `naturacor.com`)
5. Busca la sección **"DNS Records"** (Registros DNS) o haz clic en **"DNS"**

### PASO 4.3 — Eliminar registros DNS anteriores (si hay)

1. Si ves algún registro tipo **A** o **AAAA** ya existente, **elimínalos** haciendo clic en el ícono de basura/delete
2. Estos registros por defecto apuntan a los servidores de Porkbun y hay que quitarlos

### PASO 4.4 — Agregar el registro CNAME

1. Haz clic en **"Add New Record"** (Agregar nuevo registro) o **"Edit"**
2. Configura así:

| Campo | Valor |
|---|---|
| **Type** (Tipo) | `CNAME` |
| **Host** (Subdominio) | Déjalo **vacío** o pon `@` (esto hace que funcione con `naturacor.com` directamente) |
| **Answer** (Destino) | El valor CNAME que Railway te dio (algo como `cname.railway.app`) |
| **TTL** | `600` (o déjalo en Auto) |

3. Haz clic en **"Save"** (Guardar)

> [!WARNING]
> Algunos registradores no permiten CNAME en el dominio raíz (@). Si Porkbun no te deja, agrega un CNAME para `www` y luego configura un redirect del dominio raíz al www. Porkbun sí soporta **ALIAS/ANAME** que funciona como CNAME en raíz — su interfaz lo maneja automáticamente.

### PASO 4.5 — Agregar también el subdominio www (OPCIONAL pero recomendado)

1. Agrega **otro** registro CNAME:

| Campo | Valor |
|---|---|
| **Type** | `CNAME` |
| **Host** | `www` |
| **Answer** | El mismo valor CNAME de Railway |
| **TTL** | `600` |

2. Guarda

### PASO 4.6 — Esperar la propagación DNS

1. Los cambios de DNS tardan entre **5 minutos y 48 horas** en propagarse
2. Normalmente tarda **15-30 minutos**
3. Para verificar si ya propagó, puedes ir a: **https://dnschecker.org**
   - Escribe tu dominio (ejemplo: `naturacor.com`)
   - Si muestra checkmarks verdes ✅, ya propagó

### PASO 4.7 — Verificar en Railway

1. Regresa a Railway → tu servicio → **Settings** → **Custom Domains**
2. Tu dominio debería mostrar un estado **"Valid"** o un checkmark verde ✅
3. Railway automáticamente genera un certificado **SSL/HTTPS** gratuito para tu dominio

### PASO 4.8 — Actualizar APP_URL en las variables

1. Ve a la pestaña **"Variables"** de tu servicio en Railway
2. Cambia `APP_URL` a tu dominio con https:

```
APP_URL=https://naturacor.com
```

3. Guarda y espera a que redespliegue (~2 minutos)

---

# PARTE 5: VERIFICAR QUE TODO FUNCIONA ✅

### PASO 5.1 — Abrir tu dominio

1. Abre tu navegador
2. Escribe tu dominio: **https://naturacor.com** (o el que compraste)
3. Deberías ver la página de **login de NATURACOR** 🎉

### PASO 5.2 — Lista de verificación

Marca cada una que funcione:

| # | Verificación | Cómo probarlo | ¿OK? |
|---|---|---|---|
| 1 | **La página carga** | Abrir `https://tu-dominio.com` | ☐ |
| 2 | **HTTPS funciona** | Verificar que hay un 🔒 candado en la barra | ☐ |
| 3 | **Login admin** | `admin@naturacor.com` / `Admin123!` | ☐ |
| 4 | **Dashboard carga** | Ver gráficos y datos | ☐ |
| 5 | **POS funciona** | Hacer una venta de prueba | ☐ |
| 6 | **IA responde** | Módulo IA → hacer una pregunta | ☐ |
| 7 | **Boleta PDF** | Generar una boleta | ☐ |
| 8 | **Login empleado** | `empleado@naturacor.com` / `Empleado123!` | ☐ |

### PASO 5.3 — ¿Algo no funciona?

| Problema | Solución |
|---|---|
| **Pantalla en blanco o error 500** | Ve a Railway → Deployments → mira los **logs** para ver el error exacto |
| **"Connection refused" o error de BD** | Revisa que `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` estén correctos en las variables |
| **"419 Page Expired"** | Ejecuta las migraciones: en Railway CLI o redespliega |
| **Dominio no carga** | Espera más tiempo (DNS puede tardar hasta 48h) o verifica en dnschecker.org |
| **"Mixed Content" (errores de HTTP/HTTPS)** | Asegúrate que `APP_URL` empiece con `https://` |
| **IA no responde** | Verifica que `GROQ_API_KEY` esté correcta en las variables |
| **Assets (CSS/JS) no cargan** | Verifica que `npm run build` corrió sin errores en los logs del deploy |

---

# PARTE 6: CRON DEL MOTOR DE RECOMENDACIÓN ⏰

> [!IMPORTANT]
> **Esta parte es OBLIGATORIA si quieres que las recomendaciones del POS sean rápidas en horario laboral.** Sin cron, el motor recalcula los perfiles y la matriz de co-ocurrencia "en caliente" durante los primeros requests del día y la latencia será alta.

## ¿Qué corre el cron?

El sistema tiene **2 jobs nocturnos diarios** y **1 job semanal** registrados en `routes/console.php` (Bloques 3 y 5):

| Cadencia | Hora (server time) | Job | Tabla que reconstruye | Por qué offline |
|---|---|---|---|---|
| **Diaria** | **02:00** | `ReconstruirPerfilesJob` | `cliente_perfil_afinidad` | Recorre todos los clientes activos; mover esto al request del día genera latencia visible. |
| **Diaria** | **02:30** | `ReconstruirCoocurrenciaJob` | `producto_coocurrencias` | Cómputo O(n²) por par de productos; debe ser offline obligatoriamente. |
| **Semanal (lunes)** | **03:00** | `ActualizarDemandaJob` | `producto_demanda_semana` + `producto_prediccion_demanda` | Agrega meses de ventas y ajusta SES; pesado pero solo se necesita 1 vez por semana ISO. |

Los tres jobs son **encolables** (`ShouldQueue`) y están protegidos contra solapamiento (`withoutOverlapping`). Si por algún motivo el job anterior todavía está corriendo, el siguiente NO se duplica.

---

## OPCIÓN A — Linux / Railway / cPanel / VPS (PRODUCCIÓN)

### PASO 6.1 — Habilitar cron del scheduler

En **Linux** Laravel necesita una sola entrada de cron que invoque el scheduler cada minuto. El propio scheduler decide qué tarea ejecutar a qué hora.

1. Conéctate a tu servidor por SSH
2. Edita el crontab del usuario que corre la app:

```bash
crontab -e
```

3. Pega esta línea **al final** (reemplaza `/ruta/al/proyecto` por la ruta real de tu app, ejemplo en Railway: `/app`):

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

4. Guarda con `Ctrl+X`, `Y`, `Enter`
5. Verifica con `crontab -l` que la línea esté

### PASO 6.2 — Levantar un worker de cola (recomendado)

Como los jobs son `ShouldQueue`, idealmente queremos que **NO bloqueen** al scheduler. En `.env` deja:

```
QUEUE_CONNECTION=database
```

Y arranca un worker permanente con `supervisor` (o equivalente):

```bash
php artisan queue:work --queue=default --tries=1 --timeout=1800
```

> [!TIP]
> Si no quieres meter `supervisor`, deja `QUEUE_CONNECTION=sync` y los jobs correrán **sincrónicamente** dentro del propio proceso `schedule:run`. Funciona, pero el scheduler quedará bloqueado durante el cómputo (1-5 min según volumen). Para una clínica pequeña tipo NATURACOR Jauja, `sync` es perfectamente aceptable.

### PASO 6.3 — Verificar que el schedule corre

A la mañana siguiente, revisa los logs:

```bash
tail -100 storage/logs/laravel.log | grep -E "ReconstruirPerfilesJob|ReconstruirCoocurrenciaJob"
```

Debes ver dos líneas de tipo:

```
[INFO] ReconstruirPerfilesJob completado {"clientes_procesados":42,"perfiles_vacios":3,"errores":0,"duracion_seg":12.345}
[INFO] ReconstruirCoocurrenciaJob completado {"transacciones":1234,"productos":98,...}
```

---

## OPCIÓN B — Windows local (DESARROLLO)

En Windows local **no necesitas configurar el Programador de Tareas**. Laravel trae un comando que reemplaza al cron para desarrollo:

```powershell
php artisan schedule:work
```

Déjalo corriendo en una terminal separada. Cada minuto verifica si toca disparar algún job.

> [!NOTE]
> Para verificar manualmente que los jobs funcionan **sin esperar a las 02:00**, puedes dispararlos a mano:
>
> ```powershell
> php artisan tinker
> > App\Jobs\Recommendation\ReconstruirPerfilesJob::dispatchSync()
> > App\Jobs\Recommendation\ReconstruirCoocurrenciaJob::dispatchSync()
> ```

---

## PASO 6.4 — Variables de control fino (opcional)

Si necesitas cambiar las horas o desactivar uno de los jobs sin tocar código, agrega estas keys a tu `.env`:

```env
# Activar/desactivar jobs sin redespliegue
REC_JOB_PERFILES_ENABLED=true
REC_JOB_COO_ENABLED=true

# Horas en formato HH:MM (server time)
REC_JOB_PERFILES_HORA=02:00
REC_JOB_COO_HORA=02:30

# Tamaño de página al iterar clientes (sube si tienes mucha RAM)
REC_JOB_PERFILES_CHUNK=200

# Cola Laravel donde encolar ambos jobs (si usas workers dedicados)
REC_JOB_COLA=default

# ─── Bloque 5: pronóstico de demanda (SES) ───
# Activar/desactivar el job semanal sin redespliegue
REC_JOB_DEMANDA_ENABLED=true

# Día (0=domingo .. 6=sábado) y hora a la que se dispara semanalmente
REC_JOB_DEMANDA_DIA=1          # lunes
REC_JOB_DEMANDA_HORA=03:00

# Hiperparámetros del modelo
REC_FORECAST_ALPHA=0.4         # suavizado SES (0,1)
REC_FORECAST_HISTORIA=16       # semanas de historia que materializa el job
REC_FORECAST_MIN_OBS=8         # mínimo para predecir (no bajar en producción)
REC_FORECAST_TOP_WIDGET=10     # cuántos productos en riesgo muestra el dashboard

# ─── Bloque 6: mapa de calor de enfermedades ───
# Ventana inicial al cargar el panel (filtro de la vista lo puede sobreescribir)
REC_HEATMAP_DIAS=90

# Score mínimo en cliente_perfil_afinidad para considerar evidencia "observada"
# 0.20 ≈ 4-5 compras significativas. Subir a 0.40 si hay mucho ruido en compras
# de productos polifuncionales (ej. Maca → energía/digestivo/inmunidad).
REC_HEATMAP_UMBRAL_SCORE=0.20

# Cuántas enfermedades top muestra cada tarjeta de sucursal en el panel
REC_HEATMAP_TOP_SUC=3

# Permite desactivar el clustering jerárquico cuando el recetario crece
# >200 enfermedades (clustering es O(n³))
REC_HEATMAP_CLUSTER=true
```

---

## PASO 6.4 BIS — Bloque 5 · Pronóstico de demanda (SES)

El job semanal alimenta el widget **"Pronóstico de demanda · próxima semana"** del dashboard. Ese widget cruza la predicción del modelo con el `stock` actual y avisa qué productos van a quebrar antes de que ocurra.

### Verificar el ciclo manualmente

```powershell
# 1) Disparar el job sin esperar al lunes 03:00
php artisan tinker
> App\Jobs\Recommendation\ActualizarDemandaJob::dispatchSync()

# 2) Inspeccionar el snapshot
> App\Models\ProductoPrediccionDemanda::orderByDesc('prediccion')->limit(10)->get(['producto_id','prediccion','intervalo_inf','intervalo_sup','mae','mape'])
```

### Logs esperados

```
[INFO] ActualizarDemandaJob completado {
   "historico":{"semanas":16,"filas_insertadas":312,"productos_distintos":48},
   "predicciones":{"productos_pronosticados":45,"omitidos_historia_corta":3,"alpha":0.4},
   "duracion_seg":4.812
}
```

### Caveats académicos (mencionar en tesis)

- **SES no captura estacionalidad** (Navidad, día de la madre, fechas patronales). Para series con ciclo claro, propusimos en el paper extender a **Holt-Winters** como trabajo futuro.
- El **intervalo 95%** es una aproximación naive con `±1.96·σ_residuos` (no un CI bayesiano riguroso).
- **MAPE** se descarta cuando `Y_t = 0` para evitar división por cero; es por eso que algunas filas pueden mostrarse sin MAPE.
- Productos con menos de **8 semanas** de historia se omiten para evitar reportar ruido.

---

## PASO 6.4 TER — Bloque 6 · Mapa de calor de enfermedades

Panel ejecutivo en `GET /metricas/heatmap-enfermedades` que muestra una matriz **Enfermedades × Sucursales** con tres modos de evidencia:

- **Declarada**: padecimientos auto-reportados en `cliente_padecimientos`.
- **Observada**: clientes con score ≥ umbral en `cliente_perfil_afinidad` (afinidad inferida desde compras).
- **Combinada**: unión de ambos conjuntos con deduplicación cliente-único (un cliente declarado y observado cuenta UNA sola vez).

### Casos de uso de negocio (vendibles localmente)

> *"En Jauja, las enfermedades digestivas concentran al 40% de los clientes frecuentes. Conviene reforzar inventario de NopalCordial y MagaCordial en esa sucursal."*

El panel también ofrece:

- **Clustering jerárquico** opcional (single-linkage, distancia coseno) que reordena las filas para revelar grupos de enfermedades co-ocurrentes (ej. "estreñimiento" y "digestivo" caen juntas si comparten clientes).
- **Top-3 enfermedades por sucursal** como insight resumido.
- **Exportación CSV** vía `GET /metricas/heatmap-enfermedades/export.csv?fuente=combinada&dias=90` para incluir como tabla en la tesis.

### Verificación manual

```powershell
php artisan tinker
> app(App\Services\Analytics\HeatmapEnfermedadesService::class)->construirMatriz('combinada', 90)
```

### Caveats académicos (mencionar en tesis)

- **Cliente sin sucursal propia**: el modelo `Cliente` no almacena `sucursal_id`; la sucursal se infiere desde sus ventas en la ventana. Un cliente puede aparecer en varias columnas si compró en más de una sucursal.
- **Clustering O(n³)**: aceptable para recetarios de hasta ~200 enfermedades. Para recetarios mayores hay que migrar a UPGMA con heap o desactivar `REC_HEATMAP_CLUSTER`.
- **Combinada deduplica**: un cliente con padecimiento declarado **Y** afinidad observada cuenta una sola vez por celda. Esto evita inflado artificial.
- **Umbral de score** (`REC_HEATMAP_UMBRAL_SCORE`): subirlo si se observa que productos polifuncionales generan demasiadas falsas asociaciones; bajarlo si el panel queda muy disperso.

---

## PASO 6.5 — Troubleshooting cron

| Problema | Diagnóstico | Solución |
|---|---|---|
| **Logs no muestran ejecución** | Cron no está corriendo | `crontab -l` debe mostrar la línea `* * * * * ... schedule:run` |
| **"Command not found: php"** | PATH del cron no encuentra PHP | Usa la ruta absoluta: `/usr/bin/php` o `which php` |
| **Job falla con timeout** | Volumen creció | Sube `REC_JOB_PERFILES_CHUNK` y/o usa worker dedicado en cola |
| **"There are no commands defined"** | `routes/console.php` no se está cargando | Borra cache: `php artisan config:clear && php artisan route:clear` |
| **Mismo job se dispara dos veces** | `withoutOverlapping` no funciona en multi-server sin cache compartido | Asegúrate que `CACHE_STORE=database` o `redis`, no `file` |
| **Widget de pronóstico vacío** | El job semanal nunca corrió o la historia es < 8 semanas | Disparar `ActualizarDemandaJob::dispatchSync()` manualmente y revisar `producto_prediccion_demanda` |
| **MAPE muy alto (>50%)** | Serie con ceros frecuentes o cambios bruscos | Suba `REC_FORECAST_HISTORIA` para suavizar; documente la limitación de SES |
| **Heatmap muestra "Sin enfermedades..."** | Catálogo vacío o todas inactivas | Verificar `Enfermedad::where('activa', true)->count()` y `Sucursal::where('activa', true)->count()` |
| **Una enfermedad con valor 0 en todas las celdas** | Ningún cliente con esa enfermedad compró en la ventana de días | Subir el filtro `dias` en la URL o confirmar que hay padecimientos declarados |
| **Cluster muy lento al cargar el heatmap** | Demasiadas enfermedades activas (clustering O(n³)) | Cambiar a `?orden=total` o desactivar con `REC_HEATMAP_CLUSTER=false` |

---

## 🎯 RESUMEN FINAL

```
✅ Dominio comprado en Porkbun             ~$10/año
✅ App desplegada en Railway               $5/mes
✅ SSL/HTTPS automático                    GRATIS
✅ Dominio personalizado conectado         INCLUIDO
✅ Base de datos MySQL en la nube          INCLUIDO
✅ Deploy automático con cada git push     INCLUIDO
✅ Cron nocturno del recomendador          INCLUIDO
✅ Cron semanal del modelo SES (Bloque 5)  INCLUIDO
✅ Mapa de calor de enfermedades (Bloque 6) INCLUIDO

TOTAL: ~$6/mes = ~S/23/mes  💪
```

> [!TIP]
> **¡Cada vez que hagas `git push` a tu rama main, Railway desplegará automáticamente los cambios!** No tienes que hacer nada más. Solo programa, pushea, y listo.
