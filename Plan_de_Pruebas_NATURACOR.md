# Plan de Pruebas de Software

## NATURACOR — Sistema Web Empresarial
**Fecha:** 15/04/2026

---

## Historial de Versiones

| Fecha | Versión | Autor | Organización | Descripción |
|---|---|---|---|---|
| 01/04/2026 | 1.0 | Bendezu Lagos Jack Joshua | NATURACOR | Creación inicial del Plan de Pruebas |
| 05/04/2026 | 1.1 | Julca Laureano Dickmar Wilber | NATURACOR | Incorporación de pruebas de fidelización y cordiales |
| 08/04/2026 | 1.2 | Reyes Cordero Italo Eduardo | NATURACOR | Actualización de criterios de aceptación y cobertura |
| 15/04/2026 | 2.0 | Bendezu Lagos Jack Joshua | NATURACOR | Versión final con 180 tests y CI/CD validado |

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
| **Gerente / Líder de Pruebas de Software** | Julca Laureano Dickmar Wilber |

---

## Aprobaciones

| Nombre y Apellido | Cargo | Departamento u Organización | Fecha | Firma |
|---|---|---|---|---|
| Anita María Cordero Campos | Dueña del negocio | Junín, Jauja, Jauja | 08/04/2026 | _________________ |
| Maglioni Arana Caparachin | Docente del curso | Universidad — Pruebas y Calidad de Software | 15/04/2026 | _________________ |
| Bendezu Lagos Jack Joshua | Líder de Proyecto | Equipo de Desarrollo NATURACOR | 15/04/2026 | _________________ |
| Julca Laureano Dickmar Wilber | Líder de Pruebas | Equipo de QA NATURACOR | 15/04/2026 | _________________ |

---

## Resumen Ejecutivo

El presente documento constituye el **Plan de Pruebas de Software** del proyecto **NATURACOR**, un sistema web de punto de venta (POS) y gestión integral desarrollado para una cadena de tiendas de productos naturales ubicada en Jauja, Junín, Perú. Este plan es de tipo **plan detallado**, ya que especifica con precisión cada uno de los niveles de pruebas, los casos a ejecutar, los criterios de aceptación, los entornos requeridos y la planificación completa del esfuerzo de testing.

**Propósito:** Garantizar que los 10 módulos funcionales del sistema NATURACOR cumplan con los requerimientos de negocio definidos por la propietaria del negocio, que el software sea estable, seguro y libre de defectos críticos antes de su despliegue en producción.

**Alcance en relación con el Plan de Proyecto:** Este plan de pruebas cubre la fase de verificación y validación del ciclo de vida del proyecto, abarcando desde las pruebas unitarias de la lógica de negocio hasta las pruebas de integración de flujos HTTP completos, pasando por pruebas de seguridad, regresión y aceptación.

**Resumen del esfuerzo de pruebas:**
- **180 casos de prueba automatizados** distribuidos en 20 archivos de test.
- **121 pruebas de integración (Feature)** que validan flujos HTTP completos, validaciones de formularios, control de acceso por roles y rutas protegidas.
- **59 pruebas unitarias (Unit)** que verifican la lógica de modelos, cálculos de IGV, relaciones Eloquent, castings y reglas de negocio aisladas.
- **Pipeline CI/CD** en GitHub Actions que ejecuta automaticamente todos los tests en cada push y pull request a la rama principal.

**Restricciones:**
- El equipo de pruebas está compuesto por 3 integrantes que cumplen roles duales (desarrollo y testing).
- El presupuesto es limitado al entorno académico (herramientas gratuitas y open source).
- El entorno de producción es local (XAMPP), sin servidores cloud dedicados por el momento.
- Las pruebas de rendimiento y carga están fuera del alcance actual por limitaciones de infraestructura.

---

## Alcance de las Pruebas

### Elementos de Pruebas

Los siguientes módulos, componentes y elementos serán sometidos a pruebas:

**Módulos Funcionales (10):**

| # | Módulo | Controlador | Elementos a probar |
|---|---|---|---|
| 1 | POS (Punto de Venta) | `VentaController` | Registro de ventas, cálculo de IGV incluido, generación de boletas B001-XXXXXX, descuentos, métodos de pago (efectivo, Yape, Plin), venta múltiple de productos |
| 2 | Inventario | `ProductoController` | CRUD de productos, control de stock mínimo, alertas de reposición, búsqueda AJAX por nombre/código, soft delete |
| 3 | Clientes | `ClienteController` | Registro por DNI, historial de compras, búsqueda AJAX por DNI, eliminación lógica (soft delete) |
| 4 | Caja | `CajaController` | Apertura y cierre de sesiones de caja, registro de movimientos (ingresos/egresos), totales por método de pago, diferencia al cierre |
| 5 | Fidelización | `FidelizacionController` | Regla 2026: acumulado ≥ S/500 en productos naturales → Botella 2L Nopal gratis, premios automáticos, entrega manual, reinicio anual |
| 6 | Cordiales | `CordialController` | 9 tipos de bebidas con precios fijos, promo litro puro S/80 → 1 toma gratis, cortesías para invitados, tipos acumulables |
| 7 | Asistente IA | `IAController` | Análisis de negocio con IA, cascada Groq → Gemini → modo offline, recomendaciones contextuales |
| 8 | Recetario | `RecetarioController` | CRUD de enfermedades, vinculación muchos-a-muchos con productos, instrucciones, búsqueda |
| 9 | Reclamos | `ReclamoController` | Registro de reclamos, flujo de estados (pendiente → en_proceso → resuelto), escalado al admin, log de auditoría |
| 10 | Reportes y Boletas | `ReporteController` / `BoletaController` | Reportes filtrados por fecha/sucursal/empleado/método, boletas PDF (80mm), ticket térmico |

