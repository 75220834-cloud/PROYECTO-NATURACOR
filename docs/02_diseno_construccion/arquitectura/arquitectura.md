# Arquitectura Detallada — NATURACOR

## Sistema Web Empresarial  
**Fecha:** 03/05/2026  
**Versión:** 1.2 — Alineada al código actual (`Http/Controllers`)  
**Stack:** Laravel 12 + MySQL + Vite + Bootstrap 5

---

## 1. Visión General

NATURACOR es un sistema web **multi-capa** que extiende el patrón MVC clásico de Laravel con una capa de servicios (`Services/`) para aislar la lógica pesada de los controladores, un sistema de observadores (`Observers/`) para desacoplar efectos secundarios, y jobs schedulados para procesamiento offline.

```mermaid
graph TB
    subgraph "Capa de Presentación"
        BROWSER["🌐 Navegador"]
        BLADE["Blade Templates + Bootstrap 5"]
        JS["JavaScript + AJAX"]
        VITE["Vite (build system)"]
    end
    
    subgraph "Capa de Aplicación (Laravel)"
        ROUTES["Routes (web.php)"]
        MW["Middleware (auth, role, csrf)"]
        CTRL["Controllers dominio (18) + Breeze (10)"]
        REQUESTS["Form Requests"]
    end
    
    subgraph "Capa de Servicios"
        RECO["RecomendacionEngine"]
        PERFIL["PerfilSaludService"]
        COOC["CoocurrenciaService"]
        METRICS["MetricsService"]
        AB["AbTestingService"]
        FID["FidelizacionService"]
        HEAT["HeatmapEnfermedadesService"]
        FORECAST["DemandaForecastService"]
        CLOUD["CloudinaryUploader"]
    end
    
    subgraph "Capa de Dominio"
        MODELS["Eloquent Models (21)"]
        OBS["DetalleVentaObserver"]
        EVENTS["Events / Observers"]
    end
    
    subgraph "Capa de Datos"
        MYSQL["MySQL 8.0"]
        SQLITE["SQLite (testing)"]
        CACHE["Laravel Cache"]
    end
    
    subgraph "Servicios Externos"
        GROQ["Groq API (Llama 3.3 70B)"]
        GEMINI["Google Gemini 1.5 Flash"]
        CLOUDINARY["Cloudinary CDN"]
    end
    
    subgraph "Infraestructura"
        SCHEDULER["Laravel Scheduler"]
        QUEUE["Queue (sync/database)"]
        CICD["GitHub Actions CI/CD"]
    end
    
    BROWSER --> ROUTES
    ROUTES --> MW --> CTRL
    CTRL --> RECO
    CTRL --> FID
    CTRL --> FORECAST
    CTRL --> HEAT
    CTRL --> MODELS
    RECO --> PERFIL
    RECO --> COOC
    RECO --> METRICS
    RECO --> AB
    MODELS --> MYSQL
    MODELS --> OBS
    OBS --> METRICS
    CTRL --> GROQ
    CTRL --> GEMINI
    SCHEDULER --> QUEUE
    CACHE --> RECO
```

---

## 2. Flujo de una Venta Completa

Este es el flujo más complejo del sistema. Involucra 8 componentes y 4 tablas en una sola transacción:

```mermaid
sequenceDiagram
    participant E as 👤 Empleado (POS)
    participant VC as VentaController
    participant DB as DB::transaction
    participant P as Producto
    participant V as Venta
    participant DV as DetalleVenta
    participant CV as CordialVenta
    participant FS as FidelizacionService
    participant CS as CajaSesion
    participant LA as LogAuditoria
    participant OBS as DetalleVentaObserver
    participant MS as MetricsService
    
    E->>VC: POST /ventas (items, cordial, metodo_pago, cliente_id)
    VC->>VC: Validar reglas
    VC->>DB: beginTransaction()
    
    loop Cada item
        VC->>P: lockForUpdate() + findOrFail()
        P-->>VC: Producto (con lock)
        VC->>VC: Validar stock >= cantidad
        VC->>VC: Calcular precioFinal
    end
    
    VC->>VC: Calcular IGV = subtotal × 18/118
    VC->>V: save() (Venta nueva)
    VC->>V: generarNumeroBoleta()
    
    loop Cada línea
        VC->>DV: create(venta_id, producto_id, cantidad, ...)
        DV->>OBS: created(detalleVenta)
        OBS->>MS: registrarCompradaSiCorresponde()
        VC->>P: decrement('stock', cantidad)
    end
    
    opt Si hay cordiales
        loop Cada cordial
            VC->>CV: create(tipo, precio, cantidad)
            opt Si litro_puro_s80
                VC->>CV: create(toma gratis)
            end
        end
    end
    
    VC->>FS: procesarFidelizacion(venta, cliente, lineas)
    FS-->>VC: canjes generados (o [])
    
    opt Si hay caja abierta
        VC->>CS: increment(total_{metodo}, total)
        VC->>CS: increment(total_esperado, total)
    end
    
    VC->>LA: create(venta.creada, datos)
    VC->>DB: commit()
    VC-->>E: JSON {success, venta_id, numero_boleta, canjes, promos}
```

