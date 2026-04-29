# Seguridad del Sistema — NATURACOR

## Documento de Seguridad Informática
**Fecha:** 28/04/2026  
**Versión:** 1.1 — Revisada y corregida  
**Estándar de referencia:** ISO/IEC 27001:2022, OWASP Top 10 (2021)

---

## 1. Introducción

Este documento describe los controles de seguridad implementados en el sistema NATURACOR, alineados con la norma ISO/IEC 27001:2022 y las recomendaciones del OWASP Top 10. Se cubren los dominios de:

- Control de acceso (autenticación y autorización)
- Protección de datos en tránsito y reposo
- Prevención de vulnerabilidades comunes
- Auditoría y trazabilidad
- Gestión de sesiones
- Validación de entrada

---

## 2. Control de Acceso

### 2.1. Autenticación

| Control | Implementación | Archivo(s) |
|---------|---------------|-------------|
| **Framework de autenticación** | Laravel Breeze (scaffolding oficial) | `routes/auth.php` |
| **Almacenamiento de contraseñas** | Bcrypt (12 rounds en producción) | `User::$casts['password' => 'hashed']` |
| **Middleware global** | `auth` aplicado a todas las rutas excepto login y catálogo público | `routes/web.php` línea 30 |
| **Verificación de email** | Cast `email_verified_at => datetime` | `User.php` |
| **Remember token** | Token seguro para "Recuérdame" | `users.remember_token` |
| **Protección de fuerza bruta** | Rate limiting de Laravel (60 intentos/minuto por IP) | `App\Http\Kernel` |

### 2.2. Roles y Permisos (RBAC)