**Módulos de Administración (solo admin):**

| Módulo | Controlador | Elementos a probar |
|---|---|---|
| Sucursales | `SucursalController` | CRUD de sucursales, restricción de acceso solo admin |
| Usuarios y Roles | `UsuarioController` | Gestión de usuarios, asignación de roles (admin/empleado) |
| Dashboard | `DashboardController` | KPIs del día/semana/mes, acceso restringido |

**Componentes Transversales:**
- Middleware de autenticación (Laravel Breeze)
- Middleware de roles (`RoleMiddleware` con Spatie Permission)
- Protección CSRF en todas las rutas POST
- Transacciones de base de datos (`DB::beginTransaction()`)
- Soft Deletes en modelos críticos (Venta, Producto, Cliente)
- Configuración centralizada (`config/naturacor.php`)
- Factories y Seeders para datos de prueba

---

### Nuevas Funcionalidades a Probar

Desde el punto de vista del usuario, las funcionalidades a probar son:

1. **Registrar una venta en el punto de venta (POS):** El empleado puede seleccionar productos, aplicar descuentos, elegir método de pago (efectivo, Yape o Plin) y generar una boleta con número correlativo B001-XXXXXX con IGV incluido.

2. **Gestionar el inventario de productos:** El administrador puede agregar, editar, buscar y dar de baja productos. El sistema muestra alertas cuando un producto alcanza el stock mínimo.

3. **Registrar y buscar clientes por DNI:** El empleado puede dar de alta clientes ingresando su DNI, nombre y datos de contacto. La búsqueda AJAX permite ubicar clientes rápidamente desde el POS.

4. **Administrar sesiones de caja:** El empleado abre caja al inicio de su turno con un monto inicial, registra ingresos y egresos durante el día, y al cierre el sistema calcula la diferencia entre el total esperado y el conteo real.

5. **Sistema de fidelización automático:** Cuando un cliente acumula compras de productos naturales por S/500 o más, el sistema le genera automáticamente un premio (Botella 2L Nopal gratis). El empleado marca la entrega física del premio.

6. **Venta de cordiales con promociones:** El sistema permite registrar ventas de 9 tipos de cordiales con precios fijos. Si el cliente compra un litro puro a S/80, obtiene 1 toma gratis. Se pueden registrar cortesías para invitados.

7. **Consultar al asistente de inteligencia artificial:** El usuario puede hacer consultas de análisis de negocio. El sistema intenta responder con Groq (Llama 3.3 70B), si falla usa Gemini, y si ambos fallan opera en modo offline con recomendaciones locales.

8. **Consultar el recetario de productos naturales:** El usuario puede buscar enfermedades y ver qué productos naturales se recomiendan, con instrucciones de uso. El admin puede crear, editar y vincular enfermedades con productos.

9. **Registrar y dar seguimiento a reclamos:** Los clientes pueden registrar reclamos, el empleado los escala al administrador, quien los resuelve. El flujo de estados es: pendiente → en_proceso → resuelto.

10. **Generar reportes y boletas:** El administrador puede filtrar reportes de ventas por fecha, sucursal, empleado y método de pago. Las boletas se pueden descargar en PDF optimizado para impresión en papel de 80mm.

---

### Pruebas de Regresión

Las siguientes funcionalidades no están directamente involucradas en los desarrollos nuevos, pero sus componentes podrían ser afectados por cambios recientes y deben verificarse:

1. **Inicio de sesión y cierre de sesión:** Verificar que la autenticación sigue funcionando correctamente después de cambios en los middleware de roles y permisos.

2. **Acceso basado en roles (admin vs. empleado):** Confirmar que las restricciones de acceso se mantienen intactas. Un empleado no debe poder acceder a funciones de administrador como gestión de sucursales o usuarios.

3. **Aislamiento de datos por sucursal:** Tras cualquier cambio en la lógica de ventas o inventario, verificar que los empleados solo ven datos de su sucursal asignada y no los de otras.

4. **Cálculo de IGV (18%) incluido en precios:** Los cambios en el módulo POS no deben alterar la fórmula de cálculo del IGV que se aplica sobre todos los productos.

5. **Numeración correlativa de boletas (B001-XXXXXX):** Verificar que la secuencia de boletas no se ve afectada por cambios en el flujo de ventas o en la base de datos.

6. **Soft Delete de productos, clientes y ventas:** Confirmar que la eliminación lógica sigue funcionando y que los registros eliminados no aparecen en las vistas activas pero se preservan en la base de datos.

7. **Búsqueda AJAX de productos y clientes:** Validar que los endpoints de búsqueda en tiempo real desde el POS continúan respondiendo correctamente.

---

### Funcionalidades a No Probar

Las siguientes funcionalidades **no** serán incluidas en el alcance de este plan de pruebas:

