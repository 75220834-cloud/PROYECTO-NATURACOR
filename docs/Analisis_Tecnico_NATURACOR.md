# Análisis Técnico NATURACOR — Estado Actual y Roadmap

> Documento generado el 25-04-2026 tras lectura íntegra del código fuente.
> Stack verificado: **Laravel 12.55.1 · PHP 8.2 · MySQL · Bootstrap 5.3 · Chart.js 4.4.3 · Spatie Permissions**.
> Autor: continuación del trabajo iniciado en sesiones previas.

---

## 1. Resumen Ejecutivo

NATURACOR ya tiene una base sólida y madura para ser una tesis de Pruebas y Calidad de Software:

- Motor de recomendación híbrido **Fase 1** funcionando (`RecomendacionEngine`).
- Servicio de perfilado con decaimiento exponencial y normalización min-max (`PerfilSaludService`).
- Servicio de métricas con `precision@k`, atribución de compra, ticket promedio y serie temporal (`MetricsService`).
- Observer no intrusivo para registrar `comprada` (`DetalleVentaObserver`).
- Dashboard de métricas y widget IA en el dashboard general.
- POS con autocompletado de cliente, panel inline de padecimientos, badges explicables y tracking de eventos.
- Tests automatizados que cubren flujo end-to-end del recomendador (`RecomendacionApiTest`, `RecomendacionMetricasFlowTest`).

**Veredicto general**: lo que ya está implementado tiene calidad publicable; lo que falta es **profundidad científica del híbrido** (co-ocurrencia real), **predicción de demanda** y **evidencia A/B**.

---

## 2. Verificación de los BUGS Reportados

### BUG 1 — Admin sin sucursal ve "vista global" — **CONFIRMADO**

**Archivo**: `app/Http/Controllers/RecomendacionController.php`, línea **53**.

```53:53:app/Http/Controllers/RecomendacionController.php
        $sucursalId = $request->user()->sucursal_id;
```

**Diagnóstico**: el motor ya soporta `sucursalId === null` (lo trata como "vista global", revisar `RecomendacionEngine::unidadesVendidasRecientesPorSucursal` y `textoTendenciaSucursal`), por eso no rompe; pero el mensaje "vista global: tu usuario no tiene sucursal asignada" se enseña al admin como si fuera un fallo de configuración.

**Fix recomendado** (aplicar tal cual lo propusiste):

```php
$sucursalId = $request->user()->sucursal_id ?? 1;
```

**⚠️ Mejora adicional sugerida**: en lugar de hardcodear `1`, leer de `config('naturacor.sucursal_default', 1)` para que sea configurable por entorno. Esto evita romper despliegues donde la sucursal con id=1 no exista (ej. clínica multi-tenant futura).

---

### BUG 2 — Motor no lee `cliente_padecimientos` directamente — **CONFIRMADO**

**Evidencia**:

- `PerfilSaludService::reconstruirPerfil()` (línea 76 en adelante) **solo** lee de `detalle_ventas` + `enfermedad_producto`.
- `RecomendacionEngine::recomendar()` (línea 57) carga `ClientePerfilAfinidad` que es el resultado de ese perfil.
- Si el cliente nunca compró pero ya registró sus padecimientos en el panel inline del POS (`ClienteController::guardarPadecimientos`), su perfil queda **vacío** y el motor solo le devuelve trending genérico.

**Impacto científico**: rompe el caso más interesante para tu tesis (cliente nuevo con diagnóstico previo, que es exactamente el flujo que tu sistema diferencia respecto a Amazon o Mercadolibre).

**Fix detallado** (en `PerfilSaludService::reconstruirPerfil`):

1. Después de calcular `$acumulado` desde `detalle_ventas`, **inyectar** padecimientos directos.
2. Score base sugerido: `0.80` (configurable vía `config('recommendaciones.padecimiento_score_base', 0.80)`).
3. Marcar la fila con `evidencia_count = 0` y `ultima_evidencia_at = now()` para distinguir "señal declarada" de "señal observada".

