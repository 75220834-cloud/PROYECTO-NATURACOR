# 🗺️ ROADMAP: De Local a Producción — NATURACOR

**Orden cronológico real. Sin teoría. Solo acción.**

---

## TU SITUACIÓN ACTUAL (lo que ya tienes)

| Lo que tienes | Estado |
|---|---|
| Sistema Laravel 12 completo | ✅ Funcionando en local |
| 350 tests automatizados | ✅ PHPUnit |
| CI/CD GitHub Actions | ✅ Pipeline activo |
| IA con Groq/Gemini + offline | ✅ IAController listo |
| Multi-sucursal + roles | ✅ Spatie Permission |
| Repo en GitHub | ✅ `75220834-cloud/PROYECTO-NATURACOR` |

---

## 📅 CRONOGRAMA: 5 FASES EN ORDEN

```
FASE 1: Limpieza y Preparación        ← HOY (30 min)
FASE 2: Tests Finales + Evidencia     ← HOY (1 hora)
FASE 3: Despliegue a la Nube          ← HOY/MAÑANA (1-2 horas)
FASE 4: WhatsApp Business             ← DESPUÉS del despliegue (30 min)
FASE 5: Documentación Final           ← ÚLTIMO (30 min)
```

---

# FASE 1: LIMPIEZA Y PREPARACIÓN

**⏰ Cuándo:** Hoy mismo, ahora.
**⏱️ Duración:** 30 minutos.
**📍 Por qué primero:** No puedes subir a la nube un proyecto con credenciales expuestas, archivos basura o configuración incorrecta.

---

### PASO 1.1 — Asegurar que tu [.env](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/.env) NO esté en Git

Abre tu terminal en la carpeta del proyecto y ejecuta:

```bash
git log --all --diff-filter=A -- .env
```