| Funcionalidad | Razón de exclusión | Riesgo asumido |
|---|---|---|
| **Pruebas de rendimiento y carga** | No se cuenta con infraestructura cloud dedicada ni herramientas de pruebas de carga como JMeter o k6 | El sistema podría presentar problemas de rendimiento bajo alta concurrencia, aunque para una tienda naturista el volumen esperado es bajo (< 50 usuarios concurrentes) |
| **Pruebas en dispositivos móviles nativos** | El sistema es una aplicación web responsiva, no una app nativa | La experiencia en móvil depende del navegador; se asume que Bootstrap 5 gestiona la responsividad adecuadamente |
| **Pruebas de la API de Groq / Gemini en producción** | Las API keys son recursos externos de terceros que no se pueden controlar. Los tests mockean estas APIs | Si las APIs de IA caen, el sistema opera en modo offline; no hay pérdida de funcionalidad crítica |
| **Pruebas de migración de datos desde sistemas legacy** | NATURACOR es un sistema nuevo sin datos previos que migrar | No existe riesgo de pérdida de datos históricos |
| **Pruebas de despliegue en producción cloud** | El entorno actual es local (XAMPP). El despliegue cloud se realizará en fases posteriores | La configuración de producción podría diferir del entorno de desarrollo local |
| **Pruebas de envío de WhatsApp para boletas** | Depende de la integración con la API de WhatsApp Business, actualmente no configurada | El usuario no podrá enviar boletas por WhatsApp hasta que se integre la API |

---

## Enfoque de Pruebas (Estrategia)

La estrategia de pruebas del proyecto NATURACOR se basa en un enfoque de **múltiples niveles** que combina pruebas automatizadas con validaciones manuales, priorizando la automatización para garantizar cobertura continua a través del pipeline CI/CD.

### Tipos de Pruebas a Realizar

#### 1. Pruebas Unitarias (Unit Tests)
- **Propósito:** Verificar la lógica interna de los modelos Eloquent, métodos de cálculo, accessors, mutators, scopes y relaciones de forma aislada.
- **Herramienta:** PHPUnit 11.5
- **Archivos:** 7 archivos en `tests/Unit/`
- **Cantidad:** 59 tests
- **Cobertura:**
  - `ClienteUnitTest` (12 tests): Método `nombreCompleto()`, `puedeReclamarPremio()`, reinicio de acumulados.
  - `CordialVentaUnitTest` (13 tests): Precios fijos, labels de presentación, tipos acumulables.
  - `ProductoUnitTest` (10 tests): Cálculo de IGV, stock crítico, soft delete, casts de atributos.
  - `VentaUnitTest` (8 tests): Boleta correlativa, relaciones con detalles, soft delete.
  - `FidelizacionCanjeUnitTest` (8 tests): Constantes del programa, scopes, relaciones, entrega de premios.
  - `RecetarioUnitTest` (7 tests): Pivot enfermedad-producto, instrucciones de uso, ordenamiento.
  - `ExampleTest` (1 test): Test básico de verificación del entorno.

#### 2. Pruebas de Integración / Funcionales (Feature Tests)
- **Propósito:** Validar flujos HTTP completos simulando la interacción real del usuario con el sistema: peticiones GET/POST, validaciones de formularios, redirecciones, control de acceso por roles y respuestas esperadas.
- **Herramienta:** PHPUnit 11.5 con `RefreshDatabase` trait (SQLite en memoria)
- **Archivos:** 13 archivos en `tests/Feature/`
- **Cantidad:** 121 tests
- **Cobertura principal:**
  - `SeguridadTest` (16 tests): CSRF, inyección SQL, roles, aislamiento de sucursales.
  - `FidelizacionTest` (13 tests): Acumulado S/500, canjes, premios, promo litro puro.
  - `ReclamoTest` (12 tests): Flujo completo de estados, escalado, resolución.
  - `RecetarioTest` (12 tests): CRUD completo, sincronización de productos, búsqueda.
  - `CordialTest` (11 tests): Venta de cordial, promoción, cortesía de invitado.
  - `ProductoCrudTest` (10 tests): CRUD, búsqueda AJAX, alertas de stock bajo.
  - `IATest` (10 tests): Modo offline, análisis de negocio, estructura de datos.
  - `VentaTest` (9 tests): POS completo, venta múltiple, IGV, generación de boleta.
  - `ClienteCrudTest` (8 tests): CRUD, validación DNI único, búsqueda AJAX.
  - `SucursalCrudTest` (7 tests): CRUD de sucursales restringido al admin.
  - `CajaTest` (6 tests): Apertura, cierre, movimientos, venta con caja abierta.
  - `AutenticacionTest` (6 tests): Login, logout, acceso protegido, diferenciación de roles.

#### 3. Pruebas de Seguridad
- **Propósito:** Verificar que el sistema está protegido contra las vulnerabilidades más comunes.
- **Incluido en:** `SeguridadTest.php` (16 tests dedicados)
- **Aspectos cubiertos:**
  - Protección CSRF en todas las rutas POST.
  - Prevención de inyección SQL a través de Eloquent ORM.
  - Control de acceso basado en roles (admin/empleado) con Spatie Permission.
  - Aislamiento de datos por sucursal.
  - Hashing de contraseñas con Bcrypt (12 rounds en producción, 4 en tests).
  - Validación de entrada en todos los formularios.

#### 4. Pruebas de Regresión
- **Propósito:** Confirmar que las funcionalidades existentes no se ven afectadas por cambios nuevos.
- **Mecanismo:** La suite completa de 180 tests se ejecuta automáticamente en cada push/PR a través del pipeline CI/CD de GitHub Actions. Cualquier regresión es detectada de inmediato.

#### 5. Pruebas de Aceptación del Usuario (UAT)
- **Propósito:** Validar con la propietaria del negocio que el sistema cumple con sus expectativas.
- **Mecanismo:** Sesiones de demostración presencial donde se ejecutan los flujos de negocio principales en el entorno de producción local.

### Configuraciones a Probar