El sistema implementa **Control de Acceso Basado en Roles (RBAC)** mediante el paquete [Spatie Laravel Permission v6.25](https://spatie.be/docs/laravel-permission/v6):

| Rol | Descripción | Acceso |
|-----|-------------|--------|
| **admin** | Administrador del sistema | Acceso total a todos los módulos, incluyendo sucursales, usuarios, dashboard y reportes |
| **empleado** | Personal operativo de la tienda | POS, Clientes, Caja, Cordiales, Recetario, Reclamos (crear). **NO** accede a Sucursales, Usuarios ni Dashboard |

**Implementación del middleware de roles:**

```php
// app/Http/Middleware/RoleMiddleware.php
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
```

**Rutas protegidas por rol:**

```php
// routes/web.php
Route::middleware(['role:admin'])->group(function () {
    Route::resource('sucursales', SucursalController::class);
    Route::resource('usuarios', UsuarioController::class);
});
```

### 2.3. Aislamiento de Datos por Sucursal

Los empleados solo ven datos de **su sucursal asignada**. Esto se implementa mediante filtros automáticos en los queries:

```php
// Ejemplo en VentaController@index
$query = Venta::with([...])->when(
    auth()->user()->sucursal_id,
    fn($q) => $q->where('sucursal_id', auth()->user()->sucursal_id)
);
```

**Tablas afectadas:** `ventas`, `caja_sesiones`, `productos`, `logs_auditoria`.

---

## 3. Protección Contra Vulnerabilidades Web

### 3.1. OWASP A01 — Broken Access Control

| Ataque | Mitigación | Evidencia |
|--------|-----------|-----------|
| Acceso no autorizado a rutas admin | Middleware `role:admin` + verificación `auth()` | `SeguridadTest::empleado_no_puede_acceder_a_gestion_de_sucursales` |
| Modificación de datos ajenos | Filtro por `sucursal_id` del usuario autenticado | Queries con `auth()->user()->sucursal_id` |
| Anulación de ventas por empleado | Verificación `isAdmin()` antes de anular | `VentaController@destroy` |
| Escalación vertical | Spatie Permission verifica rol en cada request | `RoleMiddleware.php` |

### 3.2. OWASP A02 — Cryptographic Failures

| Control | Implementación |
|---------|---------------|
| **Contraseñas** | Bcrypt con sal aleatoria (12 rounds) — NUNCA en texto plano |
| **API Keys** | Almacenadas en `.env` (excluido de Git). Leídas vía `config()`, nunca `env()` directamente |
| **Tokens CSRF** | Generados criptográficamente por Laravel |
| **Sesiones** | Cookie `HttpOnly`, `Secure` (en producción), `SameSite=Lax` |

### 3.3. OWASP A03 — Injection

| Tipo | Mitigación | Detalle |
|------|-----------|---------|
| **SQL Injection** | ORM Eloquent + Query Builder con prepared statements | NUNCA se construyen queries con concatenación de strings |
| **XSS** | Blade escapa automáticamente con `{{ }}`. Solo se usa `{!! !!}` en contenido controlado | Templates Blade |
| **Command Injection** | No se ejecutan comandos del sistema con input del usuario | N/A |

**Ejemplo de query seguro:**

```php
// CORRECTO: Eloquent con parámetros
$clientes = Cliente::where('dni', 'like', "%{$request->search}%")->get();

// NUNCA hacer esto:
// DB::select("SELECT * FROM clientes WHERE dni LIKE '%$search%'");
```

### 3.4. OWASP A05 — Security Misconfiguration

| Control | Implementación |
|---------|---------------|
| **APP_DEBUG=false** en producción | `.env` de producción |
| **APP_KEY** | Generado con `php artisan key:generate` — 256 bits |
| **Archivos sensibles** | `.env`, `storage/`, `vendor/` excluidos de Git |
| **Headers de seguridad** | `X-Frame-Options`, `X-Content-Type-Options` vía Apache/nginx |

### 3.5. OWASP A07 — Cross-Site Request Forgery (CSRF)

Laravel incluye protección CSRF automática:

```php
// Middleware VerifyCsrfToken activo por defecto
// Todos los formularios POST incluyen @csrf token automáticamente
```

**Evidencia en tests:**

```php
// SeguridadTest.php — Verificación de middleware CSRF
#[Test]
public function proteccion_csrf_esta_activa_en_el_sistema(): void
{
    $instance = app(VerifyCsrfToken::class);
    $this->assertInstanceOf(VerifyCsrfToken::class, $instance);
}
```

---

## 4. Validación de Entrada

### 4.1. Validación en Controladores

Todos los controladores validan la entrada del usuario antes de procesarla:

```php
// VentaController@store
$rules = [
    'metodo_pago' => 'required|string',
    'cliente_id'  => 'nullable|exists:clientes,id',
    'items.*.producto_id'  => 'required|exists:productos,id',
    'items.*.cantidad'     => 'required|integer|min:1',
    'items.*.descuento'    => 'nullable|numeric|min:0',
];
$request->validate($rules);
```

### 4.2. Validación en Modelos

- **Casts seguros:** Los modelos definen tipos para cada campo (`decimal:2`, `boolean`, `array`, etc.)
- **Fillable/Guarded:** Solo los campos listados en `$fillable` son asignables masivamente
- **Soft Deletes:** Previenen eliminación accidental de datos históricos

### 4.3. Validación de APIs

Las rutas de la API de recomendación validan:

```php
// RecomendacionController@registrarEvento
$data = $request->validate([
    'reco_sesion_id' => 'required|uuid',
    'cliente_id'     => 'required|exists:clientes,id',
    'producto_id'    => 'required|exists:productos,id',
    'accion'         => 'required|string|in:agregada,clic',
]);
```

---

## 5. Auditoría y Trazabilidad

### 5.1. Log de Auditoría

El sistema registra acciones críticas en la tabla `logs_auditoria`:

| Campo | Descripción |
|-------|-------------|
| `user_id` | Quién ejecutó la acción |
| `accion` | Qué hizo (ej: `venta.creada`, `reclamo.escalado`) |
| `tabla_afectada` | En qué tabla |
| `registro_id` | ID del registro afectado |
| `datos_anteriores` | Snapshot JSON del estado anterior |
| `datos_nuevos` | Snapshot JSON del estado posterior |
| `ip` | Dirección IP del request |
| `sucursal_id` | Desde qué sucursal |

**Acciones auditadas:**
- ✅ Creación de ventas
- ✅ Anulación de ventas
- ✅ Cambios de estado en reclamos
- ✅ Escalado de reclamos
- ✅ Entrega de premios de fidelización

### 5.2. Métricas de Recomendación (Trazabilidad Experimental)

El módulo de recomendación registra un trail completo para auditoría científica:

```
mostrada → clic → agregada → comprada
```

Cada evento registra: `reco_sesion_id`, `cliente_id`, `producto_id`, `score`, `razones`, `grupo_ab`, `posicion`, `user_id`, `sucursal_id`, `created_at`.

---

## 6. Gestión de Sesiones

| Control | Configuración |
|---------|---------------|
| **Driver de sesión** | `file` (desarrollo) / `database` (producción) |
| **Lifetime** | 120 minutos (configurable) |
| **Cookie flags** | `HttpOnly=true`, `Secure=true` (producción), `SameSite=Lax` |
| **Rotación** | Sesión regenerada después del login (`regenerate()`) |
| **Logout** | Invalida sesión + elimina cookie |

---

## 7. Protección de Datos Sensibles

### 7.1. Datos Protegidos

| Dato | Clasificación | Protección |
|------|---------------|-----------|
| Contraseñas | Crítico | Hash Bcrypt (irreversible) |
| API Keys (Groq/Gemini) | Confidencial | `.env` (no versionado) |
| DNI de clientes | Personal | Acceso por autenticación |
| Historial de compras | Comercial | Filtro por sucursal |
| Perfil de salud (padecimientos) | Sensible | Solo personal autorizado |
| Métricas A/B testing | Investigación | Acceso solo admin |

### 7.2. Variables de Entorno Sensibles

```env
APP_KEY=base64:...              # Clave de encriptación (256 bits)
DB_PASSWORD=***                  # Contraseña de BD
GROQ_API_KEY=***                 # API key de Groq
GEMINI_API_KEY=***               # API key de Gemini
CLOUDINARY_API_SECRET=***        # Secret de Cloudinary
```

> **📌 El archivo `.env` está en `.gitignore` y NUNCA se sube al repositorio.**

---

## 8. Integridad de Datos

### 8.1. Transacciones de Base de Datos

Las operaciones críticas usan transacciones para garantizar atomicidad:

```php
DB::beginTransaction();
try {
    // 1. Crear Venta
    // 2. Crear DetalleVenta (por cada producto)
    // 3. Decrementar stock
    // 4. Registrar cordiales
    // 5. Procesar fidelización
    // 6. Actualizar caja
    // 7. Registrar auditoría
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    return error;
}
```

### 8.2. Bloqueo Optimista

Para evitar **race conditions** en stock:

```php
$producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
if ($producto->stock < $item['cantidad']) {
    throw new \Exception("Stock insuficiente");
}
```

### 8.3. Soft Deletes

Los registros no se eliminan físicamente. Se marcan con `deleted_at`:
- `clientes`, `productos`, `ventas`, `sucursales`, `enfermedades`

---

## 9. Pruebas de Seguridad

### 9.1. Tests Automatizados de Seguridad

El archivo `SeguridadTest.php` contiene pruebas específicas de seguridad:

| Test | Verifica |
|------|----------|
| `empleado_no_puede_acceder_a_gestion_de_sucursales` | Empleado recibe 403 al intentar acceder a `/sucursales` |
| `empleado_no_puede_crear_sucursal` | POST `/sucursales` → 403 + BD sin cambios |
| `proteccion_csrf_esta_activa_en_el_sistema` | Middleware `VerifyCsrfToken` existe y se resuelve |
| `post_con_token_csrf_valido_pasa` | Request con token válido → no es 419 |
| `credenciales_incorrectas_no_autentican` | Credenciales incorrectas → error de validación |
| `usuario_no_autenticado_es_redirigido_al_login` | 8 rutas protegidas → redirect a `/login` |
| `empleado_solo_ve_ventas_de_su_sucursal` | Filtro por `sucursal_id` verificado |
| `usuario_inactivo_no_puede_autenticarse` | `activo: false` → error, `assertGuest()` |

### 9.2. Análisis Estático

El proyecto incluye configuración para SonarQube/SonarCloud:

```properties
# sonar-project.properties
sonar.sources=app,routes,config
sonar.tests=tests
sonar.php.coverage.reportPaths=coverage.xml
```

---

## 10. Mapeo ISO 27001:2022

| Control ISO 27001 | Implementación NATURACOR |
|--------------------|--------------------------|
| **A.5.15** Acceso al medio | Control de acceso basado en roles (RBAC) |
| **A.5.17** Autenticación | Laravel Breeze + Bcrypt |
| **A.8.2** Acceso privilegiado | Middleware `role:admin` para rutas sensibles |
| **A.8.3** Restricción de acceso | Filtro por `sucursal_id` del usuario |
| **A.8.5** Autenticación segura | Bcrypt 12 rounds + sesión HttpOnly |
| **A.8.9** Gestión de configuración | `.env` separado por entorno, no versionado |
| **A.8.12** Prevención de fuga de datos | `$hidden` en modelos para campos sensibles |
| **A.8.24** Criptografía | TLS 1.2+ para APIs externas, Bcrypt para passwords |
| **A.8.25** Ciclo de vida del desarrollo seguro | CI/CD con tests de seguridad automatizados |
| **A.8.28** Codificación segura | ORM Eloquent, escape XSS, CSRF tokens |
| **A.8.34** Protección de sistemas de información en pruebas | SQLite in-memory, datos sintéticos (factories) |