- **Si no muestra nada →** Perfecto, [.env](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/.env) nunca se subió ✅
- **Si muestra commits →** Tu API key de Groq (`gsk_XXXXXXXXXXXX`) quedó en el historial. Debes rotarla: ve a [console.groq.com](https://console.groq.com), revoca esa key y genera una nueva.

### PASO 1.2 — Actualizar el [.env.example](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/.env.example) para producción

Tu [.env.example](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/.env.example) actual es el que viene por defecto de Laravel (dice `APP_NAME=Laravel`, `APP_LOCALE=en`). Debes actualizarlo para que cualquiera que clone tu repo pueda configurarlo:

```env
APP_NAME=NATURACOR
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=naturacor
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@naturacor.pe"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

# IA (opcionales — el sistema funciona sin ellas en modo offline)
GROQ_API_KEY=
GEMINI_API_KEY=

# Configuración de negocio
STOCK_MINIMO_DEFAULT=5
IGV_PORCENTAJE=18
FIDELIZACION_MONTO=500
FIDELIZACION_MAXIMO_PREMIO=30
FIDELIZACION_CORDIALES_MONTO=500
FIDELIZACION_INICIO=2026-01-01
FIDELIZACION_FIN=2026-12-31
```

**Ejecuta esto en tu terminal:**

```bash
copy .env.example .env.example.bak
```

Luego reemplaza el contenido de [.env.example](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/.env.example) con lo de arriba.

### PASO 1.3 — Limpiar y hacer commit

```bash
# Verificar que todo está limpio
git status

# Agregar cambios
git add .env.example
git commit -m "chore: actualizar .env.example con configuración NATURACOR completa"

# Push a GitHub
git push origin main
```

### PASO 1.4 — Verificar que tu sistema funciona en local

```bash
# 1. Levantar el servidor
php artisan serve

# 2. Abrir en el navegador: http://localhost:8000
#    - Login con admin@naturacor.com / Admin123!
#    - Verificar que el Dashboard carga
#    - Ir al POS → hacer una venta de prueba
#    - Ir a IA → hacer una consulta
#    - Verificar que la boleta se genera

# 3. Cerrar el servidor (Ctrl+C)
```

> [!IMPORTANT]
> **Si algo no funciona en local, ARRÉGLALO ANTES de continuar.** No subas a la nube un proyecto roto.

---

# FASE 2: TESTS FINALES + EVIDENCIA

**⏰ Cuándo:** Hoy, después de la Fase 1.
**⏱️ Duración:** 1 hora.
**📍 Por qué ahora:** Los tests son tu evidencia de calidad para el docente. Debes ejecutarlos y capturar la evidencia ANTES del despliegue.

---

### PASO 2.1 — Ejecutar los 350 tests automatizados

```bash
php artisan test
```

**Resultado esperado:**

```
Tests:    350 passed (1347 assertions)
Duration: ~25s
```

📸 **CAPTURA DE PANTALLA AHORA** — Esta es tu evidencia #1 para el docente.

### PASO 2.2 — Ejecutar tests con resumen detallado

```bash
php artisan test --log-junit=test-results.xml
```

Esto genera un archivo XML con todos los resultados. Guárdalo para tu documentación.

### PASO 2.3 — Verificar que el pipeline CI/CD funciona

1. Ve a tu repositorio en GitHub: `https://github.com/75220834-cloud/PROYECTO-NATURACOR`
2. Haz clic en la pestaña **"Actions"**
3. Verifica que el último workflow muestra ✅ **verde**

📸 **CAPTURA DE PANTALLA** — Evidencia #2: CI/CD pasando en GitHub Actions.

### PASO 2.4 — Pruebas manuales de aceptación (UAT)

Ejecuta estas pruebas manualmente en tu sistema local y documenta los resultados:

| # | Prueba | Cómo ejecutar | Resultado esperado | ¿Pasó? |
|---|---|---|---|---|
| 1 | **Login admin** | Ir a /login → admin@naturacor.com / Admin123! | Accede al dashboard | ☐ |
| 2 | **Login empleado** | Ir a /login → empleado@naturacor.com / Empleado123! | Accede al POS (no ve Dashboard admin) | ☐ |
| 3 | **Abrir caja** | Módulo Caja → Abrir con S/100 | Caja abierta correctamente | ☐ |
| 4 | **Registrar venta** | POS → Buscar producto → Agregar → Pagar en efectivo | Boleta generada, stock descontado | ☐ |
| 5 | **Generar boleta PDF** | Después de venta → Ver boleta → Descargar PDF | PDF se descarga correcto (80mm) | ☐ |
| 6 | **IA en modo online** | Módulo IA → "¿Cuáles son mis productos más vendidos?" | IA responde con datos reales | ☐ |
| 7 | **IA en modo offline** | Quitar GROQ_API_KEY del .env → consultar IA | Respuesta local con datos del negocio | ☐ |
| 8 | **Cerrar caja** | Módulo Caja → Cerrar → Ingresar conteo real | Diferencia calculada correctamente | ☐ |
| 9 | **Crear reclamo** | Módulo Reclamos → Nuevo → Llenar formulario | Reclamo creado en estado "pendiente" | ☐ |
| 10 | **Escalar reclamo** | Reclamo pendiente → clic "Escalar" | Estado cambia a "en_proceso" | ☐ |
| 11 | **Resolver reclamo** | Reclamo en_proceso → Resolver con descripción | Estado cambia a "resuelto" | ☐ |
| 12 | **Empleado NO accede a admin** | Login empleado → ir a /sucursales | Redirigido o error 403 | ☐ |
| 13 | **Fidelización** | Registrar ventas hasta S/500 para un cliente | Premio generado automáticamente | ☐ |
| 14 | **Reporte filtrado** | Reportes → Filtrar por fecha del día | Muestra las ventas del día | ☐ |

📸 **CAPTURA de cada una que pase** — Evidencia #3 para el docente.

---

# FASE 3: DESPLIEGUE A LA NUBE

**⏰ Cuándo:** Después de que la Fase 2 esté completa (todo pasa).
**⏱️ Duración:** 1-2 horas.
**📍 Por qué ahora y no antes:** Porque necesitas garantizar que tu código está limpio, los tests pasan, y todo funciona antes de poner algo en Internet.

---

### Elección de plataforma: Railway.app

**¿Por qué Railway?** Es la forma más rápida de desplegar Laravel. No necesitas configurar servidores, Nginx, ni nada. Solo conectas tu GitHub y listo.

- **Costo:** $5 de crédito gratis al verificar con tarjeta (suficiente para ~2 meses).
- **Alternativa sin tarjeta:** Render.com (free tier sin tarjeta, pero más lento en arranque).

---

### PASO 3.1 — Crear cuenta en Railway

1. Ve a [railway.app](https://railway.app)
2. Clic en **"Start a New Project"**
3. **Inicia sesión con tu cuenta de GitHub** (la misma donde está tu repo)

### PASO 3.2 — Crear el proyecto desde GitHub

1. Clic en **"Deploy from GitHub Repo"**
2. Selecciona el repositorio: **`75220834-cloud/PROYECTO-NATURACOR`**
3. Railway detecta que es Laravel/PHP automáticamente
4. **NO hagas deploy todavía** — primero configura las variables

### PASO 3.3 — Agregar base de datos MySQL

1. Dentro de tu proyecto en Railway, clic en **"+ New"** → **"Database"** → **"MySQL"**
2. Railway crea la base de datos automáticamente
3. Haz clic en la base de datos creada → pestaña **"Variables"**
4. Anota los valores de: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

### PASO 3.4 — Configurar TODAS las variables de entorno

Haz clic en tu **servicio Laravel** (no en la BD) → pestaña **"Variables"** → **"Raw Editor"** → pega todo esto:

```env
APP_NAME=NATURACOR
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-app.up.railway.app

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_DATABASE=${MYSQL_DATABASE}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=480
QUEUE_CONNECTION=sync
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@naturacor.pe"
MAIL_FROM_NAME=NATURACOR

GROQ_API_KEY=tu_nueva_api_key_aqui
GEMINI_API_KEY=tu_api_key_aqui

STOCK_MINIMO_DEFAULT=5
IGV_PORCENTAJE=18
FIDELIZACION_MONTO=500
FIDELIZACION_MAXIMO_PREMIO=30
FIDELIZACION_CORDIALES_MONTO=500
FIDELIZACION_INICIO=2026-01-01
FIDELIZACION_FIN=2026-12-31
```

> [!CAUTION]
> **`APP_DEBUG=false`** → NUNCA pongas `true` en producción. Expone contraseñas y API keys en los errores.
> **`APP_KEY`** → Se genera automáticamente en el siguiente paso.

### PASO 3.5 — Crear archivo `Procfile` en tu proyecto

En la raíz de tu proyecto (al lado de [composer.json](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/composer.json)), crea un archivo llamado `Procfile` (**sin extensión**):

```
web: php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
```

### PASO 3.6 — Crear archivo `nixpacks.toml` en tu proyecto

En la raíz del proyecto, crea `nixpacks.toml`:

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

### PASO 3.7 — Commit y push para desplegar

```bash
git add Procfile nixpacks.toml
git commit -m "feat: agregar archivos de despliegue Railway"
git push origin main
```

**Railway detecta el push y despliega automáticamente.** Observa los logs en el panel de Railway.

### PASO 3.8 — Verificar el despliegue

1. En Railway, espera a que el deploy termine (2-5 minutos)
2. Haz clic en el **dominio generado** (algo como `naturacor-production.up.railway.app`)
3. Deberías ver la página de login de NATURACOR

**Lista de verificación en producción:**

| Verificación | Cómo | ¿OK? |
|---|---|---|
| Página de login carga | Abrir la URL | ☐ |
| Login admin funciona | admin@naturacor.com / Admin123! | ☐ |
| Dashboard muestra datos | Ir a /dashboard | ☐ |
| POS funciona | Hacer una venta de prueba | ☐ |
| IA responde | Módulo IA → hacer consulta | ☐ |
| Boleta PDF se genera | Ver boleta de la venta | ☐ |
| Empleado tiene acceso limitado | Login empleado → verificar restricciones | ☐ |

📸 **CAPTURAS DE TODO ESTO** — Evidencia #4: sistema funcionando en la nube.

### PASO 3.9 — Dominio personalizado (opcional)

Si quieres un dominio tipo `naturacor.pe`:
1. Compra un dominio en Namecheap (~$10/año para `.pe`)
2. En Railway → Settings → **Custom Domain** → agrega tu dominio
3. Configura los DNS en Namecheap apuntando a Railway

Para presentar el proyecto al docente, la **URL de Railway es suficiente**.

---

### ⛑️ SOLUCIÓN DE PROBLEMAS COMUNES

| Error | Causa | Solución |
|---|---|---|
| **500 Internal Server Error** | APP_KEY faltante o APP_DEBUG=false | Verificar que `APP_KEY` se generó en las variables |
| **SQLSTATE Connection refused** | BD no conectada | Verificar variables DB_HOST, DB_PORT, DB_DATABASE |
| **419 Page Expired** | Sesiones no configuradas | `SESSION_DRIVER=database` y ejecutar migraciones |
| **Mixed Content** | HTTP vs HTTPS | Agregar `APP_URL=https://...` con https |
| **Vite assets no cargan** | Build no ejecutado | Verificar que `npm run build` corrió en nixpacks |
| **IA no responde** | API key no configurada | Verificar `GROQ_API_KEY` en variables de Railway |

---

# FASE 4: WHATSAPP BUSINESS

**⏰ Cuándo:** DESPUÉS de que el sistema esté funcionando en la nube.
**⏱️ Duración:** 30 minutos.
**📍 Por qué ahora y no antes:** WhatsApp depende de tener una URL pública para compartir boletas. Sin el despliegue, no tiene sentido configurarlo.

---

### PASO 4.1 — Descargar WhatsApp Business

1. Busca **"WhatsApp Business"** en la Play Store / App Store
2. Instálalo (**es diferente al WhatsApp normal**)
3. Regístralo con el **número de teléfono de la tienda NATURACOR**

### PASO 4.2 — Configurar el perfil del negocio

Ve a **Ajustes → Herramientas para la empresa → Perfil de empresa**:

| Campo | Valor |
|---|---|
| **Nombre** | NATURACOR — Productos Naturales |
| **Categoría** | Tienda / Salud y bienestar |
| **Descripción** | Tienda de productos naturales y cordiales en Jauja, Junín. Sistema de fidelización: acumula S/500 en compras y recibe una Botella de Nopal 2L GRATIS 🌿 |
| **Dirección** | Tu dirección en Jauja |
| **Horario** | Lun-Sáb: 8:00 AM – 8:00 PM |
| **Email** | info@naturacor.pe |
| **Sitio web** | `https://tu-app.up.railway.app` ← **tu URL de Railway** |

### PASO 4.3 — Mensaje de bienvenida automático

Ve a **Ajustes → Herramientas para la empresa → Mensaje de bienvenida** → Activar:

```
¡Hola! 🌿 Bienvenido(a) a *NATURACOR*
Tu tienda de productos naturales en Jauja.

¿En qué te podemos ayudar?
1️⃣ Ver catálogo de productos
2️⃣ Precios de cordiales
3️⃣ Hacer un pedido
4️⃣ Consultar mi programa de fidelización
5️⃣ Hablar con un asesor

⏰ Atención: Lun-Sáb 8am a 8pm
📍 Jauja, Junín
```

### PASO 4.4 — Respuestas rápidas

Ve a **Ajustes → Herramientas para la empresa → Respuestas rápidas** y crea:

| Atajo | Mensaje |
|---|---|
| `/precios` | `🥤 *Precios de Cordiales NATURACOR:*\n• Toma normal: S/10\n• Litro puro: S/80 (incluye 1 toma gratis 🎉)\n\n🌿 Consulta nuestro catálogo completo en nuestra web` |
| `/fidelidad` | `🏆 *Programa de Fidelización 2026:*\nPor cada S/500 acumulados en productos naturales, recibes una *Botella de 2L de Nopal GRATIS* (valor S/30)\n\n📊 Pregúntanos por tu acumulado con tu DNI` |
| `/pedido` | `📝 *Para hacer tu pedido, envíanos:*\n1. Producto(s) que deseas\n2. Cantidad\n3. Tu nombre y DNI\n4. Método de pago: efectivo / Yape / Plin\n\n✅ Te confirmaremos disponibilidad y precio` |
| `/horario` | `⏰ *Horario de atención:*\nLunes a Sábado: 8:00 AM - 8:00 PM\nDomingos: Cerrado\n\n📍 Jauja, Junín - Perú` |

### PASO 4.5 — Catálogo de productos

Ve a **Ajustes → Herramientas para la empresa → Catálogo**:

1. Agrega tus **productos naturales principales** (los que vendes más) con foto, nombre y precio
2. Agrega los **9 cordiales** con sus precios
3. Agrega **NATURACOR (Nopal 2L)** como producto estrella

### PASO 4.6 — Compartir boletas por WhatsApp

Tu sistema **ya tiene la ruta** `/boletas/{venta}/whatsapp` configurada en [web.php](file:///d:/ESCRITORIO/UNIVERSIDAD/7mo%20ciclo/PRUEBAS%20Y%20CALIDAD%20DE%20SOFTWARE/PROYECTO%20NATURACOR/naturacor/routes/web.php#L55). Ahora que tienes una URL pública, el botón de compartir por WhatsApp funciona con el enlace real de la boleta.

📸 **CAPTURAS DE WHATSAPP** — Evidencia #5: WhatsApp configurado con mensaje de bienvenida, respuestas rápidas y catálogo.

---

# FASE 5: DOCUMENTACIÓN FINAL

**⏰ Cuándo:** Al final, cuando todo funciona.
**⏱️ Duración:** 30 minutos.
**📍 Por qué al final:** Necesitas las capturas de las fases anteriores para completar la documentación.

---

### PASO 5.1 — Compilar toda la evidencia

Crea una carpeta `evidencias/` y organiza:

```
evidencias/
├── 01_tests_180_pasando.png         ← Captura de php artisan test
├── 02_github_actions_verde.png      ← Captura de CI/CD en GitHub
├── 03_login_produccion.png          ← Login en Railway URL
├── 04_dashboard_produccion.png      ← Dashboard funcionando
├── 05_pos_venta_produccion.png      ← POS con venta registrada
├── 06_ia_online_produccion.png      ← IA respondiendo desde la nube
├── 07_boleta_pdf.png                ← Boleta generada
├── 08_whatsapp_bienvenida.png       ← Mensaje de bienvenida
├── 09_whatsapp_catalogo.png         ← Catálogo de productos
├── 10_whatsapp_respuesta_rapida.png ← Respuesta rápida funcionando
└── 11_uat_tabla_resultados.png      ← Tabla UAT de Fase 2 completa
```

### PASO 5.2 — Actualizar tabla UAT con resultados reales

Vuelve a la tabla de la Fase 2 (Paso 2.4) y marca con ✅ todas las pruebas que pasaron. Esta tabla es tu **evidencia de pruebas de aceptación del usuario** para el docente.

### PASO 5.3 — Verificar los documentos que ya tienes

| Documento | Archivo | Estado |
|---|---|---|
| Requisitos de Software (SRS) | [Documento_Requerimientos_NATURACOR.md](../01_fundamentos/Documento_Requerimientos_NATURACOR.md) | ✅ Tienes |
| Plan de Pruebas | [Plan_de_Pruebas_NATURACOR.md](../03_pruebas_calidad/Plan_de_Pruebas_NATURACOR.md) | ✅ Tienes |
| README profesional | [README.md](../../README.md) | ✅ Tienes |
| 350 tests automatizados | `tests/Feature/` + `tests/Unit/` | ✅ Tienes |
| CI/CD Pipeline | `.github/workflows/tests.yml` | ✅ Tienes |
| Evidencia de producción | `evidencias/` | ⬜ Crear ahora |
| Evidencia WhatsApp | `evidencias/` | ⬜ Crear ahora |
| Tabla UAT completa | En tu documentación | ⬜ Crear ahora |

---

## RESUMEN EJECUTIVO — QUÉ HACER, EN QUÉ ORDEN

```
┌─────────────────────────────────────────────────────────────┐
│  ❶ AHORA: Limpiar .env.example, verificar Git        30 min│
│  ❷ AHORA: Ejecutar tests + capturas de evidencia      1 hr │
│  ❸ HOY/MAÑANA: Deploy en Railway + verificar        1-2 hr │
│  ❹ DESPUÉS: WhatsApp Business configurado            30 min │
│  ❺ FINAL: Compilar evidencia y documentación         30 min │
│                                                             │
│  TOTAL ESTIMADO: 3-4 horas                                  │
│  RESULTADO: Sistema en producción + IA + WhatsApp + nota 20 │
└─────────────────────────────────────────────────────────────┘
```

> [!TIP]
> **Lo primero que haces HOY, AHORA MISMO, es el Paso 1.1:** verificar que tu `.env` no se subió a Git. Si la API key de Groq se filtró, tienes que rotarla ANTES de cualquier otra cosa.
