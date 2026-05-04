# Documento de Requerimientos de Software

## NATURACOR — Sistema Web Empresarial
**Fecha:** 15/04/2026

---

## Tabla de contenido

- Historial de Versiones
- Información del Proyecto
- Aprobaciones
1. Propósito
2. Alcance del producto / Software
3. Referencias
4. Funcionalidades del producto
5. Clases y características de usuarios
6. Entorno operativo
7. Requerimientos funcionales
8. Reglas de negocio
9. Requerimientos de interfaces externas
10. Requerimientos no funcionales
11. Otros requerimientos
12. Glosario

---

## Historial de Versiones

| Fecha | Versión | Autor | Organización | Descripción |
|---|---|---|---|---|
| 25/03/2026 | 1.0 | Bendezu Lagos Jack Joshua | NATURACOR | Creación inicial del documento de requerimientos con los 10 módulos funcionales |
| 02/04/2026 | 1.1 | Reyes Cordero Italo Eduardo | NATURACOR | Incorporación de requerimientos del módulo de fidelización y cordiales (reglas 2026) |
| 05/04/2026 | 1.2 | Julca Laureano Dickmar Wilber | NATURACOR | Actualización de requerimientos no funcionales y reglas de negocio |
| 15/04/2026 | 2.0 | Bendezu Lagos Jack Joshua | NATURACOR | Versión final revisada con 10 módulos, 3 módulos administrativos y requerimientos consolidados |

---

## Información del Proyecto

| Campo | Detalle |
|---|---|
| **Empresa / Organización** | NATURACOR — Tiendas de Productos Naturales |
| **Proyecto** | Sistema Web de Punto de Venta y Gestión Integral para Tiendas Naturistas |
| **Fecha de preparación** | 15/04/2026 |
| **Cliente** | Anita María Cordero Campos |
| **Patrocinador principal** | Anita María Cordero Campos — Propietaria de NATURACOR |
| **Gerente / Líder de Proyecto** | Bendezu Lagos Jack Joshua |
| **Gerente / Líder de Análisis de negocio y requerimientos** | Julca Laureano Dickmar Wilber |

---

## Aprobaciones

| Nombre y Apellido | Cargo | Departamento u Organización | Fecha | Firma |
|---|---|---|---|---|
| Anita María Cordero Campos | Dueña del negocio | Junín, Jauja, Jauja | 08/04/2026 | _________________ |
| Maglioni Arana Caparachin | Docente del curso | Universidad — Pruebas y Calidad de Software | 15/04/2026 | _________________ |
| Bendezu Lagos Jack Joshua | Líder de Proyecto | Equipo de Desarrollo NATURACOR | 15/04/2026 | _________________ |
| Julca Laureano Dickmar Wilber | Líder de Análisis de Requerimientos | Equipo de QA NATURACOR | 15/04/2026 | _________________ |
| Reyes Cordero Italo Eduardo | Desarrollador / Analista | Equipo de Desarrollo NATURACOR | 15/04/2026 | _________________ |

---

## 1. Propósito

El presente documento especifica los requerimientos de software del sistema **NATURACOR versión 2.0**, un sistema web de punto de venta (POS) y gestión integral diseñado para una cadena de tiendas de productos naturales ubicada en la ciudad de Jauja, departamento de Junín, Perú.

Este documento cubre la **totalidad del sistema**, incluyendo los 10 módulos funcionales principales (POS, Inventario, Clientes, Caja, Fidelización, Cordiales, Asistente IA, Recetario, Reclamos y Reportes/Boletas), así como los 3 módulos de administración (Sucursales, Usuarios/Roles y Dashboard). Se incluyen todos los requerimientos funcionales, no funcionales, reglas de negocio, interfaces externas y restricciones del entorno operativo necesarios para el desarrollo, pruebas y despliegue del sistema.

---

## 2. Alcance del producto / Software

**Propósito u objetivo general:**
NATURACOR tiene como objetivo principal digitalizar y automatizar las operaciones diarias de una cadena de tiendas de productos naturales, abarcando desde el registro de ventas con cálculo automático de IGV hasta la gestión de un programa de fidelización con premios automáticos, pasando por un asistente de inteligencia artificial para análisis de negocio y recomendación de productos basada en el perfil de salud del cliente.

**Beneficios que brinda al área de negocio y organización:**

- **Eficiencia operativa:** Reducción del tiempo de atención en el punto de venta mediante búsqueda AJAX de productos y clientes, escaneo de código de barras y generación automática de boletas.
- **Control financiero:** Gestión integral de sesiones de caja con apertura, cierre, registro de movimientos (ingresos/egresos) y cálculo automático de diferencias.
- **Fidelización de clientes:** Programa automatizado de premios que incentiva la compra recurrente de productos naturales, incrementando la retención de clientes.
- **Inteligencia de negocio:** Asistente de IA que brinda análisis y recomendaciones contextuales basadas en los datos del negocio.
- **Trazabilidad:** Registro completo de ventas, reclamos y auditoría que permite el seguimiento histórico de todas las operaciones.
- **Gestión multi-sucursal:** Soporte para múltiples sucursales con aislamiento de datos y control de acceso por empleado.

**Objetivos y metas:**

- Automatizar el 100% de las operaciones de venta y facturación de la tienda.
- Implementar un sistema de fidelización alineado con las reglas de negocio 2026 de la propietaria.
- Alcanzar una cobertura de pruebas automatizadas superior al 85% del código funcional.
- Garantizar la estabilidad del sistema mediante un pipeline CI/CD con **350** pruebas automatizadas (113 Unit + 237 Feature, estado 03/05/2026).
- Proveer un sistema seguro con control de acceso basado en roles (administrador y empleado).

