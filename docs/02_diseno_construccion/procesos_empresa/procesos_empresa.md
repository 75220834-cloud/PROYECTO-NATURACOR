# Procesos de la Empresa NATURACOR

## Modelado de Procesos de Negocio y Puntos de Integración con el Sistema
**Fecha:** 09/05/2026  
**Versión:** 1.0  
**Notación:** BPMN 2.0 (Business Process Model and Notation)

---

## 1. Introducción

Este documento modela los **procesos de negocio** de NATURACOR como empresa naturista, identificando los flujos operativos principales, los actores involucrados y los **puntos de integración** donde el sistema web automatiza o asiste cada proceso.

### 1.1. Datos de la Empresa

| Dato | Valor |
|------|-------|
| **Razón social** | NATURACOR |
| **Propietaria** | Anita María Cordero Campos |
| **Ubicación** | Jauja, Junín, Perú |
| **Giro** | Venta de productos naturales y cordiales |
| **Sucursales** | Configurables (multi-sucursal) |
| **Empleados** | ~3-5 por sucursal |

---

## 2. Mapa de Procesos de la Empresa

```mermaid
graph TB
    subgraph "Procesos Estratégicos"
        PE1["Gestión de Sucursales"]
        PE2["Análisis de Demanda"]
        PE3["Estrategia de Fidelización"]
    end
    
    subgraph "Procesos Operativos (Core)"
        PO1["Proceso de Venta (POS)"]
        PO2["Gestión de Inventario"]
        PO3["Atención al Cliente"]
        PO4["Venta de Cordiales"]
        PO5["Gestión de Caja"]
    end
    
    subgraph "Procesos de Soporte"
        PS1["Gestión de Usuarios"]
        PS2["Reportería y Boletas"]
        PS3["Recetario / Conocimiento"]
        PS4["Gestión de Reclamos"]
    end
    
    PE1 --> PO1
    PE2 --> PO2
    PE3 --> PO1
    PO1 --> PO5
    PO1 --> PS2
    PO3 --> PO1
    PS3 --> PO1
```

---

## 3. Proceso Principal: Venta en Punto de Venta (POS)

### 3.1. Diagrama BPMN

```mermaid
graph TB
    A["Inicio: Cliente llega a tienda"] --> B["Empleado abre POS"]
    B --> C{"¿Cliente registrado?"}
    C -->|No| D["Registrar cliente<br/>(DNI, nombre)"]
    C -->|Sí| E["Seleccionar cliente<br/>(autocompletado)"]
    D --> E
    
    E --> F["Sistema carga recomendaciones IA"]
    F --> G["Empleado busca productos<br/>(nombre o código de barras)"]
    G --> H["Agregar al carrito<br/>(calcular subtotales)"]
    H --> I{"¿Más productos?"}
    I -->|Sí| G
    I -->|No| J["Seleccionar método de pago"]
    
    J --> K["Confirmar venta"]
    K --> L["Sistema: DB Transaction"]
    L --> M["Actualizar stock"]
    M --> N["Registrar en caja"]
    N --> O["Acumular fidelización"]
    O --> P["Registrar métricas IA"]
    P --> Q["Generar boleta PDF/ticket"]
    Q --> R["Fin: Entregar boleta"]
```

### 3.2. Puntos de Integración con el Sistema

| Paso | Proceso Manual (sin sistema) | Con NATURACOR | Módulo |
|------|------------------------------|---------------|--------|
| Identificar cliente | Preguntar nombre, buscar cuaderno | Autocompletar por DNI/nombre | Clientes |
| Buscar producto | Buscar en estante | Búsqueda AJAX + código de barras | Inventario |
| Calcular total | Calculadora manual | Cálculo automático con IGV | POS |
| Sugerir productos | Experiencia del vendedor | **Motor IA con 3 señales** | Recomendador |
| Control de stock | Conteo manual | `lockForUpdate()` + rollback | Inventario |
| Fidelización | Tarjeta física de sellos | Acumulación automática | Fidelización |
| Boleta | Cuaderno de ventas | PDF A4 + ticket térmico | Reportes |

---

## 4. Proceso: Gestión de Inventario

### 4.1. Diagrama de Flujo

```mermaid
graph TB
    A["Inicio"] --> B{"¿Acción?"}
    B -->|"Alta"| C["Registrar producto<br/>(nombre, precio, stock, barcode)"]
    B -->|"Consulta"| D["Buscar por nombre<br/>o escanear barcode"]
    B -->|"Actualizar"| E["Modificar precio/stock"]
    B -->|"Carga masiva"| F["Importar Excel"]
    B -->|"Exportar"| G["Descargar Excel"]
    
    C --> H["Subir imagen<br/>(local o Cloudinary)"]
    F --> I["Validar filas<br/>(match case-insensitive)"]
    I --> J["Actualizar solo campos llenos"]
    
    H --> K["Verificar stock mínimo"]
    E --> K
    J --> K
    K --> L{"Stock < mínimo?"}
    L -->|Sí| M["⚠️ Alerta visual"]
    L -->|No| N["✅ OK"]
```