Esquema del cambio (pseudocódigo):

```php
$padecimientosIds = DB::table('cliente_padecimientos')
    ->where('cliente_id', $clienteId)
    ->pluck('enfermedad_id');

foreach ($padecimientosIds as $eid) {
    $acumulado[$eid] ??= ['raw' => 0.0, 'evidencias' => 0, 'ultima' => $now];
    $acumulado[$eid]['raw'] = max(
        $acumulado[$eid]['raw'],
        config('recommendaciones.padecimiento_score_base', 0.80)
    );
}
```

**Importante**: hacerlo **después** del cálculo desde ventas pero **antes** de la normalización min-max (líneas 162-164), para que la inyección participe en el cálculo del nuevo `maxRaw`.

---

### BUG ADICIONAL #3 (no reportado pero detectado) — Conflicto de nombres de ruta

**Archivo**: `routes/web.php`, líneas **47-48** y **52-53**.

```47:53:routes/web.php
Route::get('/api/clientes/{cliente}/padecimientos', [ClienteController::class, 'padecimientos'])->name('clientes.padecimientos');
Route::post('/api/clientes/{cliente}/padecimientos', [ClienteController::class, 'guardarPadecimientos'])->name('clientes.padecimientos.guardar');


    // Padecimientos
    Route::get('/clientes/{cliente}/padecimientos', [ClienteController::class, 'padecimientos'])->name('clientes.padecimientos');
    Route::post('/clientes/{cliente}/padecimientos', [ClienteController::class, 'guardarPadecimientos'])->name('clientes.padecimientos.store');
```

Hay **dos** rutas con el mismo nombre `clientes.padecimientos`. Laravel acepta esto sin error pero `route('clientes.padecimientos', $c)` siempre resolverá a la **última** registrada (`/clientes/{cliente}/padecimientos`, sin prefijo `/api`). Tu JS del POS ya usa la URL hardcodeada `/api/clientes/${clienteId}/padecimientos`, así que en runtime no falla, pero es **deuda técnica peligrosa**: cualquier `route()` futuro con ese nombre romperá el contrato.

**Fix sugerido**: renombrar la web a `clientes.padecimientos.web` o eliminar el bloque duplicado si la versión web no se usa.

---

### BUG ADICIONAL #4 — Caché del JSON no se invalida tras guardar padecimientos

**Archivo**: `app/Http/Controllers/ClienteController.php` líneas 122-154 (`guardarPadecimientos`).

Cuando guardas padecimientos nuevos, el caché de recomendaciones (`recommendaciones.json.v1.{clienteId}.{sucursal}.{limite}`) sigue vigente hasta `REC_CACHE_MINUTOS` (10 min por defecto). El cliente no verá productos sugeridos por sus padecimientos hasta que expire o que el JS pida `?refresh=1`.

El POS sí lo hace correctamente (`cargarRecomendacionesPos(clienteId, true)` después de `guardarPadecimientos`), por lo cual el flujo UI funciona, pero si alguien edita los padecimientos vía API directa (admin, integración futura), no se invalida.

**Fix sugerido**: tras guardar padecimientos, llamar:

```php
Cache::forget("recommendaciones.json.v1.{$cliente->id}.*"); // requiere driver con tags o hacer purge manual
$this->perfilSalud->reconstruirPerfil($cliente->id);
```

---

## 3. Análisis del Código Existente — Qué está BIEN

### 3.1. Arquitectura de servicios (excelente)

- Separación correcta `Controllers / Services / Models / Observers`.
- Inyección por constructor con `private readonly` (PHP 8 nivel senior).
- `RecomendacionEngine` recibe `PerfilSaludService` por DI, lo cual es **mockeable** y testeable.
- `MetricsService` no toca controladores de venta: el observer hace el bridge. **Patrón limpio para tesis**.

### 3.2. Modelo matemático del recomendador (sólido)

