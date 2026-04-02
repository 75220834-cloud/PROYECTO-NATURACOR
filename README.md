# 🌿 NATURACOR — Sistema Web Empresarial

![Tests CI/CD](https://github.com/75220834-cloud/PROYECTO-NATURACOR/actions/workflows/tests.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![Tests](https://img.shields.io/badge/Tests-125%2B-brightgreen?logo=phpunit)


> Sistema de Gestión Integral para Tiendas Naturistas con Módulo de Consultoría Inteligente y Recomendación de Productos basado en el Perfil de Salud del Cliente.

**Curso:** Pruebas y Calidad de Software  
**Docente:** Maglioni Arana Caparachin  
**Integrantes:**
- BENDEZU LAGOS JACK JOSHUA
- JULCA LAUREANO DICKMAR WILBER
- REYES CORDERO ITALO EDUARDO

---

## 🛠️ Requisitos previos

Asegúrate de tener instalado en tu máquina:

| Herramienta | Versión mínima | Link |
|---|---|---|
| **PHP** | 8.2+ | https://www.php.net/downloads |
| **Composer** | 2.x | https://getcomposer.org |
| **MySQL** | 8.0+ | https://dev.mysql.com/downloads |
| **Node.js** | 18+ | https://nodejs.org |
| **Git** | cualquier | https://git-scm.com |
| **XAMPP** (opcional) | 8.2+ | https://www.apachefriends.org |

> 💡 En Windows se recomienda usar **XAMPP** — incluye PHP, MySQL y Apache en un solo instalador.

---

## 🚀 Instalación paso a paso

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

Luego abre `.env` y edita las líneas de base de datos:

```env
APP_NAME=NATURACOR
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=naturacor
DB_USERNAME=root
DB_PASSWORD=          # tu contraseña de MySQL (vacío si usas XAMPP por defecto)

# Opcional: para el Asistente IA
GEMINI_API_KEY=tu_api_key_aqui
GROQ_API_KEY=tu_api_key_aqui
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

> Esto crea todas las tablas y carga los datos iniciales (roles, usuario admin, productos de ejemplo).

### 8. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

Abre tu navegador en: **http://localhost:8000**

---

## 🔑 Credenciales por defecto

| Rol | Email | Contraseña |
|---|---|---|
| **Administrador** | admin@naturacor.com | password |
| **Empleado** | empleado@naturacor.com | password |

> ⚠️ Cambia estas credenciales en producción.

---

## ✅ Ejecutar las pruebas

El proyecto incluye **125+ casos de prueba** que cubren todos los módulos del sistema.

### Ejecutar todos los tests:

```bash
php artisan test
```

### Ejecutar un módulo específico:

```bash
php artisan test tests/Feature/RecetarioTest.php
php artisan test tests/Feature/VentaTest.php
php artisan test tests/Feature/ReclamoTest.php
php artisan test tests/Feature/IATest.php
php artisan test tests/Feature/FidelizacionTest.php
php artisan test tests/Feature/CordialTest.php
php artisan test tests/Unit/
```

### Resultado esperado:

```
Tests:  125 passed
Duration: ~20s
```

> Los tests usan **SQLite en memoria** (`:memory:`) — no afectan tu base de datos MySQL.

---

## 🧩 Módulos del sistema

| Módulo | Descripción |
|---|---|
| 🛒 **POS (Punto de Venta)** | Registro de ventas, IGV incluido, boletas B001-XXXXXX |
| 📦 **Inventario** | Stock con alertas de mínimo, SoftDelete |
| 👥 **Clientes** | Registro por DNI, historial y fidelización |
| 💰 **Caja** | Apertura/cierre, movimientos, diferencias |
| 🏪 **Sucursales** | Multi-sede, roles por sucursal |
| 🌿 **Recetario** | Enfermedades → productos recomendados |
| 🤖 **Asistente IA** | Groq (Llama 3) + Gemini 1.5 Flash + modo offline |
| 📊 **Reportes** | Ventas por período, sucursal, método de pago |
| 📋 **Reclamos** | Registro, seguimiento y escalado de reclamos |
| 🥤 **Cordiales** | Gestión de ventas de cordiales y cortesías |

---

## 🗂️ Estructura del proyecto

```
naturacor/
├── app/
│   ├── Http/Controllers/    # Controladores de todos los módulos
│   └── Models/              # Modelos Eloquent
├── database/
│   ├── migrations/          # Migraciones de BD
│   ├── factories/           # Factories para tests
│   └── seeders/             # Datos iniciales
├── resources/views/         # Vistas Blade por módulo
├── routes/web.php           # Rutas de la aplicación
└── tests/
    ├── Feature/             # Tests de integración (HTTP)
    └── Unit/                # Tests unitarios (modelos, lógica)
```

---

## ⚙️ Variables de entorno importantes

```env
# Base de datos
DB_DATABASE=naturacor

# IAs (opcionales — el sistema funciona sin ellas en modo offline)
GEMINI_API_KEY=AIza...
GROQ_API_KEY=gsk_...

# Fidelización
NATURACOR_FIDELIZACION_MONTO=250        # Umbral en S/ para premio
NATURACOR_FIDELIZACION_MAXIMO_PREMIO=30  # Valor máximo del premio
```

---

## 🧪 Stack tecnológico

- **Backend:** Laravel 12 (PHP 8.2)
- **Base de datos:** MySQL 8 + SQLite (tests)
- **Frontend:** Bootstrap 5 + Blade Templates
- **Roles:** Spatie Laravel Permission
- **IAs:** Google Gemini 1.5 Flash + Groq (Llama 3.3 70B)
- **Tests:** PHPUnit (integrado en Laravel)

---

## 📝 Notas para el equipo

- El archivo `.env` **no se sube a GitHub** (está en `.gitignore`). Cada desarrollador debe crear el suyo con `cp .env.example .env`.
- La carpeta `vendor/` tampoco se sube — se instala con `composer install`.
- Si usas XAMPP, asegúrate de que la extensión `pdo_sqlite` esté habilitada en `php.ini` para que los tests funcionen.