---

## 3. Arquitectura del Motor de Recomendación

### 3.1. Pipeline Híbrido

El motor implementa un sistema de recomendación híbrido con tres señales fusionadas por pesos configurables:

```mermaid
graph LR
    subgraph "Señal 1: CONTENIDO"
        S1A["Compras históricas del cliente"]
        S1B["Grafo enfermedad_producto"]
        S1C["Decaimiento temporal e^(-λ·días)"]
        S1D["PerfilSaludService"]
        S1A --> S1D
        S1B --> S1D
        S1C --> S1D
        S1D --> S1E["Score perfil × 100"]
    end
    
    subgraph "Señal 2: TENDENCIA"
        S2A["Ventas últimos 14 días"]
        S2B["Filtro por sucursal"]
        S2C["log(1 + unidades) normalizado"]
        S2A --> S2C
        S2B --> S2C
        S2C --> S2D["Score trending × 100"]
    end
    
    subgraph "Señal 3: COLABORATIVO"
        S3A["Carrito actual del POS"]
        S3B["Matriz co-ocurrencia"]
        S3C["CoocurrenciaService"]
        S3A --> S3C
        S3B --> S3C
        S3C --> S3D["Score cooc × 100"]
    end
    
    S1E --> FUSION["Fusión ponderada"]
    S2D --> FUSION
    S3D --> FUSION
    
    FUSION --> BOOST["Boost ×1.5 si perfil + cooc coinciden"]
    BOOST --> SELECT["Selección diversa (MMR)"]
    SELECT --> OUTPUT["Top-K recomendaciones"]
```

### 3.2. Fórmula de Scoring

```
score_final = (peso_perfil × comp_perfil) + (peso_trending × comp_trend) + (peso_cooc × comp_cooc)

Si comp_cooc > 0 (señal redundante perfil + carrito):
    score_final *= boost_carrito (1.5)
```

**Pesos configurables** (`config/recommendaciones.php`):
- `peso_perfil`: 1.0 (dominante)
- `peso_trending`: 0.45 (moderado)
- `peso_coocurrencia`: 0.35 (complementario)
- `boost_carrito`: 1.5 (refuerzo por coincidencia)

### 3.3. Co-ocurrencia Item-Item (Bloque 2)

```mermaid
graph TB
    A["Histórico de ventas completadas"] --> B["Canastas: {venta → [productos]}"]
    B --> C["Contar pares (a<b) co-comprados"]
    C --> D["Calcular Jaccard"]
    C --> E["Calcular NPMI"]
    
    D --> F["J(A,B) = co(A,B) / (n_A + n_B - co(A,B))"]
    E --> G["NPMI = PMI / -log P(A,B)"]
    
    F --> H["Persistir en producto_coocurrencias"]
    G --> H
    
    H --> I["Consulta en tiempo real desde POS"]
```

**Implementación técnica:**
- **Truncate + insert** atómico dentro de transacción (regeneración completa).
- **Pares ordenados** `(a < b)` para evitar duplicados `(a,b)/(b,a)`.
- **Filtro de ruido:** Descarta pares con `co_count < min_co_count` (default: 2).
- **Ventana temporal:** Configurable (default: 90 días).

### 3.4. Experimentación A/B (Bloque 4)