- **Decaimiento temporal**: `peso = cantidad * exp(-lambda * dias)` — clásico en sistemas content-based, citable en literatura.
- **Compensación por grado de producto**: `contribucion = peso / grado(producto)` — evita que productos muy genéricos dominen el perfil. **Esto es citable** (similar a TF-IDF inverso).
- **Normalización min-max por cliente**: correcta para hacer comparables los scores entre clientes distintos.
- **Fusión lineal `peso_perfil * comp_perfil + peso_trending * comp_trend`**: simple pero válida y configurable por `.env`.
- **Diversidad por enfermedad** (`seleccionarDiverso`): evita resultados redundantes (todos del mismo grupo). **Esto es originalidad publicable**.

### 3.3. Sistema de métricas (publicable)

- Eventos append-only: arquitectura correcta para experimentos científicos (no se pierde historia).
- `precision@k` operativo (no solo teórico).
- Atribución por ventana de lookback (72h por defecto): justificable y configurable.
- Comparación de ticket promedio con/sin recomendación: **base perfecta para un A/B**.

### 3.4. Caché y rendimiento

- Caché por `cliente / sucursal / límite` con TTL configurable.
- Marcador de "perfil vacío comprobado" para evitar trabajo redundante.
- Inserción en bloque (`DB::table->insert(chunk)`) para `mostrada` y `historial_perfil`.

### 3.5. Transacción correcta en POS

- `VentaController::store` envuelve todo en `DB::beginTransaction()` con rollback en error.
- `Producto::lockForUpdate()` para evitar race conditions de stock. **Buen patrón concurrente.**

---

## 4. Análisis del Código Existente — Qué se puede MEJORAR

### 4.1. Híbrido "incompleto" (lo que detectaste)

El motor combina **content-based** (perfil ↔ enfermedad) + **trending por sucursal** (popularidad). Falta el componente colaborativo: **co-ocurrencia producto↔producto** (lo que llamas `CoocurrenciaService`). Sin esto, "híbrido" en el título de la tesis es vendido pero parcialmente cubierto. Resolverlo te da **pieza original publicable**.

### 4.2. Sin job scheduler activo

`bootstrap/app.php` no registra schedule, y `routes/console.php` solo tiene un comando `limpiar:ventas`. Eso significa que **hoy** el perfil se recalcula de forma sincrónica en el primer request del día (latencia perceptible). Mover a job nocturno es correcto y mejora UX.

### 4.3. `DetalleVentaObserver` puede ralentizar el `store()` de venta

Cada `DetalleVenta::create` dentro del `foreach` de `VentaController::store` dispara `MetricsService::registrarCompradaSiCorresponde`, que ejecuta 2-3 queries adicionales (lookback, sesión válida). En una venta de 20 productos = ~60 queries extra dentro de la transacción.

**Optimización sugerida**: bachear vía `dispatchAfterResponse()` o un Job en cola, no bloquear el commit.

### 4.4. `precision@k` puede ser engañoso

Hoy `calcularPrecisionAtK` cuenta como "hit" si **al menos uno** de los top-k se compró. Esto es **Hit Rate@k**, no `precision@k` clásico (que sería `compras_top_k / k`). Dos opciones:

- (a) Renombrar la métrica a `hit_rate@k` (honestidad académica) — recomendado para la tesis.
- (b) Calcular ambas y mostrarlas. Más completo, más publicable.

### 4.5. Falta tracking del A/B

El servicio no distingue entre "cliente que vio recos" y "cliente que NO vio recos por config". Hoy todo cliente con padecimientos ve recos. Para evidencia científica del impacto necesitas **grupo control** medible.

### 4.6. Falta protección anti-tampering en `registrarEvento`

Cualquier usuario autenticado puede emitir eventos `agregada`/`clic` con el `reco_sesion_id` de otra sucursal/usuario. El servicio valida que exista una `mostrada` previa, pero **no valida** que esa `mostrada` haya sido emitida para el mismo `user_id` o `sucursal_id`. En auditoría científica esto puede ensuciar las métricas.

**Fix sugerido**: agregar `where('sucursal_id', $sucursalId)` y/o `where('user_id', $userId)` al lookup de la fila `mostrada` en `MetricsService::registrarInteraccionPos`.