| Entorno | Base de datos | Objetivo |
|---|---|---|
| Testing (CI/CD) | SQLite en memoria (`:memory:`) | Ejecución rápida, aislada y repetible de los 180 tests |
| Desarrollo local | MySQL 8.0 (XAMPP) | Validación con datos reales y persistentes |
| Producción local | MySQL 8.0 (XAMPP) | Entorno final para la operación diaria |

### Subconjuntos de Datos

- **Tests automatizados:** Se utilizan Factories (`ClienteFactory`, `ProductoFactory`, `VentaFactory`, etc.) para generar datos de prueba controlados y reproducibles.
- **Tests de integración:** Cada test utiliza el trait `RefreshDatabase` que ejecuta todas las migraciones y limpia la base de datos entre cada test, garantizando aislamiento total.
- **Datos iniciales:** El seeder `AdminSeeder` carga los datos base necesarios: roles (admin, empleado), usuario administrador, productos de ejemplo y clientes demo.

---

## Criterios de Aceptación o Rechazo

### Criterios de Aceptación

El Plan de Pruebas de Software se considerará completado exitosamente cuando se cumplan **todos** los siguientes criterios:

| # | Criterio | Métrica objetivo |
|---|---|---|
| 1 | Todos los tests unitarios pasan | 59/59 tests exitosos (100%) |
| 2 | Todos los tests de integración pasan | 121/121 tests exitosos (100%) |
| 3 | Total de tests ejecutados | 180/180 tests pasados (100%) |
| 4 | Pipeline CI/CD en GitHub Actions | Estado verde ✅ en la rama `main` |
| 5 | Cobertura de módulos | 10/10 módulos funcionales cubiertos con al menos 1 test |
| 6 | Pruebas de seguridad | 16/16 pruebas de seguridad exitosas |
| 7 | Defectos críticos | 0 defectos de severidad crítica o bloqueante abiertos |
| 8 | Defectos mayores | ≤ 2 defectos de severidad mayor abiertos con plan de corrección |
| 9 | Pruebas de regresión | 0 regresiones detectadas tras correcciones |
| 10 | Aprobación del cliente | Firma de aceptación de la propietaria Anita María Cordero Campos |

### Criterios de Rechazo

El plan de pruebas será rechazado si se presenta **cualquiera** de las siguientes condiciones:

- Más del 5% de los tests (9 o más) fallan de forma persistente.
- Se detecta al menos 1 defecto de severidad crítica o bloqueante no resuelto.
- El pipeline CI/CD falla de forma consistente en más de 3 ejecuciones consecutivas.
- Algún módulo funcional completo no tiene cobertura de tests (0 tests para el módulo).
- Se detectan vulnerabilidades de seguridad críticas (inyección SQL, bypass de autenticación, acceso no autorizado a datos de otra sucursal).

---

### Criterios de Suspensión

Las actividades de pruebas se **suspenderán** bajo las siguientes condiciones:

1. **Defecto bloqueante en el entorno:** Si la base de datos de testing no se puede crear/migrar, o el servidor de desarrollo no inicia, las pruebas se suspenden hasta que el entorno sea restaurado.
2. **Fallo de migración:** Si una migración de base de datos produce errores que impiden la ejecución de los seeders o tests, todas las pruebas se suspenden.
3. **Más del 30% de tests fallidos en una ejecución:** Si 54 o más tests fallan en una sola ejecución, se asume un problema sistémico y se suspende la suite completa.
4. **Indisponibilidad del servidor CI/CD:** Si GitHub Actions no está disponible por un período prolongado (> 4 horas), las pruebas automatizadas en la nube se suspenden y se ejecutan localmente.
5. **Cambio mayor en requerimientos:** Si la propietaria solicita un cambio significativo en la lógica de negocio que afecte a más del 50% de los módulos, las pruebas se suspenden hasta que el código sea estabilizado.

---

### Criterios de Reanudación

Las pruebas se **reanudarán** cuando se cumplan las siguientes condiciones:

1. **Entorno restaurado:** El entorno de pruebas (SQLite en memoria o MySQL local) ha sido verificado y puede ejecutar al menos el test `ExampleTest` exitosamente.
2. **Corrección del defecto bloqueante:** El defecto que causó la suspensión ha sido corregido, validado con un test específico y deployeado en la rama correspondiente.
3. **Tasa de fallos normalizada:** Tras las correcciones, se ejecuta una ejecución de "smoke test" (los tests `ExampleTest` + `AutenticacionTest`) y si pasan correctamente, se reanuda la suite completa.
4. **Disponibilidad del CI/CD restaurada:** Para suspensiones por indisponibilidad de GitHub Actions, se reanuda cuando el servicio vuelve a estar operativo.
5. **Requerimientos estabilizados:** Para suspensiones por cambio de requerimientos, se reanuda cuando el código refleja los nuevos requerimientos y el equipo de desarrollo confirma la estabilidad del sistema.

---

## Entregables

Los siguientes documentos y artefactos serán entregados como resultado de la ejecución del Plan de Pruebas:

