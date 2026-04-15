# 🌿 NATURACOR — Sistema Web Empresarial

![Tests CI/CD](https://github.com/75220834-cloud/PROYECTO-NATURACOR/actions/workflows/tests.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![Tests](https://img.shields.io/badge/Tests-180-brightgreen?logo=phpunit)

> Sistema de Gestión Integral para Tiendas Naturistas con Módulo de Consultoría Inteligente y Recomendación de Productos basado en el Perfil de Salud del Cliente.

**Curso:** Pruebas y Calidad de Software  
**Docente:** Maglioni Arana Caparachin  
**Integrantes:**
- BENDEZU LAGOS JACK JOSHUA
- JULCA LAUREANO DICKMAR WILBER
- REYES CORDERO ITALO EDUARDO

---

## 📋 Descripción del Proyecto

NATURACOR es un **sistema web de punto de venta (POS)** diseñado para una cadena de tiendas de productos naturales en Perú. Integra 10 módulos funcionales que cubren todo el ciclo operativo del negocio: desde el registro de ventas con cálculo automático de IGV, hasta un asistente de inteligencia artificial y un sistema de fidelización con premios automáticos.

El sistema sigue la arquitectura **MVC (Modelo-Vista-Controlador)** de Laravel, implementa **control de acceso basado en roles** (admin/empleado), y cuenta con una suite de **180 pruebas automatizadas** que validan toda la lógica de negocio.

---

## 🧪 Stack Tecnológico

| Categoría | Tecnología | Versión |
|---|---|---|
| **Backend** | PHP | 8.2+ |
| **Framework** | Laravel | 12 |
| **Base de datos** | MySQL (producción) / SQLite (tests) | 8.0+ / en memoria |
| **Frontend** | Blade Templates + Bootstrap 5 | — |
| **Build tool** | Vite + Tailwind CSS | — |
| **Roles y permisos** | Spatie Laravel Permission | 6.25 |
| **Generación de PDFs** | Barryvdh DomPDF | 3.1 |
| **Autenticación** | Laravel Breeze | — |
| **IA** | Groq (Llama 3.3 70B) + Google Gemini 1.5 Flash | API |
| **Tests** | PHPUnit | 11.5 |
| **CI/CD** | GitHub Actions | — |

---

## 🧩 Módulos del Sistema

| # | Módulo | Descripción | Controlador |
|---|---|---|---|
| 1 | 🛒 **POS (Punto de Venta)** | Registro de ventas con IGV incluido, boletas B001-XXXXXX, descuentos y métodos de pago (efectivo, Yape, Plin) | `VentaController` |
| 2 | 📦 **Inventario** | CRUD de productos con stock mínimo, alertas de reposición, búsqueda AJAX y escaneo de código de barras | `ProductoController` |
| 3 | 👥 **Clientes** | Registro por DNI, historial de compras, búsqueda AJAX por DNI, soft delete | `ClienteController` |
| 4 | 💰 **Caja** | Apertura/cierre de sesiones, movimientos (ingresos/egresos), totales por método de pago, diferencia al cierre | `CajaController` |
| 5 | 🏆 **Fidelización** | Regla 2026: acumulado ≥ S/500 en naturales → Botella 2L Nopal gratis. Premios automáticos, entrega manual | `FidelizacionController` |
| 6 | 🥤 **Cordiales** | 9 tipos de bebidas con precios fijos. Promo: litro puro S/80 → 1 toma gratis. Cortesías para invitados | `CordialController` |
| 7 | 🤖 **Asistente IA** | Análisis de negocio con IA. Cascada: Groq → Gemini → modo offline. Recomendaciones contextuales | `IAController` |
| 8 | 🌿 **Recetario** | Enfermedades vinculadas a productos naturales recomendados (relación muchos-a-muchos con instrucciones) | `RecetarioController` |
| 9 | 📋 **Reclamos** | Registro, seguimiento (pendiente → en_proceso → resuelto), escalado al admin. Log de auditoría | `ReclamoController` |
| 10 | 📊 **Reportes y Boletas** | Reportes filtrados por fecha/sucursal/empleado/método. Boletas en PDF (80mm), ticket térmico y WhatsApp | `ReporteController` / `BoletaController` |

**Administración (solo admin):**
- Gestión de sucursales (`SucursalController`)
- Gestión de usuarios y roles (`UsuarioController`)
- Dashboard con KPIs del día/semana/mes (`DashboardController`)

---

## 🗂️ Estructura del Proyecto

```
naturacor/
├── app/
│   ├── Console/Commands/      # Comando artisan: fidelizacion:reiniciar
│   ├── Http/
│   │   ├── Controllers/       # 15 controladores (14 de negocio + Auth)
│   │   ├── Middleware/        # RoleMiddleware (control de acceso)
│   │   └── Requests/          # Form Requests de validación
│   ├── Models/                # 14 modelos Eloquent
│   └── Providers/             # Service providers
├── config/
│   └── naturacor.php          # Config de negocio (IGV, fidelización, APIs)
├── database/
│   ├── factories/             # 5 factories para tests
│   ├── migrations/            # 25 migraciones
│   └── seeders/               # AdminSeeder (roles, usuarios, productos demo)
├── resources/views/           # 18 carpetas de vistas Blade
├── routes/
│   ├── web.php                # Rutas principales (autenticadas)
│   ├── auth.php               # Rutas de autenticación (Breeze)
│   └── console.php            # Comando limpiar:ventas
├── tests/
│   ├── Feature/               # 13 archivos — 121 tests de integración
│   └── Unit/                  # 7 archivos — 59 tests unitarios
├── .github/workflows/         # CI/CD (tests.yml, issues, PRs, changelog)
└── [archivos de configuración]
```

---

## 🛠️ Requisitos Previos

| Herramienta | Versión mínima | Link |
|---|---|---|
| **PHP** | 8.2+ | https://www.php.net/downloads |
| **Composer** | 2.x | https://getcomposer.org |
| **MySQL** | 8.0+ | https://dev.mysql.com/downloads |
| **Node.js** | 18+ | https://nodejs.org |
| **Git** | cualquiera | https://git-scm.com |
| **XAMPP** (opcional) | 8.2+ | https://www.apachefriends.org |

> 💡 En Windows se recomienda usar **XAMPP** — incluye PHP, MySQL y Apache en un solo instalador.

---

## 🚀 Instalación Paso a Paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/75220834-cloud/PROYECTO-NATURACOR.git
cd PROYECTO-NATURACOR
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Instalar dependencias JS (para compilar assets)

```bash
npm install
npm run build
```

### 4. Configurar el archivo de entorno

```bash
cp .env.example .env
```

Luego edita `.env` con tu configuración de base de datos:

```env
APP_NAME=NATURACOR
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=naturacor
DB_USERNAME=root
DB_PASSWORD=          # tu contraseña de MySQL (vacía si usas XAMPP por defecto)

# Opcional: para el Asistente IA (el sistema funciona sin ellas en modo offline)
GROQ_API_KEY=tu_api_key_aqui
GEMINI_API_KEY=tu_api_key_aqui

# Fidelización (valores por defecto)
FIDELIZACION_MONTO=500
FIDELIZACION_MAXIMO_PREMIO=30
FIDELIZACION_INICIO=2026-01-01
FIDELIZACION_FIN=2026-12-31
```

### 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 6. Crear la base de datos en MySQL

Abre MySQL (o phpMyAdmin) y ejecuta:

```sql
CREATE DATABASE naturacor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Ejecutar las migraciones y seeders

```bash
php artisan migrate --seed
```

> Esto crea todas las tablas y carga los datos iniciales (roles, usuario admin, productos de ejemplo, clientes demo).

### 8. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

Abre tu navegador en: **http://localhost:8000**

---

## 🔑 Credenciales por Defecto

| Rol | Email | Contraseña |
|---|---|---|
| **Administrador** | admin@naturacor.com | Admin123! |
| **Empleado** | empleado@naturacor.com | Empleado123! |

> ⚠️ Cambia estas credenciales en producción.

---

## ✅ Suite de Pruebas — 180 Tests

El proyecto incluye **180 casos de prueba** distribuidos en **20 archivos**, que cubren todos los módulos del sistema.

### Distribución de tests

| Tipo | Archivos | Tests | Cobertura |
|---|---|---|---|
| **Feature (integración)** | 13 | 121 | Flujos HTTP completos, validaciones, roles, rutas |
| **Unit (unitarios)** | 7 | 59 | Lógica de modelos, cálculos, relaciones, casts |
| **Total** | **20** | **180** | **Todos los módulos** |

### Detalle por archivo

| Test | Tests | Módulo |
|---|---|---|
| `SeguridadTest` | 16 | CSRF, roles, inyección SQL, aislamiento de sucursales |
| `FidelizacionTest` | 13 | Acumulado S/500, canjes, premios, promo litro puro |
| `ReclamoTest` | 12 | Crear, escalar, resolver, flujo de estados |
| `RecetarioTest` | 12 | CRUD enfermedades, sync productos, búsqueda |
| `ClienteUnitTest` | 12 | nombreCompleto(), puedeReclamarPremio(), reiniciar |
| `CordialVentaUnitTest` | 13 | Precios, labels, tipos acumulables |
| `CordialTest` | 11 | Venta cordial, promo, invitado, validación |
| `ProductoCrudTest` | 10 | CRUD, búsqueda AJAX, stock bajo |
| `IATest` | 10 | Modo offline, análisis de negocio, estructura datos |
| `ProductoUnitTest` | 10 | IGV, stock crítico, soft delete, casts |
| `VentaTest` | 9 | POS, venta múltiple, IGV, boleta |
| `VentaUnitTest` | 8 | Boleta correlativa, relaciones, soft delete |
| `FidelizacionCanjeUnitTest` | 8 | Constantes, scope, relaciones, entrega |
| `ClienteCrudTest` | 8 | CRUD, DNI único, búsqueda AJAX |
| `RecetarioUnitTest` | 7 | Pivot enfermedad-producto, instrucciones, orden |
| `SucursalCrudTest` | 7 | CRUD sucursales (solo admin) |
| `CajaTest` | 6 | Abrir, cerrar, movimiento, venta con caja abierta |
| `AutenticacionTest` | 6 | Login, logout, acceso protegido, roles |
| `ExampleTest (Feature)` | 1 | Smoke test |
| `ExampleTest (Unit)` | 1 | Test básico |

### Ejecutar todos los tests

```bash
php artisan test
```

### Ejecutar un módulo específico

```bash
php artisan test tests/Feature/FidelizacionTest.php
php artisan test tests/Feature/VentaTest.php
php artisan test tests/Feature/SeguridadTest.php
php artisan test tests/Unit/
```

### Resultado esperado

```
Tests:  180 passed
Duration: ~25s
```

> Los tests usan **SQLite en memoria** (`:memory:`) — no afectan tu base de datos MySQL.

---

## ⚙️ Variables de Entorno Importantes

```env
# Base de datos
DB_CONNECTION=mysql
DB_DATABASE=naturacor

# IAs (opcionales — el sistema funciona sin ellas en modo offline)
GROQ_API_KEY=gsk_...
GEMINI_API_KEY=AIza...

# Fidelización
FIDELIZACION_MONTO=500              # Umbral en S/ para premio de naturales
FIDELIZACION_MAXIMO_PREMIO=30       # Valor máximo del premio
FIDELIZACION_CORDIALES_MONTO=500    # Umbral de cordiales
FIDELIZACION_INICIO=2026-01-01      # Inicio del programa
FIDELIZACION_FIN=2026-12-31         # Fin del programa

# Negocio
IGV_PORCENTAJE=18                   # IGV incluido en precios
STOCK_MINIMO_DEFAULT=5              # Stock mínimo por defecto
```

---

## 🏗️ Arquitectura y Buenas Prácticas

### Patrones implementados

- **MVC estricto**: Controladores delgados, modelos con lógica de dominio
- **Transacciones DB**: Toda operación de venta usa `DB::beginTransaction()` para consistencia
- **SoftDeletes**: Ventas, productos y clientes no se eliminan físicamente
- **Validación centralizada**: Reglas de validación en cada controlador
- **Config vs .env**: Uso de `config('naturacor.*')` en lugar de `env()` directo en controladores
- **Logs de auditoría**: Registro automático de acciones críticas (ventas, reclamos)
- **Factory pattern**: Factories para todos los modelos principales (tests)
- **Middleware de roles**: Control de acceso con `role:admin` y Spatie Permission
- **API endpoints**: Búsqueda AJAX de productos y clientes desde el POS

### Seguridad

- Protección CSRF automática en todas las rutas POST
- Autenticación obligatoria para todas las rutas (excepto login)
- Roles: `admin` (acceso total) y `empleado` (operativo)
- Aislamiento de sucursales: empleados solo ven datos de su sucursal
- Bcrypt con 12 rounds para contraseñas
- Validación de entrada en todos los formularios

### CI/CD

- **GitHub Actions**: Pipeline automático en cada push/PR a `main`
- PHP 8.2 + SQLite en memoria + todos los tests
- Cache de dependencias Composer
- 4 workflows: tests, issues, pull requests, changelog

---

## 📝 Comandos Artisan Personalizados

```bash
# Limpiar todas las ventas y datos relacionados (para empezar de cero)
php artisan limpiar:ventas

# Reiniciar acumulados de fidelización (al fin de año)
php artisan fidelizacion:reiniciar [--force]
```

---

## 📝 Notas para el Equipo

- El archivo `.env` **no se sube a GitHub** (está en `.gitignore`). Cada desarrollador debe crear el suyo con `cp .env.example .env`.
- La carpeta `vendor/` tampoco se sube — se instala con `composer install`.
- Si usas XAMPP, asegúrate de que la extensión `pdo_sqlite` esté habilitada en `php.ini` para que los tests funcionen.
- Los tests son **independientes entre sí** y utilizan `RefreshDatabase` para limpiar la BD entre cada test.