### 4.7. Variables sin tipado fuerte

Algunas funciones devuelven `array` genérico cuando podrían ser DTOs (ej. `serializarProducto`). Para tesis no es crítico, pero si quieres puntuación máxima en revisión de código, considera `Spatie\LaravelData` o crear `RecomendacionItemDTO`.

### 4.8. Sin tests para los flujos negativos críticos

Ya tienes 184 tests pero faltan:

- ¿Qué pasa si `Producto` está inactivo y aparece en perfil? (debería filtrarse — verificable)
- ¿Qué pasa si `enfermedad.activa = false` después de calcular? (¿se queda en `cliente_perfil_afinidad`?)
- ¿El observer se dispara correctamente si la venta se crea en un Job en cola?

---

## 5. Viabilidad de los Pendientes — Análisis y Plan

### 5.1. CoocurrenciaService — **CRÍTICO Y VIABLE**

**Objetivo**: producto P y producto Q se compran juntos N veces → recomendar Q a quien lleva P.

**Métricas posibles**:

- **Jaccard**: `J(P,Q) = |ventas(P) ∩ ventas(Q)| / |ventas(P) ∪ ventas(Q)|`. Simple, simétrico, [0,1].
- **PMI (Pointwise Mutual Information)**: `log(P(P,Q) / (P(P) * P(Q)))`. Más informativo, asimétrico, requiere normalización para mostrar.
- **Lift**: `P(Q|P) / P(Q)`. Más usado en retail (Apriori, asociación).

**Recomendación para tesis**: implementar **Jaccard** como base (citable, simple) y opcionalmente Lift (vendible al negocio: "los que compraron X también llevan Y").

**Diseño propuesto**:

```text
Tabla nueva: producto_coocurrencia
  - producto_a_id (FK productos)
  - producto_b_id (FK productos, b > a para evitar duplicados)
  - n_ab  INT      (número de ventas que tienen ambos)
  - n_a   INT      (ventas que tienen A)
  - n_b   INT      (ventas que tienen B)
  - jaccard DECIMAL(8,6) GENERATED   (n_ab / (n_a + n_b - n_ab))
  - sucursal_id (nullable, para coocurrencia por sucursal)
  - computed_at TIMESTAMP
  - UNIQUE(producto_a_id, producto_b_id, sucursal_id)
```

**Servicio propuesto**:

```php
namespace App\Services\Recommendation;

class CoocurrenciaService
{
    public function reconstruirGlobal(?int $sucursalId = null): int { /* … */ }
    public function topRelacionados(int $productoId, ?int $sucursalId, int $k = 5): Collection { /* … */ }
}
```

**Integración en el motor**: añadir un tercer componente `componente_coocurrencia` con peso configurable `REC_PESO_COOC=0.30`. Para cada producto candidato, sumar el `jaccard` con los productos comprados recientemente por el cliente.

**Costo computacional**: O(n²) por sucursal. Para 200 productos = 40 000 pares. Para 1000 productos = 1 000 000 pares. **Solo factible como job nocturno**, no en línea.

**Viabilidad**: ✅ **100% viable**, alto impacto científico.

---

### 5.2. DemandaForecastService + widget — **VIABLE PERO TIENE CAVEATS**

**Objetivo**: predecir cuántas unidades se venderán de cada producto la próxima semana.

**Modelo propuesto (Suavizado Exponencial Simple — SES)**:

```text
S_t = α * Y_t + (1 - α) * S_{t-1}
```

donde:

- `Y_t` = unidades vendidas en la semana `t`.
- `S_t` = predicción suavizada para la semana `t+1`.
- `α ∈ (0,1)` típicamente 0.3-0.5.

**Tablas propuestas**:

```text
producto_demanda_semana (histórico):
  - producto_id, sucursal_id, anio, semana_iso, unidades_vendidas
  - UNIQUE(producto_id, sucursal_id, anio, semana_iso)

producto_prediccion_demanda (resultado del modelo):
  - producto_id, sucursal_id, semana_objetivo (date)
  - prediccion, intervalo_inf, intervalo_sup
  - alpha_usado, modelo (ej. 'SES')
  - computed_at
```

**Caveats académicos**:

- **SES no captura estacionalidad**. Si el negocio vende más en Navidad, el modelo subestima. Para una tesis honesta, **mencionar esta limitación** y proponer Holt-Winters como mejora futura.
- Necesitas **al menos 8-12 semanas** de historia para que el modelo no diga ruido. Verifica que tengas ese histórico en MySQL antes de implementar.
- La **error metric** (MAE, MAPE) es obligatoria para defender el modelo en tesis.

**Widget en dashboard**: tabla con top-10 productos próximos a quedarse sin stock comparando `prediccion_demanda` vs `stock_actual`. Esto es **MUY vendible** al negocio.

**Viabilidad**: ✅ **Viable**, mediano impacto científico, **alto impacto operativo**.

---

### 5.3. Jobs & Schedule nocturno — **TRIVIAL Y VIABLE**

Hoy NO hay schedule activo. La estructura debe ser:

```php
// app/Console/Kernel.php  (Laravel 11+: protected function schedule(Schedule $schedule))
$schedule->job(new ReconstruirPerfilesJob)->dailyAt('02:00');
$schedule->job(new ReconstruirCoocurrenciaJob)->dailyAt('02:30');
$schedule->job(new ActualizarDemandaJob)->weeklyOn(1, '03:00');
```