**Relación con los objetivos corporativos:** Este sistema se alinea directamente con la estrategia de crecimiento de NATURACOR, que busca expandir su presencia en la región Junín mediante la apertura de nuevas sucursales. La plataforma multi-sucursal permite escalar el negocio sin perder control sobre las operaciones de cada punto de venta.

---

## 3. Referencias

| # | Título | Autor | Versión | Fecha | Ubicación |
|---|---|---|---|---|---|
| 1 | Plan de Proyecto NATURACOR (PMO.doc) | Bendezu Lagos Jack Joshua | 2.0 | 05/04/2026 | Repositorio del proyecto — carpeta raíz |
| 2 | Plan de Pruebas de Software NATURACOR | Julca Laureano Dickmar Wilber | 2.1 | 03/05/2026 | `../03_pruebas_calidad/Plan_de_Pruebas_NATURACOR.md` |
| 3 | Formato 06: Requerimientos Funcionales | Bendezu Lagos Jack Joshua | 1.0 | 05/04/2026 | Documentación académica del proyecto |
| 4 | Formato 04: Modelo BPM Actual (Proceso AS-IS) | Reyes Cordero Italo Eduardo | 1.0 | 03/04/2026 | Documentación académica del proyecto |
| 5 | Formato 05: Modelo BPM Mejorado (Proceso TO-BE) | Reyes Cordero Italo Eduardo | 1.0 | 03/04/2026 | Documentación académica del proyecto |
| 6 | README.md del proyecto | Bendezu Lagos Jack Joshua | 2.0 | 15/04/2026 | `README.md` en la raíz del repositorio GitHub |
| 7 | Documentación oficial de Laravel 12 | Laravel LLC | 12.x | 2026 | https://laravel.com/docs/12.x |
| 8 | Documentación de Spatie Laravel Permission | Spatie | 6.25 | 2026 | https://spatie.be/docs/laravel-permission/v6 |
| 9 | IEEE 830 — Recommended Practice for SRS | IEEE | 1998 | 1998 | Estándar internacional de referencia |

---

## 4. Funcionalidades del producto

Las funcionalidades del sistema NATURACOR se organizan en 10 módulos funcionales principales y 3 módulos de administración:

**Módulos funcionales principales:**

1. **POS — Punto de Venta:** Registro de ventas con IGV incluido, generación de boletas con numeración correlativa, descuentos y múltiples métodos de pago.
2. **Inventario:** Gestión completa de productos con control de stock mínimo, alertas de reposición, búsqueda AJAX y escaneo de código de barras.
3. **Clientes:** Registro de clientes por DNI, historial de compras, búsqueda AJAX por DNI y eliminación lógica.
4. **Caja:** Apertura y cierre de sesiones de caja, registro de movimientos (ingresos/egresos), totales por método de pago y cálculo de diferencia al cierre.
5. **Fidelización:** Programa de premios automáticos basado en acumulado de compras de productos naturales (reglas 2026).
6. **Cordiales:** Venta de 9 tipos de bebidas naturales con precios fijos, promociones de litro puro y cortesías para invitados.
7. **Asistente de Inteligencia Artificial:** Análisis de negocio con IA mediante cascada de proveedores (Groq → Gemini → modo offline).
8. **Recetario:** Consulta de enfermedades vinculadas a productos naturales recomendados con instrucciones de uso.
9. **Reclamos:** Registro y seguimiento de reclamos con flujo de estados y escalado al administrador.
10. **Reportes y Boletas:** Reportes de ventas filtrados por múltiples criterios y generación de boletas en PDF optimizadas para impresión térmica.

**Módulos de administración (solo admin):**

11. **Sucursales:** Gestión de puntos de venta (CRUD completo).
12. **Usuarios y Roles:** Gestión de usuarios del sistema con asignación de roles.
13. **Dashboard:** Panel resumen con KPIs del día, semana y mes.

---

## 5. Clases y características de usuarios

| Tipo de usuario | Descripción | Funcionalidades relevantes | Frecuencia de uso | Nivel de experiencia |
|---|---|---|---|---|
| **Administrador (admin)** | Propietaria del negocio o gerente general. Tiene acceso completo a todas las funcionalidades del sistema, incluyendo la gestión de sucursales, usuarios y configuración global. | Todas las funcionalidades (1–13). Utiliza principalmente: Dashboard, Reportes, Gestión de sucursales, Gestión de usuarios, Fidelización y Reclamos. | Diario — Acceso frecuente para monitoreo de KPIs y gestión administrativa. | Medio — Capacitada en el uso del sistema mediante sesión presencial de formación. |
| **Empleado (empleado)** | Personal operativo de la tienda que atiende a los clientes en el punto de venta. Tiene acceso limitado a las funcionalidades de su sucursal asignada. | POS, Inventario (consulta), Clientes, Caja, Cordiales, Recetario, Reclamos (crear). No puede acceder a Sucursales, Usuarios ni Dashboard administrativo. | Diario — Uso intensivo durante el horario de atención al público. Es el usuario que más interactúa con el sistema. | Básico — Requiere una interfaz intuitiva y sencilla. Capacitación mínima de 2 horas. |
| **Cliente final** | Persona que realiza compras en la tienda. No accede directamente al sistema, pero es el beneficiario del programa de fidelización y del recetario. | Fidelización (beneficiario de premios), Reclamos (puede solicitar el registro de un reclamo a través del empleado). | Ocasional — Interactúa indirectamente a través del empleado en cada compra. | No aplica — No interactúa directamente con el sistema. |