| # | Entregable | Descripción | Formato |
|---|---|---|---|
| 1 | Plan de Pruebas de Software | El presente documento, que define la estrategia, alcance, criterios y planificación de las pruebas | Documento (Word/PDF) |
| 2 | Suite de Casos de Prueba Automatizados | 180 tests en 20 archivos PHP organizados en `tests/Unit/` y `tests/Feature/` | Código PHP (PHPUnit) |
| 3 | Reporte de Ejecución de Tests | Salida de `php artisan test` mostrando los 180 tests con su resultado (passed/failed) | Captura de pantalla / Log de terminal |
| 4 | Reporte de Pipeline CI/CD | Estado del badge de GitHub Actions (verde/rojo) y log de la última ejecución | Captura de GitHub Actions |
| 5 | Log de Defectos / Incidencias | Registro de bugs encontrados durante las pruebas con su severidad, estado y resolución | GitHub Issues / Documento |
| 6 | Evidencias de Pruebas Funcionales | Capturas de pantalla de las funcionalidades probadas: POS, fidelización, reclamos, reportes | Capturas de pantalla (PNG) |
| 7 | Reporte de Cobertura de Código | Resultados del análisis de cobertura mostrando el porcentaje de código cubierto por los tests | HTML generado por PHPUnit / Xdebug |
| 8 | Acta de Aceptación | Documento firmado por la propietaria validando que el sistema cumple con los requerimientos | Documento firmado (PDF) |

---

## Recursos

### Requerimientos de Entornos – Hardware

| Recurso | Especificación mínima | Cantidad | Propósito |
|---|---|---|---|
| PC de desarrollo/testing | CPU: Intel Core i5 (8va gen.) o superior, RAM: 8 GB, Disco: 256 GB SSD, OS: Windows 10/11 | 3 | Desarrollo, ejecución de tests locales y pruebas manuales |
| Servidor CI/CD | Ubuntu Latest (GitHub Actions hosted runner), 2 vCPU, 7 GB RAM | 1 (cloud) | Ejecución automática del pipeline de tests en cada push/PR |
| Servidor de Base de Datos (desarrollo) | Incluido en la PC de desarrollo — MySQL 8.0 vía XAMPP | 3 | Base de datos MySQL local para pruebas con datos persistentes |
| Conectividad de red | Internet mínimo 10 Mbps, acceso a GitHub.com y a las APIs de Groq/Gemini | Permanente | CI/CD, push/pull de código, consultas a APIs de IA |
| Impresora térmica (opcional) | Impresora de tickets 80mm compatible con ESC/POS | 1 | Pruebas de impresión de boletas en formato térmico |

---

### Requerimientos de Entornos – Software

| Software | Versión | Propósito | Instalación |
|---|---|---|---|
| **PHP** | 8.2+ | Runtime del backend Laravel | XAMPP o instalación standalone |
| **Composer** | 2.x | Gestión de dependencias PHP | https://getcomposer.org |
| **MySQL** | 8.0+ | Base de datos para desarrollo/producción | XAMPP (incluido) |
| **Node.js** | 18+ | Compilación de assets frontend (Vite + Tailwind) | https://nodejs.org |
| **Git** | Cualquier versión | Control de versiones y push a GitHub | https://git-scm.com |
| **XAMPP** | 8.2+ | Stack completo (Apache + MySQL + PHP) para Windows | https://www.apachefriends.org |
| **Laravel Framework** | 12 | Framework base del proyecto | Instalado vía Composer |
| **SQLite** (extensión PHP) | Incluida en PHP | Base de datos en memoria para tests (`pdo_sqlite`) | Habilitar en `php.ini` |
| **Navegador web** | Chrome 120+ o Firefox 120+ | Pruebas funcionales manuales y verificación de UI | Instalado en PC de desarrollo |
| **GitHub** | Servicio cloud | Repositorio, CI/CD (Actions), Issues, PRs | https://github.com |

**Accesos requeridos:**
- Acceso de escritura al repositorio GitHub: `75220834-cloud/PROYECTO-NATURACOR`
- Acceso a la base de datos `naturacor` en MySQL local con usuario `root`
- Variables de entorno configuradas en `.env` (API keys de Groq y Gemini opcionales)

---

### Herramientas de Pruebas Requeridas

| Herramienta | Versión | Tipo | Propósito |
|---|---|---|---|
| **PHPUnit** | 11.5.50 | Framework de testing automatizado | Ejecución de 180 tests unitarios y de integración |
| **GitHub Actions** | N/A (servicio cloud) | Plataforma de CI/CD | Ejecución automática de la suite completa en cada push/PR |
| **Mockery** | 1.6 | Librería de mocking | Simulación de APIs externas (Groq, Gemini) en los tests |
| **Faker (FakerPHP)** | 1.23 | Generador de datos ficticios | Generación de datos realistas en las Factories de prueba |
| **Laravel Breeze** | Latest | Framework de autenticación | Proporciona rutas y controladores de auth para testing |
| **Spatie Laravel Permission** | 6.25 | Gestión de roles y permisos | Configuración de roles admin/empleado en los tests |
| **SQLite (en memoria)** | Integrada | Base de datos ligera | Entorno de testing aislado y rápido con `RefreshDatabase` |
| **Xdebug** (opcional) | 3.x | Extensión PHP de debugging | Generación de reportes de cobertura de código |
| **Laravel Pint** | 1.24 | Linter de código PHP | Verificación del estilo de código (PSR-12) |

---

### Personal

| Rol | Nombre | Responsabilidades | Dedicación |
|---|---|---|---|
| **Líder de Proyecto / Desarrollador** | Bendezu Lagos Jack Joshua | Coordinación general, desarrollo de módulos POS, Inventario, Caja y Reportes. Diseño de la arquitectura de tests. | 100% |
| **Líder de Pruebas / Analista QA** | Julca Laureano Dickmar Wilber | Diseño y ejecución de casos de prueba, configuración del pipeline CI/CD, análisis de resultados, reporte de defectos. | 100% |
| **Desarrollador / Tester** | Reyes Cordero Italo Eduardo | Desarrollo de módulos de Fidelización, Cordiales y Recetario. Escritura de tests unitarios y de integración para estos módulos. | 100% |
| **Cliente / Validador de Aceptación** | Anita María Cordero Campos | Validación funcional desde la perspectiva del usuario final, aprobación de criterios de aceptación. | Parcial (sesiones programadas) |