⚠️ **Importante en Laravel 12 + bootstrap/app.php style** que tú usas: el schedule va en `routes/console.php`, no en Kernel. Yo te lo dejaría así:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\Recommendation\ReconstruirPerfilesJob)->dailyAt('02:00');
Schedule::job(new \App\Jobs\Recommendation\ReconstruirCoocurrenciaJob)->dailyAt('02:30');
Schedule::job(new \App\Jobs\Recommendation\ActualizarDemandaJob)->weeklyOn(1, '03:00');
```

**Pendiente de Windows en producción**: tu mamá probablemente correrá esto en un VPS Linux (cPanel, DigitalOcean, etc.), no en su PC. Necesitarás **cron** real ejecutando `php artisan schedule:run` cada minuto. Para Windows local (desarrollo) puedes usar `php artisan schedule:work`.

**Viabilidad**: ✅ Trivial, 1-2 horas de trabajo bien hecho.

---

### 5.4. Experimento A/B documentado — **CRÍTICO PARA TESIS, VIABLE**

**Diseño propuesto**:

```php
// config/recommendaciones.php
'rec_modo_ab' => env('REC_MODO_AB', false),
'rec_ab_estrategia' => env('REC_AB_ESTRATEGIA', 'dia_par_impar'), // 'cliente_id_par', 'aleatorio'
```

**Lógica en `RecomendacionController::show`**:

```php
if (config('recommendaciones.rec_modo_ab') && $this->grupoControl($cliente)) {
    return response()->json([
        'cliente_id' => $cliente->id,
        'items' => [],
        'meta' => [
            'respuesta_desde_cache' => false,
            'perfil_recalculado' => false,
            'reco_sesion_id' => null,
            'grupo_ab' => 'control',
        ],
    ]);
}
```

**Crítica metodológica honesta**:

- **Día par/impar es un A/B débil** porque introduce sesgo temporal (lunes vs martes pueden tener venta diferente). La literatura recomienda **aleatorización por cliente** (split por hash de `cliente_id`).
- **Para tu tesis** te conviene proponer ambos y discutir trade-offs (rigor vs simplicidad de despliegue).
- Necesitarás registrar `grupo_ab` en `recomendacion_eventos` (nueva columna) o en `ventas` (más limpio).

**Análisis estadístico** (lo que va al paper):

- Test de hipótesis sobre ticket promedio: `t-test` Welch o Mann-Whitney U si no hay normalidad.
- Tamaño de muestra mínimo: regla práctica 100 ventas por grupo para detectar diferencias del 10%.
- Reportar **p-valor** y **tamaño de efecto** (Cohen's d).

**Viabilidad**: ✅ Viable, **imprescindible para Scopus**.

---

### 5.5. Mapa de calor de enfermedades — **VIABLE Y PUBLICABLE**

**Concepto**: una matriz visual donde:

- Filas = enfermedades del recetario.
- Columnas = sucursales (o meses).
- Color = cuántos clientes frecuentes tienen esa enfermedad / cuántas ventas asociadas.

**Implementación propuesta**:

- Vista nueva: `resources/views/metricas-recomendacion/heatmap.blade.php`.
- Datos: query agregando `cliente_padecimientos` + `cliente_perfil_afinidad` por enfermedad y sucursal.
- Renderizado: **Chart.js Matrix Plugin** (gratis, MIT) o **D3.js** para mayor rigor visual.
- Bonus: añadir **clustering jerárquico** sobre las enfermedades para reordenar filas y revelar grupos. Esto sí es publicable directo.

**Caso de uso para tu mamá** (vendible localmente): "En Jauja, las enfermedades digestivas concentran el 40% de los clientes frecuentes; conviene reforzar inventario de productos digestivos en sucursal X".

**Viabilidad**: ✅ Viable, **alto valor para tesis y para el negocio**.

---

## 6. Roadmap de Implementación Sugerido (orden de prioridad)

| # | Tarea | Tiempo estimado | Riesgo | Valor tesis | Valor negocio |
|---|---|---|---|---|---|
| 1 | Fix BUG 1 (sucursal admin fallback) | 5 min | bajo | bajo | medio |
| 2 | Fix BUG 2 (inyectar padecimientos en perfil) | 30 min | bajo | **alto** | **alto** |
| 3 | Renombrar route name duplicado | 2 min | bajo | bajo | bajo |
| 4 | Invalidar caché tras `guardarPadecimientos` | 10 min | bajo | medio | medio |
| 5 | `CoocurrenciaService` (Jaccard + tabla) | 4-6h | medio | **alto** | **alto** |
| 6 | Schedule + 3 Jobs nocturnos | 2h | bajo | medio | medio |
| 7 | A/B testing config + columna `grupo_ab` | 3-4h | medio | **CRÍTICO** | medio |
| 8 | Renombrar `precision@k` → `hit_rate@k` o calcular ambas | 1h | bajo | **alto** | bajo |
| 9 | `DemandaForecastService` (SES) + tablas + widget | 6-8h | medio | medio | **alto** |
| 10 | Mapa de calor de enfermedades | 4-5h | bajo | **alto** | medio |
| 11 | Tests de los flujos nuevos | 4h | bajo | **alto** | bajo |
| 12 | Mover `DetalleVentaObserver` a Job en cola | 2h | medio | bajo | medio |

**Total optimista**: 30-35 horas de trabajo limpio = ~1 semana intensa.

---

## 7. Recomendaciones Específicas para tu Tesis y Scopus

### 7.1. Título sugerido

> *"Sistema híbrido de recomendación de productos naturales con perfil de salud declarado y observado: caso de estudio en una farmacia naturista de los Andes peruanos"*

Justificación: el ángulo **"perfil declarado + observado"** es novedoso. La mayoría de papers solo usan compras pasadas (observado). Tu sistema combina padecimientos auto-reportados (declarado) + compras (observado), lo cual es **diferenciador**.

### 7.2. Estructura del paper Scopus

1. **Introducción**: contexto retail naturista en Perú; gap de literatura sobre SR híbridos en tiendas físicas pequeñas.
2. **Trabajos relacionados**: comparar con Amazon (CF puro), Mercadolibre (CB+CF), recomendadores médicos (basados en diagnóstico).
3. **Sistema propuesto**: arquitectura modular (PerfilService + CoocurrenciaService + Engine), mostrar diagrama UML.
4. **Modelo matemático**: ecuaciones de decaimiento, normalización, fusión, Jaccard.
5. **Implementación**: Laravel 12 (mencionar como aporte open-source), tests automatizados como evidencia de calidad.
6. **Evaluación**: A/B real, métricas (`hit_rate@k`, `precision@k`, ticket promedio, tasa conversión).
7. **Resultados**: tabla con grupos control vs tratamiento, p-valores.
8. **Discusión**: limitaciones (estacionalidad, tamaño muestra, generalización).
9. **Conclusiones y trabajo futuro**: ML real (matrix factorization), Holt-Winters, recomendación contextual (clima, etc.).

### 7.3. Para potencial de patente

Lo más patentable es la **combinación específica**:

- **Inyección de padecimiento declarado con score base 0.80** + **decaimiento exponencial sobre compras** + **fusión con co-ocurrencia local por sucursal** + **registro auditable append-only de eventos para evaluación científica**.

Aisladamente cada pieza está en literatura, pero la combinación específica con **explicabilidad por badges** (🩺/📈) puede ser registrable como invención de software en INDECOPI (Perú) o como derecho de autor de programa de cómputo, que es lo más práctico.

### 7.4. Para nota máxima en Pruebas y Calidad de Software

La materia se llama **Pruebas y Calidad**, así que tu evaluador buscará:

- ✅ Tests automatizados (ya tienes 184 + flujo CI). 
- ✅ Cobertura mínima 70% en módulos críticos. **Verifica con `php artisan test --coverage`** y reporta el porcentaje.
- ⚠️ Falta **prueba de carga**: usa `wrk` o `k6` contra `/api/recomendaciones/*` y reporta latencia p50/p95.
- ⚠️ Falta **prueba de seguridad**: muestra que el endpoint requiere auth, que un empleado de sucursal A no puede ver métricas de sucursal B (esto último requiere fortalecer controlador).
- ✅ Documentación técnica (este documento + README + `Plan_de_Pruebas_NATURACOR.md` que ya tienes).

---

## 8. Reglas de Trabajo Acordadas

Mantengo estrictamente estas reglas tuyas:

1. **Antes de tocar cualquier archivo**, te lo pido con `type ruta\archivo.php` y verifico estructura real.
2. **No adivinar** la estructura de tablas o métodos: siempre `php artisan tinker` o lectura del modelo.
3. **Un paso a la vez**: implemento, verifico que tests pasen, te muestro el diff, y luego sigo.
4. **Windows-friendly**: `type` no `cat`; `\` no `/`; `findstr` no `grep`.
5. **Migraciones**: `php artisan migrate` después de crear cada tabla nueva, y siempre con `--pretend` primero para revisar SQL.
6. **Caché**: `php artisan view:clear && php artisan cache:clear && php artisan config:clear` después de tocar `config/`.
7. **Tests**: `php artisan test --filter=NombreTest` para iterar rápido.

---

## 9. Próximo Paso Sugerido

Si estás de acuerdo con este análisis, te propongo el siguiente orden de ejecución:

**Bloque 1 — Hotfixes (mismo día, ~1h)**
- BUG 1, BUG 2, conflicto de ruta, invalidación de caché.
- Tests específicos para cada fix.

**Bloque 2 — CoocurrenciaService (1-2 días)**
- Migración `producto_coocurrencia`.
- Servicio con Jaccard.
- Integración en `RecomendacionEngine` con peso configurable.
- Tests unitarios y de integración.

**Bloque 3 — Schedule + Jobs (medio día)**
- 3 jobs encolables.
- Schedule en `routes/console.php`.
- Documentación de cron en `guia_despliegue_produccion.md`.

**Bloque 4 — A/B Testing (1 día)**
- Columna `grupo_ab` en `recomendacion_eventos`.
- Lógica de control en controller.
- Vista comparativa en dashboard de métricas.

**Bloque 5 — Forecast + Heatmap + Tests adicionales (2-3 días)**

---

**Fin del documento.**

Confirma con cuál bloque arranco y te pido los archivos exactos que necesite editar antes de tocar nada.