```mermaid
graph LR
    A["Cliente llega al POS"] --> B{"A/B Testing activo?"}
    B -->|No| C["Grupo: sin_ab → muestra recos"]
    B -->|Sí| D["AbTestingService.asignarGrupo()"]
    D --> E{"Estrategia"}
    E -->|hash_cliente| F["crc32(id) % 100"]
    E -->|dia_par_impar| G["día % 2"]
    E -->|aleatorio| H["random_int(0,99)"]
    F --> I{"bucket < pct_control?"}
    G --> I
    H --> I
    I -->|Sí| J["Grupo: CONTROL → items: []"]
    I -->|No| K["Grupo: TRATAMIENTO → motor completo"]
    
    J --> L["Welch t-test + Cohen's d"]
    K --> L
```

**Tests estadísticos implementados en PHP puro (sin dependencias externas):**
- Welch's t-test (no asume varianzas iguales)
- Aproximación de p-valor (Abramowitz & Stegun + Beta incompleta regularizada)
- Cohen's d como tamaño de efecto
- Función lnGamma (aproximación de Lanczos)

### 3.5. Pronóstico de Demanda SES (Bloque 5)

```mermaid
graph TB
    A["Comando semanal: ActualizarDemandaJob"] --> B["materializarHistorico()"]
    B --> C["Agregar unidades por (producto, sucursal, semana ISO)"]
    C --> D["Persistir en producto_demanda_semana"]
    D --> E["recomputarPredicciones()"]
    E --> F["Para cada par (producto, sucursal):"]
    F --> G["Series temporales: [Y_0, Y_1, ..., Y_T]"]
    G --> H["S_t = α·Y_t + (1-α)·S_{t-1}"]
    H --> I["ŷ_{T+1} = S_T"]
    H --> J["MAE, MAPE, CI 95%"]
    I --> K["Persistir en producto_prediccion_demanda"]
    J --> K
    K --> L["Dashboard: 'Productos en riesgo de stock'"]
```

---

## 4. Arquitectura del Pipeline de Métricas

### 4.1. Embudo de Conversión

```mermaid
graph TB
    A["MOSTRADA"] -->|"MetricsService.registrarMostradas()"| B["Producto aparece en lista"]
    B --> C["CLIC"]
    C -->|"POS envía evento"| D["Cliente interesado"]
    D --> E["AGREGADA"]
    E -->|"POS agrega al carrito"| F["Intención de compra"]
    F --> G["COMPRADA"]
    G -->|"DetalleVentaObserver automático"| H["Conversión real"]
    
    H --> I["Precision@K"]
    H --> J["Hit Rate"]
    H --> K["Ticket promedio"]
    H --> L["Comparativa A/B"]
```

### 4.2. Atribución de Compra

El `DetalleVentaObserver` implementa **atribución por sesión con ventana temporal**:

1. Cuando se crea un `DetalleVenta` (línea de venta), el observer verifica:
   - ¿La venta tiene `cliente_id` y estado `completada`?
   - ¿Existe un evento `mostrada/clic/agregada` para ese `(cliente, producto)` en las últimas 72 horas?
   - ¿La sesión de recomendación tiene un `mostrada` vigente?
2. Si todas las condiciones se cumplen → registra evento `comprada` vinculado a la venta.
3. La herencia de `grupo_ab` se mantiene desde la `mostrada` original (trazabilidad A/B).

---

## 5. Mapa de Controladores y Rutas