---

### Entrenamiento

| Área de entrenamiento | Dirigido a | Método | Duración estimada |
|---|---|---|---|
| Framework PHPUnit 11 y testing en Laravel | Todo el equipo | Documentación oficial de Laravel + tutoriales prácticos | 8 horas |
| GitHub Actions y configuración de pipelines CI/CD | Julca Laureano Dickmar Wilber | Documentación de GitHub Actions + configuración práctica | 4 horas |
| Spatie Laravel Permission (roles y permisos) | Todo el equipo | Documentación oficial del paquete + implementación en el proyecto | 3 horas |
| Uso del sistema NATURACOR (capacitación funcional) | Anita María Cordero Campos | Sesión presencial con demostración de todos los módulos | 2 horas |
| Mockery y mocking de APIs externas | Reyes Cordero Italo Eduardo | Documentación de Mockery + implementación en `IATest` | 2 horas |
| Estándares de codificación PSR-12 y Laravel Pint | Todo el equipo | Configuración y ejecución de Laravel Pint con resolución de hallazgos | 1 hora |

---

## Planificación y Organización

### Procedimientos para las Pruebas

El equipo seguirá la siguiente metodología de pruebas durante la ejecución del plan:

**1. Escritura de Tests (TDD parcial):**
- Antes de implementar una nueva funcionalidad, se define al menos 1 test que verifique el comportamiento esperado.
- Los tests se escriben en PHP usando PHPUnit, ubicados en `tests/Unit/` (lógica aislada) o `tests/Feature/` (flujos HTTP).
- Cada test utiliza el trait `RefreshDatabase` para garantizar aislamiento entre tests.

**2. Ejecución Local:**
- El desarrollador ejecuta `php artisan test` localmente antes de hacer push.
- Si algún test falla, se corrige el código antes de subir cambios.

**3. Ejecución Automatizada (CI/CD):**
- Al hacer push o crear un Pull Request a la rama `main`, GitHub Actions ejecuta automáticamente todos los 180 tests.
- El pipeline configura PHP 8.2, instala dependencias con Composer (con cache), configura SQLite en memoria y ejecuta `php artisan test`.
- Si algún test falla, el pipeline marca el commit como fallido ❌ y el equipo es notificado.

**4. Reporte de Defectos:**
- Los defectos encontrados se documentan como Issues en el repositorio de GitHub.
- Cada issue incluye: descripción, pasos para reproducir, resultado esperado vs. actual, severidad y asignado.
- Los defectos bloqueantes se priorizan para corrección inmediata.

**5. Verificación de Corrección:**
- Tras corregir un defecto, se agrega un test de regresión específico que valide la corrección.
- Se ejecuta la suite completa para confirmar que no se introdujeron nuevas regresiones.

---

### Matriz de Responsabilidades (RACI)

| Actividad | Bendezu Lagos Jack J. (Líder Proyecto) | Julca Laureano Dickmar W. (Líder QA) | Reyes Cordero Italo E. (Dev/Tester) | Anita María Cordero C. (Cliente) |
|---|---|---|---|---|
| Diseño del Plan de Pruebas | A | R | C | I |
| Escritura de tests unitarios | R | C | R | — |
| Escritura de tests de integración | R | R | R | — |
| Configuración CI/CD (GitHub Actions) | C | R | I | — |
| Ejecución de pruebas automatizadas | I | R | R | — |
| Análisis de resultados de tests | C | R | C | I |
| Reporte de defectos | R | R | R | — |
| Corrección de defectos | R | I | R | — |
| Pruebas de regresión | I | R | R | — |
| Pruebas de aceptación (UAT) | R | C | C | A |
| Generación de reportes finales | C | R | C | I |
| Aprobación del Plan de Pruebas | A | R | I | A |

**Leyenda:** R = Responsable, A = Aprobador, C = Consultado, I = Informado

---

### Cronograma

| Fase | Actividad | Fecha inicio | Fecha fin | Duración | Predecesor | Responsable |
|---|---|---|---|---|---|---|
| **Fase 1: Planificación** | Diseño del Plan de Pruebas | 01/04/2026 | 05/04/2026 | 5 días | — | Julca Laureano D. |
| | Definición de casos de prueba | 02/04/2026 | 06/04/2026 | 5 días | — | Todo el equipo |
| | Configuración del entorno de pruebas | 01/04/2026 | 03/04/2026 | 3 días | — | Bendezu Lagos J. |
| **Fase 2: Implementación de Tests** | Tests unitarios (modelos y lógica) | 02/04/2026 | 08/04/2026 | 7 días | Config. entorno | Reyes Cordero I. |
| | Tests de integración (Feature) | 03/04/2026 | 10/04/2026 | 8 días | Config. entorno | Todo el equipo |
| | Tests de seguridad | 05/04/2026 | 08/04/2026 | 4 días | Tests unitarios | Bendezu Lagos J. |
| | Configuración CI/CD (GitHub Actions) | 01/04/2026 | 03/04/2026 | 3 días | — | Julca Laureano D. |
| **Fase 3: Ejecución** | Ejecución de suite completa (local) | 10/04/2026 | 12/04/2026 | 3 días | Tests completados | Todo el equipo |
| | Validación del pipeline CI/CD | 10/04/2026 | 12/04/2026 | 3 días | Config. CI/CD | Julca Laureano D. |
| | Corrección de defectos encontrados | 10/04/2026 | 14/04/2026 | 5 días | Ejecución | Bendezu Lagos J. / Reyes Cordero I. |
| | Re-ejecución de tests (regresión) | 13/04/2026 | 14/04/2026 | 2 días | Corrección defectos | Julca Laureano D. |
| **Fase 4: Validación y Cierre** | Pruebas de aceptación (UAT) con cliente | 14/04/2026 | 15/04/2026 | 2 días | Suite verde | Anita María C. |
| | Generación de reportes finales | 15/04/2026 | 15/04/2026 | 1 día | UAT aprobado | Julca Laureano D. |
| | Entrega y cierre del Plan de Pruebas | 15/04/2026 | 15/04/2026 | 1 día | Reportes listos | Bendezu Lagos J. |

