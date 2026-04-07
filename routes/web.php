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

// Redirigir raíz al login
Route::get('/', fn() => redirect('/login'));

// Rutas autenticadas
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS (Punto de Venta)
    Route::get('/ventas/pos', [VentaController::class, 'pos'])->name('ventas.pos');
    Route::resource('ventas', VentaController::class)->except(['create', 'edit']);

    // Productos
    Route::resource('productos', ProductoController::class);
    Route::get('/api/productos/buscar', [ProductoController::class, 'buscar'])->name('productos.buscar');
    Route::get('/api/productos/barcode', [ProductoController::class, 'buscarBarcode'])->name('productos.barcode');

    // Clientes
    Route::resource('clientes', ClienteController::class);
    Route::get('/api/clientes/dni', [ClienteController::class, 'buscarDni'])->name('clientes.dni');

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

    // Recetario
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
