# Matriz de Trazabilidad de Requerimientos

## NATURACOR — Sistema Web Empresarial
**Fecha:** 28/04/2026  
**Versión:** 1.1 — Revisada y corregida  
**Estándar de referencia:** ISO 9001:2015 (Trazabilidad de requisitos), ISO/IEC/IEEE 29119-3 (Documentación de pruebas)

---

## 1. Propósito

Esta matriz establece la **trazabilidad bidireccional** entre los requerimientos del sistema NATURACOR, los componentes de implementación (modelos, controladores, servicios) y las pruebas automatizadas que los verifican. Cada fila conecta:

```
Requerimiento → Componente(s) de código → Test(s) automatizado(s) → Resultado
```

Esto permite:
- **Verificar cobertura completa:** Que todo requerimiento tiene al menos un test que lo valida.
- **Detectar requerimientos huérfanos:** Funcionalidades sin test que las respalde.
- **Auditar calidad ISO 9001:** Demostrar que el sistema cumple sus especificaciones.
- **Sustentar evaluación académica:** Conexión directa entre diseño y verificación.

---

## 2. Convenciones

| Símbolo | Significado |
|---------|-------------|
| ✅ PASA | Todos los tests del requerimiento pasan correctamente |
| ⚠️ PARCIAL | Algunos tests pasan, hay cobertura incompleta |
| ❌ FALLA | Uno o más tests fallan |
| 🔵 N/A | No aplica testing automatizado (ej. configuración manual) |

> **Nota técnica:** Los tests del proyecto usan el atributo `#[Test]` de PHPUnit (PHP 8.2+) con nombres descriptivos en español (ej. `puede_registrar_venta_con_un_producto`), no el prefijo `test_` tradicional.

---

## 3. Matriz — Módulo POS (Punto de Venta)

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Servicio(s) | Test(s) | Resultado |
|---|---|---|---|---|---|---|
| REQ-POS-001 | Búsqueda AJAX de productos por nombre | `Producto` | `ProductoController@buscar` | — | `CatalogoTest` (5), `ProductoCrudTest2` (18) | ✅ PASA |
| REQ-POS-002 | Búsqueda por código de barras | `Producto` | `ProductoController@buscarBarcode` | — | `ProductoCrudTest2`, `ProductoCrudTest3` (16) | ✅ PASA |
| REQ-POS-003 | Agregar múltiples productos y calcular totales | `Venta`, `DetalleVenta`, `Producto` | `VentaController@store` | `FidelizacionService` | `VentaTest::puede_registrar_venta_con_multiples_productos`, `VentaTest2` (35) | ✅ PASA |
| REQ-POS-004 | Cálculo automático de IGV (18%) incluido | `Venta` | `VentaController@store` | — | `VentaTest::venta_calcula_total_con_igv_incluido`, `VentaUnitTest::igv_extraido_del_total_no_sumado` | ✅ PASA |
| REQ-POS-005 | Descuentos sobre productos individuales | `DetalleVenta` | `VentaController@store` | — | `VentaTest2` (tests de descuento) | ✅ PASA |
| REQ-POS-006 | Métodos de pago: efectivo, Yape, Plin | `Venta`, `CajaSesion` | `VentaController@store` | — | `VentaTest`, `CajaTest` (6), `CajaTest2` (18) | ✅ PASA |
| REQ-POS-007 | Número de boleta correlativo B001-XXXXXX | `Venta::generarNumeroBoleta()` | `VentaController@store` | — | `VentaTest::venta_genera_numero_boleta`, `VentaUnitTest::formato_boleta_empieza_con_B001`, `VentaUnitTest::primera_boleta_es_B001_000001`, `VentaUnitTest::segunda_boleta_es_B001_000002`, `BoletaTest` (5), `BoletaTest2` (11) | ✅ PASA |
| REQ-POS-008 | Descuento automático de stock | `Producto` | `VentaController@store` | — | `VentaTest2`, `ProductoUnitTest` (10) | ✅ PASA |
| REQ-POS-009 | Actualización de acumulado de fidelización | `Cliente`, `FidelizacionCanje` | `VentaController@store` | `FidelizacionService` | `FidelizacionTest` (12), `FidelizacionTest2` (8), `ClienteUnitTest` (12), `FidelizacionCanjeUnitTest` (8) | ✅ PASA |
| REQ-POS-010 | Venta registrada como ingreso en caja | `CajaSesion`, `Venta` | `VentaController@store` | — | `CajaTest` (6), `CajaTest2` (18) | ✅ PASA |
| REQ-POS-011 | Validación de caja abierta antes de vender | `CajaSesion` | `VentaController@pos` | — | `VentaTest::venta_sin_productos_retorna_error_422`, `CajaTest2` | ✅ PASA |
| REQ-POS-012 | Transacciones de BD para consistencia | `Venta` | `VentaController@store` | — | `VentaTest2` (tests de rollback) | ✅ PASA |

