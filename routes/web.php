<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BoletaController;
use App\Http\Controllers\RecetarioController;
use App\Http\Controllers\IAController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\CordialController;
use App\Http\Controllers\FidelizacionController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\RecomendacionController;
use App\Http\Controllers\RecomendacionMetricasController;
use App\Http\Controllers\HeatmapEnfermedadesController;

// Redirigir raíz al catálogo público
Route::get('/', fn() => redirect('/catalogo'));

// Catálogo público (sin autenticación)
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo');

// Rutas autenticadas
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS (Punto de Venta)
    Route::get('/ventas/pos', [VentaController::class, 'pos'])->name('ventas.pos');
    Route::resource('ventas', VentaController::class)->except(['create', 'edit']);

    // Productos — rutas específicas ANTES del resource para evitar conflicto con {producto}
    Route::get('/productos/exportar', [ProductoController::class, 'exportar'])->name('productos.exportar');
    Route::get('/productos/plantilla', [ProductoController::class, 'plantilla'])->name('productos.plantilla');
    Route::post('/productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
    Route::resource('productos', ProductoController::class);
    Route::get('/api/productos/buscar', [ProductoController::class, 'buscar'])->name('productos.buscar');
    Route::get('/api/productos/barcode', [ProductoController::class, 'buscarBarcode'])->name('productos.barcode');

    // Clientes
    Route::resource('clientes', ClienteController::class);
    Route::get('/api/clientes/dni', [ClienteController::class, 'buscarDni'])->name('clientes.dni');
    Route::get('/api/clientes/autocompletar', [ClienteController::class, 'autocompletar'])->name('clientes.autocompletar');

    // Padecimientos del cliente (API JSON — único endpoint, consumido desde el POS)
    // BUG 3 FIX: se eliminó el duplicado /clientes/{cliente}/padecimientos que reusaba
    // el mismo nombre y rompía route('clientes.padecimientos').
    Route::get('/api/clientes/{cliente}/padecimientos', [ClienteController::class, 'padecimientos'])
        ->name('clientes.padecimientos');
    Route::post('/api/clientes/{cliente}/padecimientos', [ClienteController::class, 'guardarPadecimientos'])
        ->name('clientes.padecimientos.guardar');

    // Recomendaciones (módulo inteligente Fase 1 — no modifica el POS; consumo opcional vía API)
    Route::get('/api/recomendaciones/{cliente}', [RecomendacionController::class, 'show'])->name('api.recomendaciones.show');
    Route::post('/api/recomendaciones/evento', [RecomendacionController::class, 'registrarEvento'])->name('api.recomendaciones.evento');

    // Métricas del recomendador (evaluación / tesis)
    Route::get('/metricas/recomendaciones', [RecomendacionMetricasController::class, 'index'])->name('metricas.recomendaciones');

    // Bloque 6 — Mapa de calor de enfermedades (vista + export CSV)
    Route::get('/metricas/heatmap-enfermedades', [HeatmapEnfermedadesController::class, 'index'])
        ->name('metricas.heatmap_enfermedades');
    Route::get('/metricas/heatmap-enfermedades/export.csv', [HeatmapEnfermedadesController::class, 'exportCsv'])
        ->name('metricas.heatmap_enfermedades.csv');

    // Caja
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
    Route::post('/caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
    Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('/caja/{cajaSesion}', [CajaController::class, 'show'])->name('caja.show');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::post('/reportes/generar', [ReporteController::class, 'generar'])->name('reportes.generar');

    // Boletas
    Route::get('/boletas/{venta}', [BoletaController::class, 'show'])->name('boletas.show');
    Route::get('/boletas/{venta}/pdf', [BoletaController::class, 'pdf'])->name('boletas.pdf');
    Route::get('/boletas/{venta}/whatsapp', [BoletaController::class, 'whatsapp'])->name('boletas.whatsapp');
    Route::get('/boletas/{venta}/ticket', [BoletaController::class, 'ticket'])->name('boletas.ticket');

    // Recetario — rutas específicas ANTES del resource para evitar conflicto con {recetario}
    Route::get('/recetario/exportar', [RecetarioController::class, 'exportar'])->name('recetario.exportar');
    Route::get('/recetario/plantilla', [RecetarioController::class, 'plantilla'])->name('recetario.plantilla');
    Route::post('/recetario/importar', [RecetarioController::class, 'importar'])->name('recetario.importar');
    Route::resource('recetario', RecetarioController::class);

    // IA
    Route::get('/ia', [IAController::class, 'index'])->name('ia.index');
    Route::post('/ia/analizar', [IAController::class, 'analizar'])->name('ia.analizar');

    // Reclamos
    Route::resource('reclamos', ReclamoController::class)->except(['edit']);
    Route::post('/reclamos/{reclamo}/escalar', [ReclamoController::class, 'escalar'])->name('reclamos.escalar');

    // Cordiales
    Route::get('/cordiales/precios', [CordialController::class, 'precios'])->name('cordiales.precios');
    Route::resource('cordiales', CordialController::class)->only(['index', 'create', 'store']);

    // Fidelización — Premios pendientes de entrega
    Route::get('/fidelizacion', [FidelizacionController::class, 'index'])->name('fidelizacion.index');
    Route::post('/fidelizacion/{canje}/entregar', [FidelizacionController::class, 'entregar'])->name('fidelizacion.entregar');

    // Admin only routes
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('sucursales', SucursalController::class);
        Route::resource('usuarios', UsuarioController::class);
    });
});

// Autenticación por defecto de Laravel
require __DIR__.'/auth.php';
