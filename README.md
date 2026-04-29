# 🌿 NATURACOR — Sistema Web Empresarial

![Tests CI/CD](https://github.com/75220834-cloud/PROYECTO-NATURACOR/actions/workflows/tests.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![Tests](https://img.shields.io/badge/Tests-292%20passed-brightgreen?logo=phpunit)
![License](https://img.shields.io/badge/license-MIT-blue)

Sistema web integral para la operación de tiendas naturistas, con punto de venta, inventario, clientes, caja, fidelización, recetario, asistente IA, recomendación inteligente, pronóstico de demanda y métricas de evaluación. Desarrollado como tesis de **Pruebas y Calidad de Software** y validado con **292 tests automatizados**.

---

## Tabla de Contenidos

- [Descripción General](#descripción-general)
- [Módulos del Sistema](#módulos-del-sistema)
- [Mejoras Recientes (Abril 2026)](#mejoras-recientes-abril-2026)
- [Sistema Inteligente](#sistema-inteligente)
- [Sistema de Métricas](#sistema-de-métricas)
- [Pronóstico de Demanda y Mapa de Calor](#pronóstico-de-demanda-y-mapa-de-calor)
- [Carga Masiva con Excel](#carga-masiva-con-excel)
- [Almacenamiento de Imágenes (Cloudinary)](#almacenamiento-de-imágenes-cloudinary)
- [Hardware Soportado](#hardware-soportado)
- [Arquitectura y Flujo](#arquitectura-y-flujo)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Stack Tecnológico](#stack-tecnológico)
- [Instalación](#instalación)
- [Variables de Entorno](#variables-de-entorno)
- [Pruebas Automatizadas](#pruebas-automatizadas)
- [Despliegue en Producción](#despliegue-en-producción)
- [Generalización del Sistema](#generalización-del-sistema)
- [Autores](#autores)

---

## Descripción General

NATURACOR está construido en Laravel 12 y sigue arquitectura MVC con control de acceso por roles (`admin` y `empleado`). El sistema cubre el flujo comercial completo de una tienda naturista: venta, control de stock, caja, fidelización, emisión de boletas, atención al cliente y analítica. Adicionalmente incorpora un sistema híbrido de recomendación con métricas de evaluación científica y un módulo de pronóstico de demanda con suavizado exponencial simple (SES).

El proyecto está diseñado como caso de estudio académico para demostrar buenas prácticas de pruebas y calidad de software: la suite de 292 tests automatizados (unitarios + integración) se ejecuta en cada `git push` mediante GitHub Actions, garantizando que ninguna regresión entre a la rama principal sin ser detectada.

---

## Módulos del Sistema

### Módulos operativos
- **POS y Ventas:** registro de venta con IGV incluido, cálculo automático de subtotales, integración con caja, fidelización y métricas. Soporta búsqueda AJAX de productos, escáner USB de código de barras y panel de sugerencias inteligentes en tiempo real.
- **Inventario:** gestión de productos, control de stock mínimo con alertas, búsqueda por nombre o código de barras, exportación e importación masiva en Excel.
- **Clientes:** registro y consulta por DNI, historial de compras, ficha con perfil de salud editable, programa de fidelización con acumulado.
- **Caja:** apertura y cierre por sesión, registro de movimientos, totales por método de pago, cálculo automático de diferencia al cierre.
- **Fidelización:** acumulación de compras de productos naturales, generación automática de canjes al alcanzar el umbral configurable y módulo de entrega de premios.
- **Cordiales:** ventas de bebidas naturales con precios fijos, promoción del litro puro (toma gratis incluida) y registro de cortesías.
- **Recetario:** relación enfermedades ↔ productos recomendados con instrucciones de uso, búsqueda por categoría e import/export Excel.
- **Reclamos:** registro y seguimiento con flujo de estados (pendiente → en proceso → resuelto), escalado al administrador y log de auditoría.
- **Reportes y Boletas:** reportes filtrables por fecha/sucursal/empleado/método de pago, boleta PDF tamaño A4 y ticket térmico de 80 mm o 58 mm.

### Módulos inteligentes
- **Asistente IA:** consultas de negocio en lenguaje natural con cascada Groq → Gemini → modo offline.
- **Sistema híbrido de recomendación:** perfil de afinidad cliente-enfermedad + co-ocurrencia producto-producto + tendencia por sucursal.
- **Métricas del recomendador:** dashboard con `precision@k`, `hit_rate@k`, atribución de compra y ticket promedio comparado.
- **Pronóstico de demanda:** modelo SES semanal con widget de productos en riesgo de quiebre.
- **Mapa de calor de enfermedades:** matriz Enfermedades × Sucursales con clustering jerárquico y export CSV.
- **A/B Testing:** experimento con grupo control y tratamiento, prueba estadística Welch t-test.

### Módulos administrativos (solo admin)
- **Sucursales, Usuarios y Roles, Dashboard ejecutivo** con KPIs del día/semana/mes y widget de pronóstico.

---

## Mejoras Recientes (Abril 2026)

Durante abril de 2026 el sistema recibió siete mejoras significativas que lo prepararon para despliegue en producción y carga masiva de datos:

| # | Mejora | Descripción |
|---|---|---|
| 1 | **Cloudinary** | Sistema de imágenes con detección automática de entorno: en local guarda en `storage/app/public`, en producción sube a Cloudinary CDN. Migración cero-fricción gracias al helper `producto_image_url()`. |
| 2 | **Escáner USB físico** | Campo `codigo_barras` único por producto, endpoint AJAX `/api/productos/barcode` y validación en formularios. El POS ya tiene input con autofocus para escaneo continuo. |
| 3 | **Ticket térmico** | Vista optimizada para impresoras térmicas de 58 mm y 80 mm con botón de impresión directa desde la boleta. |
| 4 | **POS rediseñado** | Layout de dos columnas: catálogo a la izquierda (50%), panel derecho dividido en cliente arriba + sugerencias IA y carrito 50/50. Responsivo a partir de 1200 px. |
| 5 | **Perfil de salud en cliente** | Sección con chips editables de enfermedades en `/clientes/{id}`, sincronizado con el endpoint `/api/clientes/{id}/padecimientos` que ya consume el POS. |
| 6 | **Excel productos** | Exportar inventario, descargar plantilla con encabezados estilizados e importar masivamente con validación. |
| 7 | **Excel recetario** | Mismo flujo aplicado al recetario, con `syncWithoutDetaching` que nunca borra relaciones existentes y aceptación de separadores `\|` o `;` para los productos. |

---

## Sistema Inteligente

### ¿Cómo funciona la recomendación?

El motor `app/Services/Recommendation/RecomendacionEngine.php` combina tres componentes ponderables vía `.env`:

1. **Componente de perfil (content-based):** productos vinculados a las enfermedades con mayor score en el perfil del cliente, calculado por `PerfilSaludService` usando decaimiento exponencial sobre compras y compensación por grado de producto.
2. **Componente de co-ocurrencia (collaborative filtering):** índice Jaccard producto-producto materializado por `CoocurrenciaService` durante el job nocturno.
3. **Componente de tendencia:** productos más vendidos recientemente por sucursal.

La fusión es lineal y ponderable mediante `REC_PESO_PERFIL`, `REC_PESO_COOCURRENCIA` y `REC_PESO_TRENDING` en `config/recomendaciones.php`.

### Construcción del perfil del cliente

`PerfilSaludService::reconstruirPerfil()` materializa la tabla `cliente_perfil_afinidad` combinando dos fuentes:

- **Señal observada:** historial de ventas completadas con decaimiento `exp(-λ · días)` y normalización min-max por cliente.
- **Señal declarada:** padecimientos auto-reportados desde el panel inline del POS o la ficha del cliente, inyectados con score base configurable (`REC_PADECIMIENTO_SCORE_BASE`).

Esta combinación de "declarado + observado" es uno de los aportes diferenciadores del sistema respecto a recomendadores tradicionales basados solo en compras pasadas.

### Endpoints del recomendador

- `GET /api/recomendaciones/{cliente}?limite=6` — devuelve productos sugeridos con justificación (perfil / tendencia / cross-sell) y meta-información de la sesión.
- `POST /api/recomendaciones/evento` — registra interacciones del usuario (`clic`, `agregada`).

### Caché y optimización

- Caché del JSON por `cliente / sucursal / límite` con TTL configurable (`REC_CACHE_MINUTOS`).
- Recomputación de perfil solo cuando vence la validez (`REC_PERFIL_HORAS`) o se solicita `?refresh=1`.
- Inserción en bloque de eventos `mostrada` para minimizar overhead transaccional.
- Invalidación automática del caché tras guardar padecimientos.

---

## Sistema de Métricas

La tabla `recomendacion_eventos` registra eventos append-only para evaluación científica del recomendador:

- `mostrada` — el sistema sugirió el producto.
- `clic` — el usuario navegó al producto desde la sugerencia.
- `agregada` — el usuario lo agregó al carrito.
- `comprada` — la venta efectivamente se concretó (atribución por ventana de lookback).

### Atribución de compra

`DetalleVentaObserver` dispara automáticamente `MetricsService::registrarCompradaSiCorresponde()` al crear cada detalle de venta. La atribución usa una ventana configurable mediante `REC_METRICAS_LOOKBACK_HORAS` (72 horas por defecto).

### Métricas calculadas

El dashboard `/metricas/recomendaciones` calcula por periodo y opcionalmente por sucursal:

- Conteos absolutos de cada tipo de evento.
- Conversión `compra/agregada` y `compra/mostrada`.
- `precision@k` y `hit_rate@k` (configurable con `REC_METRICAS_K`).
- Ticket promedio con y sin atribución de recomendación.
- Top productos en eventos de recomendación.
- Comparativa A/B grupo control vs tratamiento con Welch t-test y Cohen's d.

---

## Pronóstico de Demanda y Mapa de Calor

### Pronóstico semanal con SES

El job semanal `ActualizarDemandaJob` materializa `producto_demanda_semana` (histórico) y `producto_prediccion_demanda` (resultado del modelo SES). El widget del dashboard cruza la predicción con el stock actual y resalta los productos en riesgo de quiebre antes de que ocurra. Hiperparámetros configurables: `REC_FORECAST_ALPHA`, `REC_FORECAST_HISTORIA`, `REC_FORECAST_MIN_OBS`.

### Mapa de calor de enfermedades

Vista ejecutiva en `/metricas/heatmap-enfermedades` que muestra una matriz Enfermedades × Sucursales con tres modos de evidencia:

- **Declarada:** padecimientos auto-reportados desde `cliente_padecimientos`.
- **Observada:** clientes con afinidad inferida desde compras (score ≥ umbral en `cliente_perfil_afinidad`).
- **Combinada:** unión deduplicada de ambos conjuntos.

Incluye clustering jerárquico opcional para revelar grupos de enfermedades co-ocurrentes y export CSV (`/metricas/heatmap-enfermedades/export.csv`).

---

## Carga Masiva con Excel

Tanto el módulo de **productos** como el de **recetario** soportan tres operaciones desde el listado:

- **Exportar Excel** — descarga el inventario actual con todas las columnas relevantes.
- **Plantilla Excel** — descarga un archivo vacío con encabezados estilizados (verde NATURACOR, Calibri 12 negrita) y una fila de ejemplo.
- **Importar Excel** — sube un archivo `.xlsx`, `.xls` o `.csv` y procesa cada fila.

### Reglas del importador

- **Match por nombre case-insensitive** — `DIABETES` y `diabetes` matchean con la misma fila.
- **Update solo de campos llenos** — celdas vacías no pisan los valores existentes en la BD.
- **Recetario usa `syncWithoutDetaching`** — nunca borra relaciones producto-enfermedad ya creadas manualmente.
- **Productos en recetario aceptan `|` o `;`** como separadores en la celda.
- **Errores parciales no frenan la importación** — productos no encontrados se reportan como mensajes y la enfermedad se crea de todas formas con los productos sí encontrados.
- **Filas vacías ignoradas silenciosamente** — Excel suele tener filas vacías al final.
- **Pre-carga de productos a memoria** — sin queries N+1 aunque importes miles de filas.

---

## Almacenamiento de Imágenes (Cloudinary)

El sistema decide automáticamente dónde guardar las imágenes de producto:

- **Local (sin `CLOUDINARY_URL` configurado):** las imágenes se guardan en `storage/app/public/productos/` y se sirven mediante `asset('storage/...')`.
- **Producción (con `CLOUDINARY_URL`):** las imágenes se suben a Cloudinary CDN y se guarda la URL absoluta en BD.

El servicio `App\Services\CloudinaryUploader` y el helper global `producto_image_url($producto)` hacen toda la magia de forma transparente. La migración entre ambos modos es **cero-fricción**: las imágenes locales viejas siguen funcionando aunque las nuevas vayan al CDN, gracias a la lógica de detección por prefijo `http`.

---

## Hardware Soportado

| Dispositivo | Protocolo | Uso en NATURACOR |
|---|---|---|
| Computadora / Laptop | HTTP/HTTPS | Acceso completo al sistema desde el navegador |
| Impresora térmica 80 mm o 58 mm | ESC/POS vía navegador | Impresión directa de boletas y tickets de venta |
| Lector de código de barras USB | Emulación de teclado (HID) | Búsqueda de productos por escaneo en POS y registro de productos |
| Tablet / Móvil | HTTP/HTTPS | Consulta de inventario y catálogo público (responsivo) |

---

## Arquitectura y Flujo

### Capas

- **Framework:** Laravel 12, MVC + Eloquent ORM.
- **Servicios de dominio:** carpeta `app/Services` con módulos especializados (`Analytics`, `Fidelizacion`, `Forecasting`, `Recommendation`).
- **Observer:** `DetalleVentaObserver` enlaza ventas con métricas sin acoplar el controlador.
- **Helpers globales:** `app/Helpers/image_helpers.php` cargado vía `composer.json` para resolver URLs de imágenes.
- **Autorización:** Spatie Permission con middleware `role:admin` para rutas administrativas.
- **Jobs en cola:** `ReconstruirPerfilesJob`, `ReconstruirCoocurrenciaJob`, `ActualizarDemandaJob` programados en `routes/console.php`.

### Flujo operativo de venta con recomendación

1. El empleado autenticado abre el POS y selecciona un cliente desde el autocompletado.
2. El POS solicita las recomendaciones al endpoint `/api/recomendaciones/{cliente}`.
3. El motor ejecuta el algoritmo híbrido si no hay caché válido y registra `mostrada` en bloque.
4. Si el usuario interactúa con una sugerencia, el JS registra `clic` o `agregada` vía `POST /api/recomendaciones/evento`.
5. Al confirmar la venta, el observer dispara `MetricsService::registrarCompradaSiCorresponde()` para cada producto recomendado que efectivamente se compró.
6. El dashboard de métricas consolida todos los eventos por periodo y muestra precision@k, hit_rate@k y diferencias entre grupo control y tratamiento.

---

## Estructura del Proyecto

```text
naturacor/
├── app/
│   ├── Console/Commands/         # RecomendacionCoocurrencia, ReiniciarFidelizacion
│   ├── Exports/                  # ProductosExport, EnfermedadesExport
│   ├── Helpers/                  # image_helpers.php (autoloaded)
│   ├── Http/Controllers/         # 19 controladores
│   ├── Imports/                  # ProductosImport, EnfermedadesImport
│   ├── Jobs/Recommendation/      # Jobs nocturnos
│   ├── Models/                   # 21 modelos Eloquent
│   ├── Observers/                # DetalleVentaObserver
│   ├── Providers/                # AppServiceProvider
│   └── Services/
│       ├── Analytics/            # HeatmapEnfermedadesService
│       ├── CloudinaryUploader.php
│       ├── Fidelizacion/         # FidelizacionService
│       ├── Forecasting/          # DemandaForecastService
│       └── Recommendation/       # AbTestingService, CoocurrenciaService,
│                                 # MetricsService, PerfilSaludService,
│                                 # RecomendacionEngine
├── config/                       # naturacor, recomendaciones, services, excel
├── database/migrations/          # 34 migraciones
├── resources/views/              # 22 carpetas modulares
├── routes/                       # web, console (con schedule), auth
├── tests/
│   ├── Unit/                     # 12 archivos
│   └── Feature/                  # 20 archivos (incluye Excel + Forecasting + Jobs)
├── .github/workflows/            # CI/CD GitHub Actions
└── docs
```

---

## Stack Tecnológico

- **Backend:** PHP 8.2+, Laravel 12.
- **Base de datos:** MySQL 8.0 (operación) y SQLite en memoria (testing).
- **Frontend:** Blade + Bootstrap 5.3 + Vite + Tailwind CSS 4.
- **Autenticación:** Laravel Breeze.
- **Roles y permisos:** Spatie Laravel Permission 6.25.
- **PDF:** Barryvdh DomPDF 3.1.
- **Excel:** Maatwebsite Excel 3.1 (export/import).
- **Imágenes en CDN:** Cloudinary PHP SDK 3.1 con fallback automático a disco local.
- **IA externa:** Groq (Llama 3.3 70B) y Google Gemini 1.5 Flash, ambos opcionales.
- **Testing:** PHPUnit 11.5 + Mockery 1.6 + FakerPHP 1.23.
- **Linter:** Laravel Pint (PSR-12).
- **CI/CD:** GitHub Actions con SQLite en memoria.

---

## Instalación

### 1. Clonar repositorio

```bash
git clone https://github.com/75220834-cloud/PROYECTO-NATURACOR.git
cd PROYECTO-NATURACOR/naturacor
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Configurar entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus credenciales de base de datos. Las API keys de IA y Cloudinary son **opcionales** — el sistema funciona sin ellas en modo offline / disco local.

### 4. Crear base de datos y ejecutar migraciones

```bash
php artisan migrate --seed
```

El seeder `AdminSeeder` carga roles, un usuario admin (`admin@naturacor.com` / `Admin123!`), un empleado de muestra y datos demo.

### 5. Crear enlace simbólico de storage (para imágenes locales)

```bash
php artisan storage:link
```

### 6. Compilar assets e iniciar la aplicación

```bash
npm run build
php artisan serve
```

Abre `http://localhost:8000` e inicia sesión.

### 7. (Opcional) Activar el scheduler en local

Para que los jobs nocturnos del recomendador y del pronóstico semanal corran:

```bash
php artisan schedule:work
```

Déjalo corriendo en una terminal separada durante el desarrollo.

---

## Variables de Entorno

```env
# Base
APP_NAME=NATURACOR
APP_ENV=local
APP_LOCALE=es
APP_FAKER_LOCALE=es_PE
DB_CONNECTION=mysql
DB_DATABASE=naturacor

# Seguridad
BCRYPT_ROUNDS=12

# Negocio
IGV_PORCENTAJE=18
STOCK_MINIMO_DEFAULT=5
FIDELIZACION_MONTO=500
FIDELIZACION_MAXIMO_PREMIO=30
FIDELIZACION_CORDIALES_MONTO=500
FIDELIZACION_INICIO=2026-01-01
FIDELIZACION_FIN=2026-12-31

# IA (opcionales)
GROQ_API_KEY=
GEMINI_API_KEY=

# Cloudinary (opcional — vacío en local = guardar en storage)
CLOUDINARY_URL=
CLOUDINARY_FOLDER=naturacor/productos

# Recomendador (opcionales — defaults en config/recomendaciones.php)
REC_VENTANA_DIAS=365
REC_LAMBDA=0.008
REC_TOP_ENFERMEDADES=10
REC_TRENDING_DIAS=14
REC_PESO_PERFIL=1.0
REC_PESO_TRENDING=0.45
REC_PESO_COOCURRENCIA=0.35
REC_LIMITE_DEFAULT=10
REC_LIMITE_MAX=30
REC_PERFIL_HORAS=6
REC_CACHE_MINUTOS=10
REC_METRICAS_LOOKBACK_HORAS=72
REC_METRICAS_K=6
REC_METRICAS_DASHBOARD_DIAS=30

# Pronóstico de demanda (Bloque 5)
REC_FORECAST_ALPHA=0.4
REC_FORECAST_HISTORIA=16
REC_FORECAST_MIN_OBS=8
REC_FORECAST_TOP_WIDGET=10

# Mapa de calor (Bloque 6)
REC_HEATMAP_DIAS=90
REC_HEATMAP_UMBRAL_SCORE=0.20
REC_HEATMAP_TOP_SUC=3
REC_HEATMAP_CLUSTER=true

# A/B testing
REC_MODO_AB=false
REC_AB_ESTRATEGIA=hash_cliente
REC_AB_PORCENTAJE_CONTROL=50

# Jobs nocturnos
REC_JOB_PERFILES_HORA=02:00
REC_JOB_COO_HORA=02:30
REC_JOB_DEMANDA_HORA=03:00
REC_JOB_DEMANDA_DIA=1
```

---

## Pruebas Automatizadas

El proyecto cuenta con **292 tests automatizados** distribuidos así:

- **12 archivos unitarios** en `tests/Unit/`: lógica de modelos, cálculos de IGV, perfiles de afinidad, A/B testing, suavizado exponencial, mapa de calor.
- **20 archivos de integración** en `tests/Feature/`: flujos HTTP, control de roles, jobs en cola, recomendaciones, métricas, fidelización, Excel productos y recetario, autenticación, seguridad CSRF.

### Ejecutar los tests

```bash
# Toda la suite
php artisan test

# Solo unitarios
php artisan test --testsuite=Unit

# Filtrar por nombre
php artisan test --filter=RecetarioExcelTest

# Con cobertura (requiere Xdebug o PCOV)
php artisan test --coverage
```

### CI/CD

GitHub Actions ejecuta automáticamente los 292 tests en cada push y pull request a la rama `main`. El workflow está en `.github/workflows/tests.yml` y usa SQLite en memoria para velocidad y aislamiento total entre tests.

---

## Despliegue en Producción

El sistema está preparado para desplegarse en **Railway.app** con `Procfile` y `nixpacks.toml` ya configurados en la raíz. Para producción:

1. Crear cuenta en Railway y conectar el repositorio GitHub.
2. Agregar plugin de MySQL desde el panel.
3. Configurar variables de entorno (incluyendo `APP_KEY`, `DB_*` desde el plugin, y opcionalmente `CLOUDINARY_URL`, `GROQ_API_KEY`, `GEMINI_API_KEY`).
4. Hacer push a `main` — Railway despliega automáticamente.
5. Configurar dominio personalizado (compatible con Porkbun, Namecheap, Cloudflare).
6. Habilitar cron en el servidor para que el scheduler corra cada minuto:
   ```cron
   * * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1
   ```

La guía completa con capturas, costos y troubleshooting está en `guia_despliegue_produccion.md`.

---

## Generalización del Sistema

NATURACOR **no es 100 % reutilizable sin ajustes**, porque parte del dominio está acoplada al negocio naturista. Sin embargo, la arquitectura técnica sí lo es.

### Acoplado al negocio
- Reglas específicas de fidelización (umbrales en soles, premio Botella Nopal 2L).
- Recetario con taxonomía de enfermedades y productos del catálogo naturista.
- Textos y mensajería de la marca.
- Configuración de empresa en `config/naturacor.php`.

### Reutilizable con bajo esfuerzo
- POS, ventas, caja, boletas, reportes.
- Gestión de clientes, usuarios, sucursales, roles.
- Estructura técnica del recomendador híbrido.
- Sistema de métricas append-only y dashboard.
- Pronóstico de demanda con SES.
- Mapa de calor de variables × sucursales.
- Carga masiva en Excel con validación.
- Storage CDN con fallback local.

### Cambios necesarios para otra tienda
- Reemplazar reglas de fidelización en `config/naturacor.php`.
- Reconfigurar taxonomía del recetario o sustituir por categorías del nuevo rubro.
- Ajustar textos, reportes y catálogos del dominio.
- Revisar seeders y parámetros de negocio.

---

## Autores

- **Bendezu Lagos Jack Joshua** — Líder de proyecto y desarrollo.
- **Reyes Cordero Ítalo Eduardo** — Desarrollo y aseguramiento de calidad.
- **Julca Laureano Dickmar Wilber** — Análisis de requerimientos y pruebas.

**Cliente:** Anita María Cordero Campos — Propietaria de NATURACOR, Jauja, Junín, Perú.

**Curso:** Pruebas y Calidad de Software — 7mo ciclo.

---

## Licencia

Proyecto académico bajo licencia MIT. Contribuciones bienvenidas vía Pull Request.

---

🌿 *NATURACOR — De Jauja al mundo, naturalmente.*