**Hitos relevantes:**
- ✅ 03/04/2026: Entorno de pruebas configurado y CI/CD operativo.
- ✅ 10/04/2026: 180 tests implementados y ejecutándose localmente.
- ✅ 12/04/2026: Pipeline CI/CD verde en GitHub Actions.
- ✅ 15/04/2026: Plan de Pruebas aprobado y entregado.

---

### Premisas

1. **Disponibilidad del equipo:** Los tres integrantes del equipo dedicarán tiempo completo al proyecto durante el período de pruebas (01/04/2026 – 15/04/2026).
2. **Estabilidad del entorno:** El entorno de desarrollo local (XAMPP con PHP 8.2 y MySQL 8.0) se mantiene estable y operativo durante todo el ciclo de pruebas.
3. **Disponibilidad de GitHub Actions:** El servicio de GitHub Actions estará disponible para la ejecución del pipeline CI/CD sin interrupciones prolongadas.
4. **Requerimientos congelados:** Los requerimientos funcionales del sistema están definidos y aprobados por la propietaria. No se esperan cambios significativos durante el período de pruebas.
5. **Metodología de testing:** Se utilizará PHPUnit como framework principal de pruebas, con el trait `RefreshDatabase` de Laravel para garantizar aislamiento entre tests.
6. **Herramientas open source:** Todas las herramientas de prueba son gratuitas y de código abierto (PHPUnit, Mockery, Faker, GitHub Actions con plan gratuito).
7. **Base de datos de testing:** Los tests se ejecutarán exclusivamente sobre SQLite en memoria, sin afectar la base de datos MySQL de desarrollo/producción.
8. **Acceso al repositorio:** Todos los integrantes del equipo tienen acceso de escritura al repositorio GitHub `75220834-cloud/PROYECTO-NATURACOR`.
9. **Capacitación previa:** El equipo ha sido capacitado en PHPUnit, Laravel Testing y GitHub Actions antes del inicio de la fase de pruebas.

---

## Dependencias y Riesgos

| # | Riesgo | Categoría | Probabilidad | Impacto | Plan de mitigación / contingencia |
|---|---|---|---|---|---|
| 1 | **Dependencia con desarrollo:** Los tests dependen de que el código fuente esté estable. Cambios de última hora en los controladores pueden romper tests existentes. | Dependencia con desarrollos | Alta | Alto | Congelar el código 2 días antes de la entrega final. Ejecutar la suite completa después de cada cambio. Usar ramas de feature con PRs obligatorios. |
| 2 | **Indisponibilidad de GitHub Actions:** El servicio cloud podría sufrir interrupciones temporales que impidan la ejecución del pipeline CI/CD. | Disponibilidad de recursos | Baja | Medio | Mantener la capacidad de ejecutar la suite completa localmente con `php artisan test`. Documentar los resultados manuales si el CI/CD falla. |
| 3 | **Disponibilidad del equipo:** Los integrantes del equipo tienen otras obligaciones académicas (7mo ciclo) que podrían reducir su disponibilidad. | Disponibilidad de recursos | Media | Alto | Distribuir las tareas de forma equitativa. Mantener un cronograma con holgura de 2 días para imprevistos. Comunicación constante por grupo de WhatsApp. |
| 4 | **Restricciones de tiempo:** El cronograma de entrega del curso podría acortarse si el docente modifica las fechas de entrega. | Restricciones de tiempo | Baja | Alto | Priorizar los tests de los módulos más críticos (POS, Fidelización, Seguridad). Tener una versión "mínima viable" del plan de pruebas lista con anticipación. |
| 5 | **Cambio en requerimientos del negocio:** La propietaria podría solicitar cambios en la lógica de fidelización o en los precios de cordiales después de que los tests estén escritos. | Premisas que no se cumplen | Media | Medio | Diseñar los tests de forma parametrizable usando `config('naturacor.*')`. Mantener los valores de negocio en variables de entorno para facilitar cambios. |
| 6 | **Incompatibilidad de versiones:** Actualizaciones de Laravel, PHPUnit o dependencias podrían causar fallos inesperados en los tests. | Dependencia con desarrollos | Baja | Alto | Fijar las versiones en `composer.json` (`"phpunit/phpunit": "^11.5.50"`). No actualizar dependencias durante el período de pruebas. Usar `composer.lock` para reproducibilidad. |
| 7 | **Fallo en extensión pdo_sqlite:** Algunos equipos con XAMPP no tienen habilitada la extensión `pdo_sqlite` en `php.ini`, lo cual impide ejecutar los tests. | Disponibilidad de recursos | Media | Medio | Documentar el proceso de habilitación de `pdo_sqlite` en el README. Verificar la configuración de PHP en todos los equipos al inicio del proyecto. |
| 8 | **Pérdida de API keys de IA:** Si las API keys de Groq/Gemini son revocadas o expiran, los tests que dependen de estas APIs podrían fallar. | Dependencia con otros proyectos | Baja | Bajo | Todos los tests de IA utilizan Mockery para simular las respuestas de las APIs. El sistema opera en modo offline si las APIs no están disponibles. |