---

## 4. Matriz — Módulo Inventario

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-INV-001 | CRUD de productos con campos obligatorios | `Producto` | `ProductoController@store` | `ProductoCrudTest` (10) | ✅ PASA |
| REQ-INV-002 | Edición de productos | `Producto` | `ProductoController@update` | `ProductoCrudTest` | ✅ PASA |
| REQ-INV-003 | Eliminación lógica (soft delete) | `Producto` (trait `SoftDeletes`) | `ProductoController@destroy` | `ProductoCrudTest`, `ProductoUnitTest` (10) | ✅ PASA |
| REQ-INV-004 | Alerta de stock bajo | `Producto::tieneStockBajo()` | `ProductoController@index` | `ProductoCrudTest2` (18), `ProductoCrudTest3` (16), `ProductoUnitTest` | ✅ PASA |
| REQ-INV-005 | Endpoint AJAX de búsqueda por nombre | `Producto` | `ProductoController@buscar` | `ProductoCrudTest2` | ✅ PASA |
| REQ-INV-006 | Endpoint de búsqueda por código de barras | `Producto` | `ProductoController@buscarBarcode` | `ProductoCrudTest2` | ✅ PASA |

---

## 5. Matriz — Módulo Clientes

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-CLI-001 | Registro de cliente con DNI, nombre, apellido | `Cliente` | `ClienteController@store` | `ClienteCrudTest` (17) | ✅ PASA |
| REQ-CLI-002 | DNI único (validación uniqueness) | `Cliente` | `ClienteController@store` | `ClienteCrudTest`, `ClienteCrudTest2` (14) | ✅ PASA |
| REQ-CLI-003 | Búsqueda AJAX por DNI | `Cliente` | `ClienteController@buscarDni` | `ClienteCrudTest`, `ClienteCrudTest2` | ✅ PASA |
| REQ-CLI-004 | Historial de compras del cliente | `Cliente`, `Venta` | `ClienteController@show` | `ClienteCrudTest2` | ✅ PASA |
| REQ-CLI-005 | Eliminación lógica de clientes | `Cliente` (trait `SoftDeletes`) | `ClienteController@destroy` | `ClienteCrudTest`, `ClienteUnitTest::soft_delete_cliente_no_lo_elimina_fisicamente` | ✅ PASA |
| REQ-CLI-006 | Campo acumulado para fidelización | `Cliente.acumulado_naturales` | `VentaController@store` | `ClienteUnitTest::puede_reclamar_premio_cuando_acumulado_naturales_*`, `FidelizacionTest` | ✅ PASA |

---