### 4.2. Automatización por el Sistema

| Actividad | Automatización | Beneficio |
|-----------|---------------|-----------|
| Control de stock mínimo | Alertas automáticas | Previene desabasto |
| Predicción de demanda | SES semanal (job nocturno) | Widget "Productos en Riesgo" |
| Carga masiva | Excel con validación | Ahorra horas de carga manual |
| Imágenes | Cloudinary con fallback local | CDN en producción |

---

## 5. Proceso: Gestión de Caja

```mermaid
graph TB
    A["Inicio del turno"] --> B["Empleado abre caja<br/>(monto inicial)"]
    B --> C["Operación normal<br/>(ventas se registran)"]
    C --> D["Movimientos de caja<br/>(entradas/salidas)"]
    D --> E["Cierre de caja"]
    E --> F["Sistema calcula totales<br/>por método de pago"]
    F --> G["Comparar: esperado vs real"]
    G --> H{"¿Diferencia?"}
    H -->|Sí| I["Registrar diferencia<br/>(sobrante/faltante)"]
    H -->|No| J["Cierre exacto"]
    I --> K["Fin del turno"]
    J --> K
```

**Regla de negocio:** Solo se permite **una caja abierta** por sucursal a la vez. El sistema impide abrir una segunda.

---

## 6. Proceso: Fidelización de Clientes

```mermaid
graph TB
    A["Venta completada"] --> B["Acumular monto<br/>de productos naturales"]
    B --> C{"Acumulado ≥ S/500?"}
    C -->|No| D["Seguir acumulando"]
    C -->|Sí| E["Generar canje automático"]
    E --> F["Registrar premio pendiente"]
    F --> G["Notificar al empleado"]
    G --> H["Entregar premio<br/>(ej: Botella Nopal 2L)"]
    H --> I["Reiniciar acumulador"]
```

| Parámetro | Valor | Configurable |
|-----------|-------|:---:|
| Umbral de canje | S/ 500 | ✅ `FIDELIZACION_MONTO` |
| Premio máximo | S/ 30 | ✅ `FIDELIZACION_MAXIMO_PREMIO` |
| Período | Anual (2026) | ✅ `FIDELIZACION_INICIO/FIN` |
| Productos válidos | Solo productos naturales (no cordiales) | En código |

---

## 7. Proceso: Recomendación Inteligente

```mermaid
sequenceDiagram
    participant E as Empleado (POS)
    participant S as Sistema
    participant M as Motor IA
    participant BD as Base de Datos
    
    E->>S: Selecciona cliente en POS
    S->>M: GET /api/recomendaciones/{cliente}
    M->>BD: Consultar perfil de afinidad
    M->>BD: Consultar co-ocurrencia
    M->>BD: Consultar trending por sucursal
    M->>M: Fusión lineal ponderada
    M->>BD: Registrar eventos "mostrada"
    M-->>S: Top-K recomendaciones
    S-->>E: Mostrar badges (🩺/📈/🛒)
    
    E->>S: Clic en recomendación
    S->>BD: Registrar evento "clic"
    
    E->>S: Agregar al carrito
    S->>BD: Registrar evento "agregada"
    
    E->>S: Confirmar venta
    S->>BD: DetalleVentaObserver
    BD->>BD: Registrar evento "comprada" (atribución)
```

---

## 8. Proceso: Gestión de Reclamos

```mermaid
graph LR
    A["Cliente presenta reclamo"] --> B["Empleado registra<br/>(descripción, tipo)"]
    B --> C["Estado: PENDIENTE"]
    C --> D["Admin revisa"]
    D --> E["Estado: EN PROCESO"]
    E --> F["Resolución"]
    F --> G["Estado: RESUELTO"]
    G --> H["Notificar al cliente"]
```

---

## 9. Proceso: Recetario y Conocimiento

| Actividad | Manual | Con Sistema |
|-----------|--------|-------------|
| Consultar qué producto sirve para diabetes | Preguntar a la dueña | Buscar en recetario digital |
| Agregar nueva enfermedad | Cuaderno | CRUD con relación a productos |
| Asociar productos a enfermedad | Memoria | `syncWithoutDetaching` |
| Carga masiva de recetario | No viable | Excel con separadores `\|` o `;` |

---

## 10. Resumen de Automatización por Proceso

| Proceso | Sin Sistema | Con NATURACOR | Ahorro |
|---------|:-----------:|:-------------:|--------|
| Venta POS | 5-10 min | 1-3 min | ~60% tiempo |
| Inventario | Manual, propenso a error | Automático + alertas | ~80% errores |
| Caja | Cuaderno + calculadora | Cierre automático | ~90% tiempo |
| Fidelización | Tarjeta física | Automática | 100% manual eliminado |
| Recomendación | Experiencia del vendedor | IA con 3 señales | Valor agregado nuevo |
| Pronóstico | No existe | SES semanal | Capacidad nueva |
| Reportes | Cuaderno | PDF + filtros | ~95% tiempo |

---

**Fin del documento.**