---

## Referencias

| # | Documento | Descripción |
|---|---|---|
| 1 | Plan de Proyecto NATURACOR (PMO.doc) | Documento general del proyecto con alcance, objetivos, cronograma global y stakeholders |
| 2 | Formato 06: Requerimientos Funcionales | Listado de todos los requerimientos funcionales del sistema organizados por módulo |
| 3 | Formato 04: Modelo BPM Actual (Proceso AS-IS) | Análisis del proceso de atención al cliente antes de la implementación del sistema |
| 4 | Formato 05: Modelo BPM Mejorado (Proceso TO-BE) | Proceso mejorado de gestión de atención al cliente con el sistema de fidelización |
| 5 | README.md del proyecto | Documentación técnica completa del proyecto con instrucciones de instalación, arquitectura y suite de pruebas |
| 6 | Archivo `phpunit.xml` | Configuración del framework de pruebas PHPUnit con suites, variables de entorno y cobertura |
| 7 | Archivo `.github/workflows/tests.yml` | Definición del pipeline CI/CD en GitHub Actions |
| 8 | Archivo `config/naturacor.php` | Configuración centralizada de reglas de negocio (IGV, fidelización, APIs) |
| 9 | Documentación oficial de Laravel 12 | https://laravel.com/docs/12.x — Referencia para testing, Eloquent, middleware |
| 10 | Documentación de PHPUnit 11 | https://docs.phpunit.de/en/11.5/ — Referencia para assertions, fixtures, data providers |
| 11 | Documentación de Spatie Laravel Permission | https://spatie.be/docs/laravel-permission/v6 — Gestión de roles y permisos |
| 12 | IEEE 829 — Standard for Software Test Documentation | Estándar internacional de referencia para la estructura de planes de pruebas |

---

## Glosario

| Término | Definición |
|---|---|
| **CI/CD** | Integración Continua / Despliegue Continuo. Práctica de desarrollo que automatiza la compilación, pruebas y despliegue del software con cada cambio en el código fuente. |
| **CRUD** | Create, Read, Update, Delete. Operaciones básicas de gestión de datos en un sistema de información. |
| **CSRF** | Cross-Site Request Forgery. Ataque que fuerza a un usuario autenticado a ejecutar acciones no deseadas. Laravel incluye protección automática mediante tokens. |
| **Factory** | Patrón de diseño utilizado en Laravel para generar datos ficticios de modelos Eloquent durante las pruebas automatizadas. |
| **Feature Test** | Prueba de integración que simula una petición HTTP completa, verificando controladores, middleware, validaciones y respuestas del servidor. |
| **GitHub Actions** | Servicio de CI/CD integrado en GitHub que permite automatizar flujos de trabajo como la ejecución de tests en cada push. |
| **IGV** | Impuesto General a las Ventas. En Perú es del 18% y en NATURACOR está incluido en los precios de los productos. |
| **Middleware** | Componente de software que intercepta las peticiones HTTP antes de llegar al controlador, usado para autenticación y control de acceso. |
| **Mockery** | Librería PHP para crear objetos simulados (mocks) en pruebas unitarias, permitiendo aislar la unidad bajo prueba de sus dependencias. |
| **MVC** | Modelo-Vista-Controlador. Patrón arquitectónico que separa la lógica de datos (Modelo), la interfaz de usuario (Vista) y el control del flujo (Controlador). |
| **PHPUnit** | Framework de pruebas unitarias para PHP. Es el estándar de facto para testing automatizado en proyectos Laravel. |
| **Pipeline** | Secuencia automatizada de pasos que se ejecutan en un servidor CI/CD, como instalación de dependencias, compilación y ejecución de tests. |
| **POS** | Point of Sale (Punto de Venta). Módulo del sistema donde se registran las ventas a los clientes. |
| **Prueba de Regresión** | Prueba que verifica que funcionalidades existentes no se han visto afectadas negativamente por cambios recientes en el código. |
| **RACI** | Responsable, Aprobador, Consultado, Informado. Matriz para definir roles y responsabilidades en un proyecto. |
| **RefreshDatabase** | Trait de Laravel que ejecuta las migraciones y limpia la base de datos antes de cada test, garantizando aislamiento completo entre pruebas. |
| **Seeder** | Clase de Laravel que carga datos iniciales en la base de datos, como roles, usuarios de prueba y productos de ejemplo. |
| **Soft Delete** | Eliminación lógica de un registro (se marca como eliminado con la columna `deleted_at`) sin borrarlo físicamente de la base de datos. |
| **SQLite en memoria** | Motor de base de datos ligero que almacena todo en la RAM, ideal para tests por su velocidad y aislamiento. Configurado con `:memory:`. |
| **Suite de Pruebas** | Conjunto organizado de casos de prueba que se ejecutan juntos. En NATURACOR incluye 180 tests en 2 suites: Unit y Feature. |
| **UAT** | User Acceptance Testing. Pruebas de aceptación realizadas por el usuario final para validar que el sistema cumple con sus expectativas. |
| **Unit Test** | Prueba unitaria que verifica el comportamiento de un componente individual (modelo, método, cálculo) de forma aislada, sin dependencias externas. |
| **Xdebug** | Extensión PHP que proporciona capacidades de debugging y generación de reportes de cobertura de código. |
