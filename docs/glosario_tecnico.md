# Glosario Técnico — NATURACOR

## Definiciones y Acrónimos del Proyecto
**Fecha:** 29/04/2026  
**Versión:** 1.0  

---

## 1. Acrónimos

| Acrónimo | Significado |
|----------|-------------|
| **A/B Testing** | Prueba experimental controlada donde dos variantes (A = control, B = tratamiento) se comparan estadísticamente |
| **AJAX** | Asynchronous JavaScript and XML — Comunicación asíncrona entre navegador y servidor |
| **API** | Application Programming Interface — Interfaz para comunicación entre sistemas |
| **BD** | Base de Datos |
| **CI/CD** | Continuous Integration / Continuous Deployment — Integración y despliegue continuo |
| **CRC32** | Cyclic Redundancy Check de 32 bits — Función hash usada para asignación determinística A/B |
| **CRUD** | Create, Read, Update, Delete — Operaciones básicas de datos |
| **CSRF** | Cross-Site Request Forgery — Ataque de falsificación de petición |
| **CSS** | Cascading Style Sheets — Hojas de estilo |
| **CTR** | Click-Through Rate — Tasa de clics sobre impresiones |
| **DNI** | Documento Nacional de Identidad (Perú) |
| **EAN-13** | European Article Number — Estándar de código de barras de 13 dígitos |
| **FK** | Foreign Key — Clave foránea en base de datos relacional |
| **HID** | Human Interface Device — Protocolo USB de dispositivos de entrada |
| **IGV** | Impuesto General a las Ventas (18% en Perú) — Equivalente al IVA |
| **ISO** | International Organization for Standardization — Organización Internacional de Normalización |
| **JSON** | JavaScript Object Notation — Formato de intercambio de datos |
| **MAE** | Mean Absolute Error — Error absoluto medio |
| **MAPE** | Mean Absolute Percentage Error — Error porcentual absoluto medio |
| **MMR** | Maximal Marginal Relevance — Algoritmo de selección diversa |
| **MVC** | Model-View-Controller — Patrón de arquitectura de software |
| **NPMI** | Normalized Pointwise Mutual Information — Métrica de asociación estadística |
| **ORM** | Object-Relational Mapping — Mapeo objeto-relacional |
| **OWASP** | Open Web Application Security Project — Estándar abierto de seguridad web |
| **PDF** | Portable Document Format — Formato de documento portátil |
| **PHP** | PHP: Hypertext Preprocessor — Lenguaje de programación del backend |
| **PK** | Primary Key — Clave primaria en base de datos |
| **PMI** | Pointwise Mutual Information — Información mutua puntual |
| **POS** | Point of Sale — Punto de Venta |
| **RBAC** | Role-Based Access Control — Control de acceso basado en roles |
| **REST** | Representational State Transfer — Arquitectura de servicios web |
| **RUC** | Registro Único de Contribuyentes (Perú) — Identificación tributaria |
| **SES** | Simple Exponential Smoothing — Suavizado Exponencial Simple |
| **SOC** | Separation of Concerns — Separación de responsabilidades |
| **SQL** | Structured Query Language — Lenguaje de consulta de bases de datos |
| **TLS** | Transport Layer Security — Protocolo de seguridad en capa de transporte |
| **TTL** | Time To Live — Tiempo de vida de un recurso en caché |
| **UI** | User Interface — Interfaz de usuario |
| **UML** | Unified Modeling Language — Lenguaje unificado de modelado |
| **UUID** | Universally Unique Identifier — Identificador único universal |
| **XSS** | Cross-Site Scripting — Ataque de inyección de scripts |

---

## 2. Términos del Dominio de Negocio

| Término | Definición |
|---------|-----------|
| **Acumulado de naturales** | Monto total (S/) que un cliente ha gastado en productos naturales, usado para calcular premios de fidelización |
| **Boleta** | Comprobante de venta con formato `B001-XXXXXX` que incluye desglose de productos, IGV y total |
| **Caja / Sesión de caja** | Período operativo de un empleado donde se registran ingresos y egresos. Se abre al inicio del turno y se cierra al final |
| **Cordial** | Bebida natural preparada, vendida en 9 modalidades (consumo en tienda, para llevar, litros, etc.) |
| **Cliente frecuente** | Cliente que ha realizado compras recurrentes en el sistema |
| **Diferencia de caja** | `monto_real_cierre - total_esperado`. Positiva = sobrante, negativa = faltante |
| **Fidelización** | Programa de recompensas donde el cliente recibe un premio (botella de litro especial) cada S/500 acumulados |
| **IGV incluido** | En NATURACOR, los precios ya incluyen el IGV de 18%. El sistema lo extrae (`precio × 18/118`) para la boleta |
| **Litro Puro S/80** | Tipo de cordial premium. Su compra activa una promoción automática: 1 toma gratis |
| **Padecimiento** | Condición de salud que un cliente declara voluntariamente, usada para mejorar las recomendaciones |
| **Producto natural** | Medicina natural o suplemento herbolario vendido en la tienda |
| **Recetario** | Catálogo que vincula enfermedades con productos naturales recomendados e instrucciones de uso |
| **Sucursal** | Punto de venta físico. Cada empleado está asignado a una sucursal y solo ve datos de la suya |
| **Toma gratis** | Cordial de cortesía otorgado como promoción por la compra de litro puro |
| **Umbral de fidelización** | Monto configurable (default: S/500) que el cliente debe acumular para recibir un premio |

---

## 3. Términos del Motor de Recomendación