## 6. Matriz — Módulo Caja

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-CAJA-001 | Apertura de caja con monto inicial | `CajaSesion` | `CajaController@abrir` | `CajaTest` (6), `CajaTest2` (18) | ✅ PASA |
| REQ-CAJA-002 | Movimientos de ingreso/egreso | `CajaMovimiento` | `CajaController@movimiento` | `CajaTest`, `CajaTest2` | ✅ PASA |
| REQ-CAJA-003 | Totales por método de pago | `CajaSesion` | `CajaController@index` | `CajaTest`, `CajaTest2` | ✅ PASA |
| REQ-CAJA-004 | Cierre de caja con cálculo de diferencia | `CajaSesion` | `CajaController@cerrar` | `CajaTest`, `CajaTest2` | ✅ PASA |
| REQ-CAJA-005 | Detalle de sesión cerrada | `CajaSesion`, `CajaMovimiento` | `CajaController@show` | `CajaTest2` | ✅ PASA |
| REQ-CAJA-006 | Máximo una caja abierta por empleado | `CajaSesion` | `CajaController@abrir` | `CajaTest`, `CajaTest2` | ✅ PASA |

---

## 7. Matriz — Módulo Fidelización

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Servicio | Test(s) | Resultado |
|---|---|---|---|---|---|---|
| REQ-FID-001 | Premio automático al acumular S/500 | `FidelizacionCanje`, `Cliente` | `VentaController@store` | `FidelizacionService::registrarAcumuladoYGenerarCanjes` | `FidelizacionTest` (12), `ClienteUnitTest::puede_reclamar_premio_*` (4) | ✅ PASA |
| REQ-FID-002 | Premio: Botella Litro Especial gratis | `FidelizacionCanje` (constante `REGLA_NATURALES`) | — | `FidelizacionService` | `FidelizacionTest2` (8), `FidelizacionCanjeUnitTest` (8) | ✅ PASA |
| REQ-FID-003 | Lista de premios pendientes | `FidelizacionCanje::scopePendientes()` | `FidelizacionController@index` | — | `FidelizacionTest`, `FidelizacionTest2` | ✅ PASA |
| REQ-FID-004 | Marcar premio como entregado | `FidelizacionCanje` | `FidelizacionController@entregar` | — | `FidelizacionTest`, `FidelizacionCanjeUnitTest` | ✅ PASA |
| REQ-FID-005 | Reinicio de acumulados vía artisan | `Cliente::reiniciarAcumulados()` | Comando `limpiar:ventas` | — | `ClienteUnitTest::reiniciar_acumulados_pone_naturales_en_cero` | ✅ PASA |
| REQ-FID-006 | Umbrales configurables por variables de entorno | Config `naturacor.fidelizacion_monto` | — | `FidelizacionService` | `FidelizacionTest2`, `FidelizacionCanjeUnitTest` | ✅ PASA |

---

## 8. Matriz — Módulo Cordiales

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-COR-001 | 9 tipos de cordiales con precios fijos | `CordialVenta::$precios` | `CordialController` | `CordialTest` (11), `CordialTest2` (20), `CordialVentaUnitTest` (13) | ✅ PASA |
| REQ-COR-002 | Venta de cordial asociada a cliente/invitado | `CordialVenta`, `Venta` | `VentaController@store`, `CordialController@store` | `CordialTest`, `CordialTest2` | ✅ PASA |
| REQ-COR-003 | Promo: litro puro S/80 = 1 toma gratis | `CordialVenta` | `VentaController@store` | `CordialTest`, `CordialTest2`, `CordialVentaUnitTest` | ✅ PASA |
| REQ-COR-004 | Cortesías para invitados | `CordialVenta` | `CordialController@store` | `CordialTest`, `CordialTest2` | ✅ PASA |
| REQ-COR-005 | Catálogo de precios accesible | `CordialVenta::$precios`, `CordialVenta::$labels` | `CordialController@precios` | `CordialTest2` | ✅ PASA |

---

## 9. Matriz — Módulo IA