| Controlador | Rutas principales | Middleware | Responsabilidad |
|------------|-------------------|------------|-----------------|
| `VentaController` | `GET /ventas/pos`, `POST /ventas`, `GET/PATCH/DELETE /ventas/{id}` | `auth` | POS + CRUD de ventas |
| `ProductoController` | `resource /productos`, `GET /api/productos/buscar`, `importar/exportar` | `auth` | Inventario completo |
| `ClienteController` | `resource /clientes`, `GET /api/clientes/dni`, `padecimientos` | `auth` | Clientes + perfil salud |
| `CajaController` | `GET /caja`, `POST abrir/movimiento/cerrar` | `auth` | Sesiones de caja |
| `RecomendacionController` | `GET /api/recomendaciones/{cliente}`, `POST evento` | `auth` | API de recomendación |
| `RecomendacionMetricasController` | `GET /metricas/recomendaciones` | `auth` | Dashboard de métricas |
| `HeatmapEnfermedadesController` | `GET /metricas/heatmap-enfermedades` | `auth` | Mapa de calor |
| `DashboardController` | `GET /dashboard` | `auth` | KPIs del negocio |
| `IAController` | `GET /ia`, `POST /ia/analizar` | `auth` | Asistente IA |
| `RecetarioController` | `resource /recetario` | `auth` | CRUD de enfermedades |
| `ReclamoController` | `resource /reclamos`, `POST escalar` | `auth` | Gestión de reclamos |
| `ReporteController` | `GET /reportes`, `POST generar` | `auth` | Reportes de ventas |
| `BoletaController` | `GET boletas/{venta}`, `pdf`, `ticket`, `whatsapp` | `auth` | Boletas |
| `CordialController` | `resource /cordiales`, `GET precios` | `auth` | Venta de cordiales |
| `FidelizacionController` | `GET /fidelizacion`, `POST entregar` | `auth` | Premios |
| `SucursalController` | `resource /sucursales` | `auth + role:admin` | CRUD sucursales |
| `UsuarioController` | `resource /usuarios` | `auth + role:admin` | CRUD usuarios |
| `CatalogoController` | `GET /catalogo` | **público** | Catálogo sin login |

---

## 6. Patrones de Diseño Identificados

| Patrón | Implementación | Archivo(s) |
|--------|---------------|-------------|
| **MVC** | Separación estricta Model-View-Controller | Todo el framework Laravel |
| **Repository/Service** | Servicios de dominio separados de controladores | `app/Services/*` |
| **Observer** | Eventos automáticos al crear `DetalleVenta` | `DetalleVentaObserver` → `MetricsService` |
| **Strategy** | Estrategias intercambiables de A/B testing | `AbTestingService` (hash, día par/impar, aleatorio) |
| **Chain of Responsibility** | Cascada de proveedores IA: Groq → Gemini → Offline | `IAController@analizar` |
| **Singleton (Service Container)** | Inyección de dependencias vía constructor | Todos los controladores y servicios |
| **Template Method** | Normalización de score con Floor para padecimientos | `PerfilSaludService@reconstruirPerfil` |
| **Cache-Aside** | Cache con versioned keys para invalidación controlada | `RecomendacionEngine` |
| **Batch Processing** | Insert masivo en bloques de 50/200/500 filas | `MetricsService`, `CoocurrenciaService`, `PerfilSaludService` |

---

## 7. Scheduler Nocturno

```mermaid
gantt
    title Schedule Offline del Motor de Recomendación
    dateFormat HH:mm
    axisFormat %H:%M
    
    section Diario
    ReconstruirPerfilesJob        :02:00, 30min
    ReconstruirCoocurrenciaJob    :02:30, 30min
    
    section Semanal (Lunes)
    ActualizarDemandaJob          :03:00, 30min
```

| Job | Frecuencia | Qué hace | Tabla afectada |
|-----|-----------|----------|----------------|
| `ReconstruirPerfilesJob` | Diario 02:00 | Recalcula perfil de afinidad de todos los clientes activos | `cliente_perfil_afinidad` |
| `ReconstruirCoocurrenciaJob` | Diario 02:30 | Recomputa matriz de co-ocurrencia | `producto_coocurrencias` |
| `ActualizarDemandaJob` | Semanal 03:00 (lunes) | Materializa histórico + predicciones SES | `producto_demanda_semana`, `producto_prediccion_demanda` |

---

## 8. Stack Tecnológico Completo

| Capa | Tecnología | Versión |
|------|-----------|---------|
| **Backend** | Laravel (PHP) | 12.x (PHP 8.2+) |
| **Base de datos** | MySQL | 8.0+ |
| **Testing** | PHPUnit + SQLite in-memory | — |
| **Frontend** | Blade + Bootstrap + JavaScript | Bootstrap 5, ES6 |
| **Build** | Vite | 7.3 |
| **Autenticación** | Laravel Breeze | Latest |
| **Roles** | Spatie Laravel Permission | 6.25 |
| **PDF** | Barryvdh DomPDF | 3.1 |
| **Imágenes** | Cloudinary (SDK) | — |
| **IA** | Groq (Llama 3.3 70B) + Google Gemini 1.5 Flash | REST APIs |
| **CI/CD** | GitHub Actions | — |
| **Despliegue** | Railway.app / XAMPP local | — |
| **Análisis estático** | SonarQube / SonarCloud | — |