| Término | Definición |
|---------|-----------|
| **Boost de carrito** | Multiplicador (default: ×1.5) que se aplica al score de un producto cuando aparece tanto en el perfil de salud como en la co-ocurrencia con el carrito actual |
| **Co-ocurrencia** | Frecuencia con que dos productos aparecen juntos en la misma canasta de compra |
| **Content-Based Filtering** | Método de recomendación basado en el contenido (perfil de salud del cliente y grafo de enfermedades) |
| **Decaimiento temporal** | Factor exponencial `e^(-λ·días)` que reduce la influencia de compras antiguas en el perfil |
| **Embudo de conversión** | Secuencia de eventos: mostrada → clic → agregada → comprada, que mide la efectividad del recomendador |
| **Filtrado colaborativo** | Método de recomendación basado en patrones de compra de otros clientes similares |
| **Grupo control** | En A/B testing, el grupo que NO recibe recomendaciones (baseline) |
| **Grupo tratamiento** | En A/B testing, el grupo que SÍ recibe recomendaciones del motor |
| **Hit Rate** | Fracción de sesiones de recomendación donde al menos 1 producto recomendado fue comprado |
| **Índice Jaccard** | Métrica de similitud: `|A∩B| / |A∪B|` — Mide cuánto se parecen dos conjuntos de compradores |
| **Materialización** | Proceso de calcular y persistir datos agregados (ej. perfiles, co-ocurrencias) para consulta rápida |
| **Motor híbrido** | Sistema que combina múltiples señales (contenido + tendencia + colaborativo) con pesos configurables |
| **Perfil de afinidad** | Tabla materializada que almacena el score de afinidad de cada cliente hacia cada enfermedad del recetario |
| **Precision@K** | Métrica: fracción de las K recomendaciones que el cliente efectivamente compró |
| **Reco_sesion_id** | UUID que identifica una sesión de recomendación (una invocación del motor). Vincula todos los eventos |
| **Score** | Puntuación numérica asignada a un producto candidato, resultado de la fusión ponderada de señales |
| **Señal** | Componente individual del motor: perfil (contenido), trending (popularidad) o co-ocurrencia (colaborativo) |
| **Trending** | Productos con alta demanda en los últimos 14 días en una sucursal específica |
| **Ventana temporal** | Período de tiempo considerado para el cálculo (ej. 90 días para co-ocurrencia, 14 días para trending) |

---

## 4. Términos Estadísticos

| Término | Definición |
|---------|-----------|
| **Alpha (α) SES** | Parámetro de suavización del modelo SES. Valores cercanos a 1 dan más peso a datos recientes |
| **Cohen's d** | Tamaño de efecto estandarizado: `(μ_A - μ_B) / s_pooled`. Valores: ≤0.2 pequeño, 0.5 mediano, ≥0.8 grande |
| **Grados de libertad** | Parámetro de la distribución t de Student, calculado con fórmula Welch-Satterthwaite |
| **IC 95%** | Intervalo de confianza del 95%, rango donde se espera el valor real con 95% de probabilidad |
| **p-valor** | Probabilidad de obtener el resultado observado (o más extremo) si H₀ fuera cierta. Si p < 0.05, se rechaza H₀ |
| **Potencia estadística** | Probabilidad de detectar un efecto si realmente existe (1 - β). Se recomienda ≥ 0.80 |
| **Varianza pooled** | Promedio ponderado de las varianzas de los dos grupos, usado en Cohen's d |
| **Welch's t-test** | Variante del t-test de Student que NO asume igualdad de varianzas entre grupos |

---

## 5. Términos de Infraestructura

| Término | Definición |
|---------|-----------|
| **Artisan** | CLI de Laravel para ejecutar comandos, migraciones, seeders y jobs |
| **Blade** | Motor de templates de Laravel para generar HTML dinámico |
| **Breeze** | Paquete oficial de Laravel para scaffolding de autenticación |
| **Eloquent** | ORM de Laravel que mapea tablas de BD a clases PHP (modelos) |
| **Factory** | Clase que genera datos ficticios para testing (ej. `Cliente::factory()->create()`) |
| **GitHub Actions** | Plataforma de CI/CD que ejecuta tests automáticamente en cada push |
| **Job (Laravel)** | Tarea programada que se ejecuta de forma asíncrona o por el scheduler |
| **Middleware** | Capa intermedia que intercepta requests HTTP para aplicar lógica (auth, roles, CSRF) |
| **Migration** | Archivo PHP que define cambios al esquema de BD de forma versionada |
| **Observer** | Clase que reacciona automáticamente a eventos de un modelo Eloquent (created, updated, etc.) |
| **PHPUnit** | Framework de testing estándar para PHP, integrado nativamente en Laravel |
| **RefreshDatabase** | Trait de testing que reinicia la BD antes de cada test, garantizando aislamiento |
| **Railway.app** | Plataforma PaaS para despliegue de aplicaciones web en la nube |
| **Scheduler** | Componente de Laravel que ejecuta jobs periódicamente (diario, semanal, etc.) |
| **Seeder** | Clase que inserta datos iniciales en la BD (ej. AdminSeeder) |
| **Service Provider** | Clase que registra servicios en el contenedor de inyección de dependencias de Laravel |
| **Soft Delete** | Eliminación lógica: el registro se marca con `deleted_at` pero no se borra físicamente |
| **Spatie Permission** | Paquete de terceros para gestión de roles y permisos en Laravel |
| **SQLite in-memory** | BD temporal que existe solo en RAM, usada en testing para velocidad y aislamiento total |
| **Vite** | Build tool moderno para compilar JavaScript y CSS con hot module replacement |