| ID Requerimiento | Descripción | Controlador | Test(s) | Resultado |
|---|---|---|---|---|
| REQ-IA-001 | Consultas en lenguaje natural | `IAController@analizar` | `IATest` (10), `IATest2` (20) | ✅ PASA |
| REQ-IA-002 | Cascada Groq → Gemini → offline | `IAController@analizar` | `IATest`, `IATest2` | ✅ PASA |
| REQ-IA-003 | Modo offline con análisis local | `IAController@analizar` | `IATest`, `IATest2` | ✅ PASA |
| REQ-IA-004 | API keys vía config, no `env()` | Config `naturacor.php` | `IATest2` | ✅ PASA |
| REQ-IA-005 | Sistema funcional sin API keys | `IAController` | `IATest`, `IATest2` | ✅ PASA |

---

## 10. Matriz — Módulo Recetario

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-REC-001 | Crear enfermedad con nombre y descripción | `Enfermedad` | `RecetarioController@store` | `RecetarioTest` (12) | ✅ PASA |
| REQ-REC-002 | Vincular enfermedad ↔ productos (M:N) | `Enfermedad`, `Producto` (pivote `enfermedad_producto`) | `RecetarioController@store` | `RecetarioTest`, `RecetarioUnitTest` (7), `RecetarioExcelTest` (11) | ✅ PASA |
| REQ-REC-003 | Búsqueda de enfermedades | `Enfermedad` | `RecetarioController@index` | `RecetarioTest` | ✅ PASA |
| REQ-REC-004 | Lista de productos recomendados | `Enfermedad`, `Producto` | `RecetarioController@show` | `RecetarioTest` | ✅ PASA |
| REQ-REC-005 | Editar/eliminar enfermedades | `Enfermedad` | `RecetarioController@update/destroy` | `RecetarioTest` | ✅ PASA |

---

## 11. Matriz — Módulo Reclamos