---

## 6. Entorno operativo

El sistema NATURACOR opera en el siguiente entorno técnico:

**Plataforma de servidor:**
- **Sistema operativo:** Windows 10/11 (entorno local con XAMPP) o Ubuntu Latest (servidor CI/CD en GitHub Actions).
- **Servidor web:** Apache 2.4 (incluido en XAMPP) configurado para servir aplicaciones Laravel.
- **Runtime:** PHP 8.2 o superior con las extensiones: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`.
- **Base de datos:** MySQL 8.0 o superior (producción y desarrollo) / SQLite en memoria (entorno de testing).

**Plataforma de cliente:**
- **Navegadores soportados:** Google Chrome 120+, Mozilla Firefox 120+, Microsoft Edge 120+.
- **Dispositivos:** Computadoras de escritorio y laptops. El diseño responsivo con Bootstrap 5 permite visualización en tablets, aunque la operación está optimizada para pantallas de escritorio.
- **Resolución mínima recomendada:** 1366 × 768 píxeles.
- **Conectividad:** Acceso a red local (LAN) donde se aloje el servidor. Conexión a Internet requerida únicamente para el módulo de Asistente IA y el pipeline CI/CD.

**Componentes con los que coexiste:**
- **XAMPP 8.2+:** Stack que incluye Apache, MySQL y PHP en un solo instalador para Windows.
- **Composer 2.x:** Gestor de dependencias PHP.
- **Node.js 18+:** Requerido para la compilación de assets frontend con Vite y Tailwind CSS.
- **Git:** Control de versiones y sincronización con el repositorio remoto en GitHub.
- **Impresora térmica 80mm (opcional):** Dispositivo compatible con ESC/POS para la impresión directa de boletas y tickets.

---

## 7. Requerimientos funcionales

### 7.1. POS — Punto de Venta

**Descripción:** Módulo principal del sistema que permite al empleado registrar ventas de productos naturales a los clientes, con cálculo automático de IGV incluido, generación de boletas correlativas, aplicación de descuentos y selección de método de pago.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. El empleado accede al módulo POS desde el menú principal.
2. El sistema muestra la interfaz de punto de venta con campos de búsqueda de producto y cliente.
3. El empleado busca un producto por nombre o código de barras mediante búsqueda AJAX.
4. El sistema muestra los productos coincidentes con su precio (IGV incluido) y stock disponible.
5. El empleado selecciona uno o más productos, ingresa las cantidades y opcionalmente aplica un descuento.
6. El sistema calcula el total de la venta en tiempo real.
7. El empleado selecciona el método de pago (efectivo, Yape o Plin) y confirma la venta.
8. El sistema registra la venta, descuenta el stock, genera la boleta con número correlativo B001-XXXXXX, actualiza el acumulado de fidelización del cliente y registra el movimiento en la caja abierta.

**Requerimientos funcionales:**

**REQ-POS-001:** El sistema debe permitir buscar productos en tiempo real mediante búsqueda AJAX por nombre o fragmento de nombre, mostrando los resultados coincidentes con su precio unitario (IGV incluido) y stock actual.

**REQ-POS-002:** El sistema debe permitir buscar productos mediante escaneo de código de barras, identificando automáticamente el producto y agregándolo a la lista de venta.

**REQ-POS-003:** El sistema debe permitir agregar múltiples productos a una misma venta, especificando la cantidad de cada uno, y calcular el subtotal y total en tiempo real.

**REQ-POS-004:** El sistema debe calcular el IGV (18%) incluido en el precio de cada producto, mostrando el desglose de base imponible e IGV en la boleta.

**REQ-POS-005:** El sistema debe permitir aplicar un porcentaje de descuento sobre el total de la venta antes de confirmar.

**REQ-POS-006:** El sistema debe soportar tres métodos de pago: efectivo, Yape y Plin.

**REQ-POS-007:** Al confirmar la venta, el sistema debe generar automáticamente un número de boleta correlativo con formato B001-XXXXXX, donde XXXXXX es un número secuencial de 6 dígitos.

**REQ-POS-008:** El sistema debe descontar automáticamente el stock de cada producto vendido al confirmar la venta.

**REQ-POS-009:** El sistema debe actualizar automáticamente el acumulado de compras del cliente registrado para el programa de fidelización.

**REQ-POS-010:** El sistema debe registrar la venta como un movimiento de ingreso en la sesión de caja activa del empleado.

**REQ-POS-011:** El sistema debe validar que exista una sesión de caja abierta antes de permitir registrar una venta. Si no hay caja abierta, debe mostrar un mensaje de error descriptivo.

**REQ-POS-012:** El sistema debe utilizar transacciones de base de datos (`DB::beginTransaction()`) para garantizar la consistencia de los datos en cada venta.

---

### 7.2. Inventario

**Descripción:** Módulo que permite la gestión completa del catálogo de productos naturales de la tienda, incluyendo alta, edición, consulta, eliminación lógica, control de stock mínimo y alertas de reposición.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. El administrador o empleado accede al listado de productos.
2. El sistema muestra los productos activos con su nombre, precio, stock actual y estado.
3. El usuario puede agregar un nuevo producto, editar uno existente, o dar de baja un producto (soft delete).
4. Si el stock de un producto alcanza o desciende por debajo del umbral mínimo configurado, el sistema muestra una alerta visual.

**Requerimientos funcionales:**

**REQ-INV-001:** El sistema debe permitir crear un nuevo producto con los siguientes campos obligatorios: nombre, descripción, precio de venta (con IGV incluido), stock actual, stock mínimo y código de barras (opcional).

**REQ-INV-002:** El sistema debe permitir editar todos los campos de un producto existente.

**REQ-INV-003:** El sistema debe implementar eliminación lógica (soft delete) de productos, ocultándolos de las vistas activas pero preservándolos en la base de datos.

**REQ-INV-004:** El sistema debe mostrar una alerta visual cuando el stock de un producto sea igual o inferior al stock mínimo configurado (por defecto: 5 unidades).

**REQ-INV-005:** El sistema debe proporcionar un endpoint de búsqueda AJAX que permita buscar productos por nombre desde otros módulos (POS).

**REQ-INV-006:** El sistema debe proporcionar un endpoint de búsqueda por código de barras que retorne el producto coincidente.

---

### 7.3. Clientes

**Descripción:** Módulo de registro y gestión de la base de clientes de la tienda, con búsqueda por DNI y seguimiento del historial de compras.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. El empleado accede al módulo de clientes o busca un cliente desde el POS.
2. El sistema permite registrar un nuevo cliente o buscar uno existente por DNI.
3. Al seleccionar un cliente, se muestra su historial de compras y el acumulado para el programa de fidelización.

**Requerimientos funcionales:**

**REQ-CLI-001:** El sistema debe permitir registrar un nuevo cliente con los campos: DNI (único, 8 dígitos), nombres, apellidos, teléfono (opcional) y email (opcional).

**REQ-CLI-002:** El sistema debe validar que el DNI sea único en el sistema; no se deben permitir registros duplicados.

**REQ-CLI-003:** El sistema debe proporcionar un endpoint de búsqueda AJAX por DNI que retorne los datos del cliente.

**REQ-CLI-004:** El sistema debe permitir consultar el historial de compras de cada cliente.

**REQ-CLI-005:** El sistema debe implementar eliminación lógica (soft delete) de clientes.

**REQ-CLI-006:** El sistema debe mantener un campo de acumulado de compras de productos naturales para el cálculo de fidelización.

---

### 7.4. Caja

**Descripción:** Módulo de control financiero diario que gestiona las sesiones de caja de cada empleado, registrando aperturas, cierres, ingresos y egresos.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. El empleado abre una sesión de caja al inicio de su turno, ingresando el monto inicial.
2. Durante el día, el sistema registra automáticamente los ingresos por ventas y permite registrar egresos manuales.
3. Al finalizar el turno, el empleado cierra la caja, ingresa el conteo real y el sistema calcula la diferencia.

**Requerimientos funcionales:**

**REQ-CAJA-001:** El sistema debe permitir abrir una sesión de caja ingresando un monto inicial en efectivo.

**REQ-CAJA-002:** El sistema debe permitir registrar movimientos manuales de tipo ingreso o egreso con un monto y una descripción.

**REQ-CAJA-003:** El sistema debe calcular automáticamente los totales de venta por cada método de pago (efectivo, Yape, Plin) durante la sesión activa.

**REQ-CAJA-004:** El sistema debe permitir cerrar la sesión de caja, solicitando el conteo real del efectivo y calculando la diferencia con el total esperado.

**REQ-CAJA-005:** El sistema debe permitir visualizar el detalle de una sesión de caja cerrada con todos sus movimientos.

**REQ-CAJA-006:** El sistema debe impedir que un empleado tenga más de una sesión de caja abierta simultáneamente.

---

### 7.5. Fidelización

**Descripción:** Programa de premios automáticos que recompensa la fidelidad de los clientes que acumulan compras de productos naturales.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. Cuando un cliente acumula compras de productos naturales por S/500 o más durante el año 2026, el sistema genera automáticamente un registro de premio (Botella 2L Nopal gratis).
2. El empleado consulta la lista de premios pendientes de entrega.
3. Al entregar el premio físicamente, el empleado marca la entrega en el sistema.

**Requerimientos funcionales:**

**REQ-FID-001:** El sistema debe generar automáticamente un premio cuando el acumulado de compras de productos naturales de un cliente alcance S/500 o más dentro del período de vigencia (01/01/2026 – 31/12/2026).

**REQ-FID-002:** El premio consiste en una Botella de 2 Litros de Nopal gratis, con un valor máximo de S/30.

**REQ-FID-003:** El sistema debe mostrar una lista de premios pendientes de entrega con los datos del cliente beneficiario.

**REQ-FID-004:** El empleado debe poder marcar un premio como entregado, registrando la fecha y el usuario que realizó la entrega.

**REQ-FID-005:** El sistema debe permitir reiniciar los acumulados de fidelización de todos los clientes al finalizar el año, mediante un comando artisan (`fidelizacion:reiniciar`).

**REQ-FID-006:** Los montos umbrales del programa de fidelización deben ser configurables mediante variables de entorno.

---

### 7.6. Cordiales

**Descripción:** Módulo de venta de bebidas naturales preparadas en la tienda, con sistema de precios fijos, promociones y cortesías.

**Prioridad:** Media

**Acciones iniciadoras y comportamiento esperado:**
1. El empleado accede al módulo de cordiales y selecciona el tipo de bebida, la presentación y el cliente.
2. El sistema muestra el precio correspondiente y permite registrar la venta.
3. Si el cliente compra un litro puro a S/80, el sistema le otorga automáticamente 1 toma gratis.

**Requerimientos funcionales:**

**REQ-COR-001:** El sistema debe soportar 9 tipos de cordiales con precios fijos predeterminados.

**REQ-COR-002:** El sistema debe permitir registrar una venta de cordial asociada a un cliente registrado o a un invitado.

**REQ-COR-003:** El sistema debe aplicar automáticamente la promoción: al comprar un litro puro a S/80, el cliente obtiene 1 toma gratis.

**REQ-COR-004:** El sistema debe permitir registrar cortesías (bebidas gratis) para clientes invitados.

**REQ-COR-005:** El sistema debe mostrar un catálogo de precios de cordiales accesible desde el menú principal.

---

### 7.7. Asistente de Inteligencia Artificial

**Descripción:** Módulo de consultoría inteligente que utiliza modelos de IA para analizar datos del negocio y generar recomendaciones contextuales.

**Prioridad:** Media

**Acciones iniciadoras y comportamiento esperado:**
1. El usuario accede al módulo de IA y escribe una consulta de análisis de negocio.
2. El sistema envía la consulta al proveedor de IA primario (Groq con Llama 3.3 70B).
3. Si Groq falla, el sistema intenta con el proveedor secundario (Google Gemini 1.5 Flash).
4. Si ambos proveedores fallan, el sistema opera en modo offline con recomendaciones locales.
5. El sistema muestra la respuesta generada por la IA al usuario.

**Requerimientos funcionales:**

**REQ-IA-001:** El sistema debe permitir al usuario ingresar consultas en lenguaje natural sobre análisis de negocio.

**REQ-IA-002:** El sistema debe implementar una cascada de proveedores de IA: Groq (primario) → Gemini (secundario) → modo offline (fallback).

**REQ-IA-003:** En modo offline, el sistema debe generar recomendaciones contextuales basadas en datos locales del negocio sin depender de APIs externas.

**REQ-IA-004:** Las API keys de los proveedores de IA deben ser configurables mediante variables de entorno y leídas a través del archivo de configuración (`config/naturacor.php`), no directamente con `env()`.

**REQ-IA-005:** El sistema debe funcionar completamente sin API keys de IA configuradas, operando exclusivamente en modo offline.

---

### 7.8. Recetario

**Descripción:** Base de conocimiento que vincula enfermedades comunes con productos naturales recomendados, incluyendo instrucciones de uso.

**Prioridad:** Media

**Acciones iniciadoras y comportamiento esperado:**
1. El usuario busca una enfermedad o condición de salud.
2. El sistema muestra los productos naturales recomendados para esa condición, con instrucciones de uso.
3. El administrador puede crear, editar y eliminar enfermedades, así como vincularlas con productos.

**Requerimientos funcionales:**

**REQ-REC-001:** El sistema debe permitir crear una enfermedad con nombre y descripción.

**REQ-REC-002:** El sistema debe permitir vincular una enfermedad con múltiples productos naturales (relación muchos-a-muchos) e incluir instrucciones de uso para cada vínculo.

**REQ-REC-003:** El sistema debe permitir buscar enfermedades por nombre o palabra clave.

**REQ-REC-004:** El sistema debe mostrar, para cada enfermedad, la lista de productos recomendados con sus instrucciones de uso.

**REQ-REC-005:** El sistema debe permitir editar y eliminar enfermedades y sus vínculos con productos.

---

### 7.9. Reclamos

**Descripción:** Módulo de gestión de reclamos de clientes con flujo de estados, escalado al administrador y registro de auditoría.

**Prioridad:** Media

**Acciones iniciadoras y comportamiento esperado:**
1. El empleado registra un reclamo del cliente con la descripción del problema.
2. El reclamo se crea con estado "pendiente".
3. El empleado o administrador puede escalar el reclamo, cambiando su estado a "en_proceso".
4. El administrador resuelve el reclamo, cambiando su estado a "resuelto" y registrando la resolución.

**Requerimientos funcionales:**

**REQ-RCL-001:** El sistema debe permitir registrar un reclamo con los campos: cliente, sucursal, descripción del problema, tipo de reclamo y estado inicial "pendiente".

**REQ-RCL-002:** El sistema debe implementar un flujo de estados: pendiente → en_proceso → resuelto.

**REQ-RCL-003:** El sistema debe permitir escalar un reclamo (cambiar de pendiente a en_proceso).

**REQ-RCL-004:** El sistema debe permitir resolver un reclamo, registrando la descripción de la resolución y la fecha de cierre.

**REQ-RCL-005:** El sistema debe registrar un log de auditoría para cada cambio de estado del reclamo.

**REQ-RCL-006:** El sistema debe permitir visualizar todos los reclamos con filtros por estado y sucursal.

---

### 7.10. Reportes y Boletas

**Descripción:** Módulo de generación de reportes de ventas con filtros avanzados y emisión de boletas en formato PDF e impresión térmica.

**Prioridad:** Alta

**Acciones iniciadoras y comportamiento esperado:**
1. El administrador accede a reportes, selecciona filtros (fecha, sucursal, empleado, método de pago) y genera el informe.
2. El sistema muestra la tabla de resultados con totales.
3. El usuario puede descargar la boleta de cualquier venta en formato PDF (80mm) o visualizarla en pantalla.

**Requerimientos funcionales:**

**REQ-RPT-001:** El sistema debe permitir generar reportes de ventas filtrados por rango de fechas, sucursal, empleado y método de pago.

**REQ-RPT-002:** El sistema debe generar boletas en formato PDF optimizado para impresión en papel de 80mm de ancho.

**REQ-RPT-003:** El sistema debe generar un formato de ticket térmico para impresión directa desde el POS.

**REQ-RPT-004:** El sistema debe permitir compartir la boleta mediante enlace de WhatsApp (funcionalidad futura).

**REQ-RPT-005:** Cada boleta debe contener: número correlativo, fecha, datos del cliente, detalle de productos, subtotal, IGV, total y método de pago.

---

## 8. Reglas de negocio

Las siguientes reglas y principios aplican a todo el conjunto de requerimientos del sistema NATURACOR:

**RN-001:** Todos los precios de productos en el sistema incluyen el IGV (18%). El IGV se calcula como: IGV = Precio × 18 / 118. La base imponible se calcula como: Base = Precio − IGV.

**RN-002:** El programa de fidelización 2026 establece que cuando un cliente acumula compras de productos naturales por un monto igual o superior a S/500 dentro del período enero–diciembre 2026, se le otorga automáticamente una Botella de 2 Litros de Nopal gratis (valor máximo S/30).

**RN-003:** La promoción de cordiales establece que al comprar un litro puro de cordial a S/80, el cliente obtiene 1 toma gratis adicional.

**RN-004:** Los acumulados de compras para fidelización se reinician anualmente al inicio de cada período configurado.

**RN-005:** Solo los usuarios con rol "admin" pueden acceder a las funcionalidades de gestión de sucursales, gestión de usuarios y dashboard administrativo.

**RN-006:** Los empleados solo pueden visualizar y operar sobre los datos de la sucursal a la que están asignados (aislamiento de datos por sucursal).

**RN-007:** Toda operación de venta debe ejecutarse dentro de una transacción de base de datos para garantizar la consistencia de los datos.

**RN-008:** Los registros de ventas, productos y clientes no se eliminan físicamente de la base de datos; se utiliza eliminación lógica (soft delete) para preservar la integridad histórica.

**RN-009:** No se puede registrar una venta si el empleado no tiene una sesión de caja abierta.

**RN-010:** El número de boleta es correlativo con formato B001-XXXXXX y se genera automáticamente por el sistema de forma secuencial.

**RN-011:** El stock mínimo por defecto de cada producto es de 5 unidades, configurable a nivel global mediante variable de entorno.

---

## 9. Requerimientos de interfaces externas

### 9.1. Interfaces de usuario

El sistema NATURACOR presenta una interfaz web responsiva desarrollada con **Blade Templates** de Laravel y **Bootstrap 5** como framework CSS, complementado con compilación de assets mediante **Vite + Tailwind CSS**.

**Estándares de interfaz gráfica (GUI):**

- **Diseño responsivo:** La interfaz se adapta a pantallas de escritorio (≥ 1366px), tablets y dispositivos móviles.
- **Navegación principal:** Barra lateral (sidebar) con menú de módulos organizado por categorías. Cada módulo se identifica con un ícono y un nombre descriptivo.
- **Esquema de colores:** Paleta basada en tonalidades verdes (asociadas a lo natural y ecológico) con fondos claros y textos oscuros para máxima legibilidad.
- **Tipografía:** Fuente sans-serif del sistema con tamaños jerárquicos para títulos, subtítulos y texto de cuerpo.
- **Tablas de datos:** Listados tabulares con paginación, ordenamiento y filtros. Las filas alternadas facilitan la lectura.
- **Formularios:** Campos con etiquetas claras, validación en tiempo real, mensajes de error descriptivos en rojo bajo cada campo inválido y botones de acción diferenciados por color.
- **Alertas y notificaciones:** Mensajes flash (éxito en verde, error en rojo, advertencia en amarillo) que aparecen en la parte superior de la página tras cada acción.
- **Botones estándar:** Botón primario (verde) para acciones principales (guardar, confirmar). Botón secundario (gris) para cancelar. Botón de peligro (rojo) para eliminar.
- **Búsqueda AJAX:** Campos con autocompletado que muestran resultados en un desplegable sin recargar la página.

### 9.2. Interfaces de hardware

| Dispositivo | Protocolo | Interacción |
|---|---|---|
| Computadora de escritorio / Laptop | HTTP/HTTPS (navegador web) | Acceso completo a todas las funcionalidades del sistema |
| Impresora térmica 80mm | ESC/POS a través del navegador | Impresión directa de boletas y tickets de venta |
| Lector de código de barras USB | Emulación de teclado (HID) | Ingreso de código de producto en el campo de búsqueda del POS |
| Dispositivo móvil (tablet/celular) | HTTP/HTTPS (navegador web) | Acceso limitado a funcionalidades básicas (consulta de inventario, recetario) |

### 9.3. Interfaces de software

| Componente de software | Versión | Tipo de interacción | Descripción |
|---|---|---|---|
| **MySQL** | 8.0+ | Conexión PDO | Base de datos relacional principal para almacenamiento persistente de todos los datos |
| **SQLite** | Integrada en PHP | Conexión PDO | Base de datos en memoria utilizada exclusivamente en el entorno de testing |
| **Laravel Framework** | 12 | Framework base | Proporciona el patrón MVC, ORM Eloquent, sistema de rutas, middleware y servicios |
| **Spatie Laravel Permission** | 6.25 | Paquete Composer | Gestión de roles (admin/empleado) y permisos de acceso |
| **Laravel Breeze** | Latest | Paquete Composer | Sistema de autenticación (login, logout, registro) |
| **Barryvdh DomPDF** | 3.1 | Paquete Composer | Generación de boletas y reportes en formato PDF |
| **API de Groq** | REST API | HTTP/HTTPS (cURL) | Proveedor primario de IA (modelo Llama 3.3 70B) |
| **API de Google Gemini** | REST API | HTTP/HTTPS (cURL) | Proveedor secundario de IA (modelo Gemini 1.5 Flash) |
| **GitHub Actions** | Servicio cloud | CI/CD | Ejecución automática de la suite de pruebas en cada push/PR |

### 9.4. Interfaces de comunicación

| Interfaz | Protocolo/Estándar | Descripción |
|---|---|---|
| **Navegador web ↔ Servidor** | HTTP/HTTPS | Todas las comunicaciones entre el cliente (navegador) y el servidor (Laravel) se realizan mediante protocolo HTTP. En producción se recomienda HTTPS con certificado SSL/TLS. |
| **Servidor ↔ Base de datos** | TCP/IP (puerto 3306) | La conexión entre Laravel y MySQL se realiza mediante el driver PDO con protocolo TCP/IP en el puerto predeterminado 3306. |
| **Servidor ↔ APIs de IA** | HTTPS (REST API) | Las consultas al asistente de IA se realizan mediante peticiones HTTP POST a los endpoints de las APIs de Groq y Gemini, utilizando autenticación por API key en los headers. Todas las comunicaciones a APIs externas se realizan mediante HTTPS con cifrado TLS 1.2 o superior. |
| **CSRF Token** | Formularios HTML + Middleware | Protección contra ataques CSRF mediante tokens únicos incluidos automáticamente en todos los formularios POST de Laravel. |
| **Búsqueda AJAX** | XMLHttpRequest / Fetch API | Los endpoints de búsqueda en tiempo real (`/api/productos/buscar`, `/api/clientes/dni`) utilizan peticiones asíncronas para retornar datos en formato JSON sin recargar la página. |
| **WhatsApp (futuro)** | API WhatsApp Business | Funcionalidad planificada para el envío de boletas digitales mediante la API de WhatsApp Business. Actualmente no implementada. |

---

## 10. Requerimientos no funcionales

**RNF-001 — Rendimiento:** El sistema debe responder a cualquier petición HTTP (carga de página, registro de venta, búsqueda AJAX) en un tiempo máximo de 3 segundos bajo condiciones normales de operación (hasta 10 usuarios concurrentes).

**RNF-002 — Disponibilidad:** El sistema debe estar disponible durante el horario de operación de la tienda (lunes a sábado, 8:00 a.m. – 8:00 p.m.), con una disponibilidad mínima del 99% durante dicho horario.

**RNF-003 — Seguridad — Autenticación:** Todas las rutas del sistema (excepto la página de login) deben estar protegidas por autenticación obligatoria. Las contraseñas deben almacenarse hasheadas con Bcrypt (12 rounds en producción).

**RNF-004 — Seguridad — Autorización:** El sistema debe implementar control de acceso basado en roles mediante Spatie Laravel Permission. Los usuarios con rol "empleado" no deben poder acceder a funcionalidades restringidas al rol "admin".

**RNF-005 — Seguridad — Protección CSRF:** Todas las rutas de tipo POST deben incluir protección automática contra ataques Cross-Site Request Forgery mediante tokens CSRF de Laravel.

**RNF-006 — Seguridad — Prevención de inyección SQL:** El sistema debe utilizar exclusivamente el ORM Eloquent y query builder de Laravel para la construcción de consultas a la base de datos, previniendo inyección SQL.

**RNF-007 — Integridad de datos:** Todas las operaciones de venta deben ejecutarse dentro de transacciones de base de datos para garantizar la consistencia ante fallos.

**RNF-008 — Escalabilidad:** La arquitectura multi-sucursal del sistema debe permitir la incorporación de nuevas sucursales sin modificaciones en el código fuente.

**RNF-009 — Mantenibilidad:** El código debe seguir el patrón MVC estricto de Laravel, con controladores delgados y lógica de negocio en los modelos. Se debe utilizar el estándar de codificación PSR-12 verificado con Laravel Pint.

**RNF-010 — Testeabilidad:** El sistema debe contar con una suite de pruebas automatizadas de **al menos 300 tests** distribuidos entre pruebas unitarias y de integración, ejecutados en un pipeline CI/CD. *(Estado actual del repositorio: **350 tests**.)*

**RNF-011 — Usabilidad:** La interfaz debe ser intuitiva y utilizable por personal con nivel de experiencia básico en tecnología, requiriendo un máximo de 2 horas de capacitación para la operación completa del POS.

**RNF-012 — Compatibilidad:** El sistema debe ser compatible con los navegadores Google Chrome 120+, Mozilla Firefox 120+ y Microsoft Edge 120+.

**RNF-013 — Portabilidad:** El sistema debe poder desplegarse tanto en un entorno local (XAMPP en Windows) como en un servidor Linux (Ubuntu), sin modificaciones en el código fuente.

**RNF-014 — Auditoría:** El sistema debe mantener un log de auditoría de las acciones críticas del sistema, incluyendo: ventas registradas, cambios de estado de reclamos y entregas de premios de fidelización.

**RNF-015 — Configurabilidad:** Los parámetros de negocio (IGV, montos de fidelización, stock mínimo, API keys) deben ser configurables mediante variables de entorno, sin necesidad de modificar el código fuente.

---

## 11. Otros requerimientos

**Requerimientos de base de datos:**
- La base de datos debe utilizar el conjunto de caracteres `utf8mb4` con collation `utf8mb4_unicode_ci` para soportar caracteres especiales y emojis.
- El esquema de la base de datos se gestiona mediante 25 archivos de migración de Laravel, lo que permite la recreación íntegra de la estructura en cualquier entorno.
- Los datos iniciales (roles, usuario administrador, productos de ejemplo, clientes demo) se cargan mediante el seeder `AdminSeeder`.

**Requerimientos de backup y recuperación:**
- Se recomienda realizar respaldos diarios de la base de datos MySQL mediante `mysqldump` o herramientas equivalentes.
- El código fuente se encuentra versionado en el repositorio GitHub `75220834-cloud/PROYECTO-NATURACOR`, garantizando la recuperación ante pérdida.

**Requerimientos de internacionalización:**
- El sistema opera exclusivamente en idioma español (es-PE).
- La moneda del sistema es el Sol peruano (S/).
- Los formatos de fecha utilizan el estándar dd/mm/aaaa.

**Requerimientos de comandos personalizados:**
- El sistema debe incluir el comando artisan `limpiar:ventas` para reinstalar la base de datos de ventas y datos asociados.
- El sistema debe incluir el comando artisan `fidelizacion:reiniciar` para reiniciar los acumulados de fidelización al fin de año, con opción `--force` para omitir la confirmación.

**Requerimientos legales:**
- Las boletas emitidas deben cumplir con la normativa tributaria peruana vigente respecto al Impuesto General a las Ventas (IGV del 18%).
- El sistema debe permitir la generación de boletas con numeración correlativa según lo establecido por la SUNAT.

---

## 12. Glosario

| Término | Definición |
|---|---|
| **AJAX** | Asynchronous JavaScript and XML. Técnica de desarrollo web que permite actualizar partes de una página sin recargarla completamente. |
| **API** | Application Programming Interface. Conjunto de protocolos y herramientas que permiten la comunicación entre sistemas de software. |
| **Blade** | Motor de plantillas de Laravel que permite combinar código PHP con HTML para generar vistas dinámicas. |
| **Bootstrap** | Framework CSS de código abierto que facilita el diseño de interfaces web responsivas y modernas. |
| **CI/CD** | Integración Continua / Despliegue Continuo. Práctica de desarrollo que automatiza la compilación, pruebas y despliegue del software. |
| **Cordial** | Bebida natural preparada a base de plantas medicinales, comercializada en las tiendas NATURACOR. |
| **CRUD** | Create, Read, Update, Delete. Operaciones básicas de gestión de datos en un sistema de información. |
| **CSRF** | Cross-Site Request Forgery. Ataque web que fuerza a un usuario autenticado a ejecutar acciones no deseadas; Laravel incluye protección automática. |
| **DNI** | Documento Nacional de Identidad. Documento de identificación de 8 dígitos utilizado en Perú. |
| **Eloquent** | ORM (Object-Relational Mapping) de Laravel que permite interactuar con la base de datos usando modelos PHP en lugar de SQL directo. |
| **ESC/POS** | Lenguaje de comandos estándar utilizado para comunicarse con impresoras térmicas de punto de venta. |
| **Factory** | Patrón de diseño de Laravel para generar datos ficticios de modelos durante las pruebas automatizadas. |
| **Gemini** | Modelo de inteligencia artificial de Google utilizado como proveedor secundario de IA en el sistema. |
| **GitHub Actions** | Servicio de CI/CD integrado en GitHub para automatizar flujos de trabajo como ejecución de tests. |
| **Groq** | Plataforma de inferencia de IA de alto rendimiento que ejecuta el modelo Llama 3.3 70B como proveedor primario de IA. |
| **IGV** | Impuesto General a las Ventas. Impuesto al consumo vigente en Perú, equivalente al 18% del valor de venta. |
| **Laravel** | Framework PHP de código abierto basado en el patrón MVC para el desarrollo de aplicaciones web. |
| **Middleware** | Componente de software que intercepta las peticiones HTTP antes de llegar al controlador, usado para autenticación y control de acceso. |
| **MVC** | Modelo-Vista-Controlador. Patrón arquitectónico que separa la lógica de datos, la interfaz de usuario y el flujo de control. |
| **MySQL** | Sistema de gestión de bases de datos relacional de código abierto utilizado como almacenamiento principal del sistema. |
| **Nopal** | Cactus comestible utilizado en la medicina natural; su jugo embotellado es el premio del programa de fidelización. |
| **PDO** | PHP Data Objects. Extensión de PHP que proporciona una interfaz uniforme para acceder a diferentes bases de datos. |
| **PHP** | Hypertext Preprocessor. Lenguaje de programación del lado del servidor utilizado como base del sistema NATURACOR. |
| **PHPUnit** | Framework de pruebas unitarias para PHP, estándar de facto para testing automatizado en proyectos Laravel. |
| **POS** | Point of Sale (Punto de Venta). Módulo del sistema donde se registran las ventas a los clientes. |
| **PSR-12** | Estándar de estilo de codificación de PHP que define convenciones de formato y estructura del código. |
| **Seeder** | Clase de Laravel que carga datos iniciales en la base de datos, como roles, usuarios y productos de ejemplo. |
| **Soft Delete** | Eliminación lógica de registros mediante una marca temporal (`deleted_at`) sin borrarlos físicamente de la base de datos. |
| **Spatie** | Empresa de software que desarrolla el paquete Laravel Permission para gestión de roles y permisos. |
| **SQLite** | Motor de base de datos ligero que almacena datos en memoria o archivo; usado en NATURACOR para el entorno de testing. |
| **SUNAT** | Superintendencia Nacional de Aduanas y de Administración Tributaria. Entidad reguladora tributaria del Perú. |
| **Vite** | Herramienta de compilación de assets frontend que proporciona recarga en caliente durante el desarrollo. |
| **XAMPP** | Paquete de software libre que incluye Apache, MySQL y PHP para el despliegue local de aplicaciones web. |