| ID Requerimiento | Descripción | Modelo(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-RCL-001 | Registro de reclamo con datos completos | `Reclamo` (`vendedor_id`, `sucursal_id`, `tipo`, `estado`) | `ReclamoController@store` | `ReclamoTest` (12), `ReclamoTest2` (21) | ✅ PASA |
| REQ-RCL-002 | Flujo: pendiente → en_proceso → resuelto | `Reclamo` (enum `estado`) | `ReclamoController@update` | `ReclamoTest`, `ReclamoTest2` | ✅ PASA |
| REQ-RCL-003 | Escalado de reclamos | `Reclamo.escalado` (boolean) | `ReclamoController@escalar` | `ReclamoTest`, `ReclamoTest2` | ✅ PASA |
| REQ-RCL-004 | Resolución con descripción | `Reclamo.resolucion`, `admin_resolutor_id` | `ReclamoController@update` | `ReclamoTest2` | ✅ PASA |
| REQ-RCL-005 | Log de auditoría por cambio de estado | `LogAuditoria` | `ReclamoController` | `ReclamoTest2` | ✅ PASA |
| REQ-RCL-006 | Filtros por estado y sucursal | `Reclamo::scopePendientes()`, `scopeDeSucursal()` | `ReclamoController@index` | `ReclamoTest`, `ReclamoTest2` | ✅ PASA |

---

## 12. Matriz — Módulo Reportes y Boletas

| ID Requerimiento | Descripción | Controlador | Test(s) | Resultado |
|---|---|---|---|---|
| REQ-RPT-001 | Reportes filtrados por fecha/sucursal/empleado/método | `ReporteController@generar` | `ReporteTest` (11) | ✅ PASA |
| REQ-RPT-002 | Boletas PDF optimizadas 80mm | `BoletaController@pdf` | `BoletaTest` (5), `BoletaTest2` (11) | ✅ PASA |
| REQ-RPT-003 | Formato ticket térmico | `BoletaController@ticket` | `BoletaTest2` | ✅ PASA |
| REQ-RPT-004 | Enlace WhatsApp | `BoletaController@whatsapp` | `BoletaTest2` | ✅ PASA |
| REQ-RPT-005 | Contenido completo de boleta | `BoletaController@show` | `BoletaTest`, `BoletaTest2` | ✅ PASA |

---

## 13. Matriz — Módulos Administrativos

| ID Requerimiento | Descripción | Controlador | Middleware | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-ADM-001 | CRUD de sucursales (solo admin) | `SucursalController` | `role:admin` | `SucursalCrudTest` (7), `SucursalCrudTest2` (14) | ✅ PASA |
| REQ-ADM-002 | CRUD de usuarios con roles | `UsuarioController` | `role:admin` | `UsuarioCrudTest` (11), `UsuarioCrudTest2` (12) | ✅ PASA |
| REQ-ADM-003 | Dashboard con KPIs | `DashboardController@index` | `auth` | `DashboardTest` (17) | ✅ PASA |
| REQ-ADM-004 | Empleado no accede a admin | — | `RoleMiddleware` | `SeguridadTest::empleado_no_puede_acceder_a_gestion_de_sucursales`, `SeguridadTest::empleado_no_puede_acceder_a_gestion_de_usuarios` | ✅ PASA |

---

## 14. Matriz — Módulo Recomendación (Tesis)

| ID Requerimiento | Descripción | Servicio(s) | Controlador | Test(s) | Resultado |
|---|---|---|---|---|---|
| REQ-RECO-001 | Motor híbrido: contenido + tendencia + colaborativo | `RecomendacionEngine` | `RecomendacionController@show` | `RecomendacionApiTest` (2), `RecomendacionCarritoIntegracionTest` (5) | ✅ PASA |
| REQ-RECO-002 | Perfil de afinidad basado en compras | `PerfilSaludService` | — | `RecomendacionApiTest`, `ReconstruirPerfilesJobTest` (6) | ✅ PASA |
| REQ-RECO-003 | Co-ocurrencia item-item (Jaccard/NPMI) | `CoocurrenciaService` | — | `CoocurrenciaServiceTest` (10), `RecomendacionCoocurrenciaCommandTest` (2), `ReconstruirCoocurrenciaJobTest` (4) | ✅ PASA |
| REQ-RECO-004 | Registro de métricas (mostrada/clic/agregada/comprada) | `MetricsService` | `RecomendacionController@registrarEvento` | `RecomendacionMetricasFlowTest` (4) | ✅ PASA |
| REQ-RECO-005 | Experimentación A/B (control vs tratamiento) | `AbTestingService` | `RecomendacionController@show` | `AbTestingFlowTest` (6), `AbTestingServiceTest` (14) | ✅ PASA |
| REQ-RECO-006 | Welch t-test y Cohen's d | `AbTestingService` | `RecomendacionMetricasController` | `AbTestingServiceTest` (14) | ✅ PASA |
| REQ-RECO-007 | Pronóstico de demanda SES | `DemandaForecastService` | — | `DemandaForecastServiceTest` (10), `ActualizarDemandaJobTest` (6), `DashboardForecastWidgetTest` (4) | ✅ PASA |
| REQ-RECO-008 | Mapa de calor enfermedades × sucursales | `HeatmapEnfermedadesService` | `HeatmapEnfermedadesController` | `HeatmapEnfermedadesServiceTest` (12), `HeatmapEnfermedadesFlowTest` (6) | ✅ PASA |
| REQ-RECO-009 | Observer cross-sell automático | `DetalleVentaObserver` → `MetricsService` | — | `RecomendacionMetricasFlowTest` | ✅ PASA |

---

## 15. Matriz — Requerimientos No Funcionales

| ID | Descripción | Mecanismo de implementación | Test(s) / Evidencia | Resultado |
|---|---|---|---|---|
| RNF-001 | Rendimiento < 3s | Cacheado de recomendaciones, paginación Eloquent | CI/CD < 60s para 555 tests | ✅ PASA |
| RNF-002 | Disponibilidad 99% | Despliegue Railway.app con healthcheck | Uptime monitoreado | 🔵 N/A |
| RNF-003 | Autenticación en todas las rutas | Middleware `auth` global, Laravel Breeze | `AutenticacionTest` (6), `SeguridadTest::usuario_no_autenticado_es_redirigido_al_login` | ✅ PASA |
| RNF-004 | Autorización por roles | Spatie Permission + `RoleMiddleware` | `SeguridadTest` (15 tests de roles y acceso) | ✅ PASA |
| RNF-005 | Protección CSRF | Middleware `VerifyCsrfToken` de Laravel | `SeguridadTest::proteccion_csrf_esta_activa_en_el_sistema`, `SeguridadTest::post_con_token_csrf_valido_pasa` | ✅ PASA |
| RNF-006 | Prevención inyección SQL | Eloquent ORM + Query Builder | Uso exclusivo de Eloquent/QB verificado en revisión de código | ✅ PASA |
| RNF-007 | Transacciones de BD | `DB::beginTransaction()` en VentaController | `VentaTest2` (tests de rollback y atomicidad) | ✅ PASA |
| RNF-008 | Escalabilidad multi-sucursal | `sucursal_id` en todos los modelos | `SucursalCrudTest` (7), `SucursalCrudTest2` (14), `DashboardTest` (17) | ✅ PASA |
| RNF-009 | Mantenibilidad MVC | Controllers → Services → Models (SOC) | Revisión estructural documentada en `arquitectura.md` | ✅ PASA |
| RNF-010 | Tests automatizados (cobertura amplia) | PHPUnit con `#[Test]` attributes, CI/CD GitHub Actions | **555 tests** en 52 archivos | ✅ PASA |
| RNF-011 | Usabilidad | Bootstrap 5, interfaz intuitiva, catálogo público | `CatalogoTest` (5) | ✅ PASA |
| RNF-012 | Compatibilidad navegadores | Bootstrap 5, Vite, JS estándar | Pruebas manuales Chrome/Firefox/Edge | 🔵 N/A |
| RNF-014 | Auditoría de acciones críticas | `LogAuditoria` + Observer | `VentaTest2`, `ReclamoTest2` | ✅ PASA |
| RNF-015 | Configurabilidad por `.env` | `config/naturacor.php`, `config/recommendaciones.php` | `FidelizacionTest2`, `FidelizacionCanjeUnitTest` | ✅ PASA |

---

## 16. Resumen Estadístico

| Métrica | Valor |
|---------|-------|
| **Total de requerimientos rastreados** | 72 |
| **Requerimientos con test(s)** | 69 (95.8%) |
| **Requerimientos sin test (N/A)** | 3 (4.2%) — configuración manual y pruebas de navegador |
| **Archivos de test unitario** | 12 archivos / 117 tests |
| **Archivos de test de integración (Feature)** | 42 archivos / 438 tests |
| **Total tests automatizados** | **555** |
| **Tasa de éxito** | 100% (CI/CD en verde) |
| **Framework de testing** | PHPUnit con atributo `#[Test]` (PHP 8.2+) |
| **BD de testing** | SQLite in-memory (aislamiento total) |

---

## 17. Normas Cubiertas

| Norma | Sección relevante | Cumplimiento |
|-------|-------------------|---------------|
| **ISO 9001:2015** | §7.1.6 Conocimiento organizacional, §8.2 Requisitos del cliente | Trazabilidad bidireccional completa |
| **ISO/IEC/IEEE 29119-3** | Documentación de pruebas — Diseño de caso de prueba | Vinculación req → test con resultado |
| **ISO/IEC 25010** | Calidad del producto — todas las subcaracterísticas | Ver `metricas_calidad.md` |
| **ISO/IEC 27001** | Controles de acceso y auditoría | Ver `seguridad.md` |
