<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\CordialVenta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\FidelizacionCanje;
use App\Models\CajaSesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaController extends Controller
{
    // POS - Pantalla principal de venta
    public function pos()
    {
        // Carga TODOS los productos activos (naturales + cordiales), sin filtrar por sucursal
        $productos = Producto::where('activo', true)
            ->orderByDesc('frecuente')
            ->orderBy('nombre')
            ->get();
        $frecuentes = $productos->where('frecuente', true)->values();
        $cajaActiva = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->first();
        return view('ventas.pos', compact('productos', 'frecuentes', 'cajaActiva'));
    }

    // Confirmar venta desde POS
    public function store(Request $request)
    {
        // items es opcional si solo hay cordiales
        $tieneItems = $request->filled('items') && is_array($request->items) && count($request->items) > 0;
        $tieneCordial = $request->filled('cordial') && is_array($request->cordial) && count($request->cordial) > 0;

        if (!$tieneItems && !$tieneCordial) {
            return response()->json(['success' => false, 'message' => 'Agrega al menos un producto al carrito'], 422);
        }

        $rules = [
            'metodo_pago' => 'required|string',
            'cliente_id' => 'nullable|exists:clientes,id',
        ];

        if ($tieneItems) {
            $rules['items'] = 'array';
            $rules['items.*.producto_id'] = 'required|exists:productos,id';
            $rules['items.*.cantidad'] = 'required|integer|min:1';
            $rules['items.*.descuento'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $cliente = $request->cliente_id ? Cliente::find($request->cliente_id) : null;
            // Perú: los precios YA incluyen IGV. Se extrae, no se suma.
            $igvFactor = config('naturacor.igv_porcentaje', 18) / (100 + config('naturacor.igv_porcentaje', 18)); // 18/118
            $sucursalId = auth()->user()->sucursal_id ?? 1;
            $cajaActiva = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->first();

            $subtotal = 0;
            $descuentoTotal = 0;
            $lineas = [];

            if ($tieneItems) {
                foreach ($request->items as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para {$producto->nombre}");
                    }
                    $descuento = $item['descuento'] ?? 0;
                    $precioFinal = ($producto->precio - $descuento) * $item['cantidad'];
                    $lineas[] = ['producto' => $producto, 'cantidad' => $item['cantidad'], 'descuento' => $descuento, 'subtotal' => $precioFinal];
                    $subtotal += $precioFinal;
                    $descuentoTotal += $descuento * $item['cantidad'];
                }
            }

            // IGV incluido en precio: se EXTRAE del total (no se suma encima)
            $igv = round($subtotal * $igvFactor, 2);
            $total = $subtotal; // El total ES el subtotal (precios ya incluyen IGV)

            // Crear venta
            $venta = new Venta();
            $venta->cliente_id = $cliente?->id;
            $venta->user_id = auth()->id();
            $venta->sucursal_id = $sucursalId;
            $venta->subtotal = round($subtotal - $igv, 2); // base imponible
            $venta->igv = $igv;
            $venta->total = $total;
            $venta->descuento_total = $descuentoTotal;
            $venta->metodo_pago = $request->metodo_pago;
            $venta->metodos_pago_detalle = $request->metodos_pago_detalle ?? null;
            $venta->caja_sesion_id = $cajaActiva?->id;
            $venta->save();

            // Número de boleta
            $venta->numero_boleta = $venta->generarNumeroBoleta();
            $venta->save();

            // Detalle y descontar stock
            foreach ($lineas as $linea) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $linea['producto']->id,
                    'nombre_producto' => $linea['producto']->nombre,
                    'precio_unitario' => $linea['producto']->precio,
                    'cantidad' => $linea['cantidad'],
                    'descuento' => $linea['descuento'],
                    'subtotal' => $linea['subtotal'],
                ]);
                $linea['producto']->decrement('stock', $linea['cantidad']);
            }

            // Fidelización
            $this->procesarFidelizacion($venta, $cliente, $lineas, $request);

            // Registrar cordial si viene
            if ($request->has('cordial')) {
                foreach ($request->cordial as $c) {
                    CordialVenta::create([
                        'venta_id' => $venta->id,
                        'tipo' => $c['tipo'],
                        'precio' => CordialVenta::$precios[$c['tipo']] ?? 0,
                        'cantidad' => $c['cantidad'] ?? 1,
                        'es_invitado' => ($c['tipo'] === 'invitado'),
                        'empleado_invita_id' => $c['empleado_invita_id'] ?? auth()->id(),
                        'motivo_invitado' => $c['motivo_invitado'] ?? null,
                    ]);
                }
            }

            // Actualizar caja
            if ($cajaActiva) {
                $this->actualizarCaja($cajaActiva, $venta);
            }

            // Log
            \App\Models\LogAuditoria::create([
                'user_id' => auth()->id(),
                'accion' => 'venta.creada',
                'tabla_afectada' => 'ventas',
                'registro_id' => $venta->id,
                'datos_nuevos' => ['total' => $venta->total, 'items' => count($lineas)],
                'ip' => request()->ip(),
                'sucursal_id' => $sucursalId,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'venta_id' => $venta->id, 'numero_boleta' => $venta->numero_boleta]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function procesarFidelizacion(Venta $venta, ?Cliente $cliente, array $lineas, Request $request)
    {
        if (!$cliente) return;

        $montoNaturales = collect($lineas)->filter(fn($l) => $l['producto']->tipo === 'natural')->sum('subtotal');
        $cliente->increment('acumulado_naturales', $montoNaturales);
        $cliente->refresh();

        $umbral = config('naturacor.fidelizacion_monto', 250);
        if ($cliente->acumulado_naturales >= $umbral) {
            FidelizacionCanje::create([
                'cliente_id' => $cliente->id,
                'venta_id' => $venta->id,
                'tipo_regla' => 'regla1_250',
                'valor_premio' => config('naturacor.fidelizacion_maximo_premio', 30),
                'descripcion' => "Premio por acumulado S/{$cliente->acumulado_naturales}",
            ]);
            $cliente->acumulado_naturales = 0;
            $cliente->save();
        }

        // Regla 2: litros de cordial
        if ($request->has('cordial')) {
            foreach ($request->cordial as $c) {
                if ($c['tipo'] === 'litro_especial_s40') {
                    FidelizacionCanje::create([
                        'cliente_id' => $cliente->id,
                        'venta_id' => $venta->id,
                        'tipo_regla' => 'regla2_litro40',
                        'valor_premio' => 5,
                        'descripcion' => '1 vaso gratis por litro especial S/40',
                    ]);
                } elseif ($c['tipo'] === 'litro_puro_s80') {
                    FidelizacionCanje::create([
                        'cliente_id' => $cliente->id,
                        'venta_id' => $venta->id,
                        'tipo_regla' => 'regla2_litro80',
                        'valor_premio' => 10,
                        'descripcion' => '2 vasos gratis por litro puro S/80',
                    ]);
                }
            }
        }
    }

    private function actualizarCaja(CajaSesion $caja, Venta $venta)
    {
        $campo = match($venta->metodo_pago) {
            'yape' => 'total_yape',
            'plin' => 'total_plin',
            'efectivo' => 'total_efectivo',
            default => 'total_otros',
        };
        $caja->increment($campo, $venta->total);
        $caja->increment('total_esperado', $venta->total);
    }

    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'empleado', 'sucursal'])
            ->when(auth()->user()->sucursal_id, fn($q) => $q->where('sucursal_id', auth()->user()->sucursal_id));

        if ($request->fecha_desde) $query->whereDate('created_at', '>=', $request->fecha_desde);
        if ($request->fecha_hasta) $query->whereDate('created_at', '<=', $request->fecha_hasta);
        if ($request->metodo_pago) $query->where('metodo_pago', $request->metodo_pago);

        $ventas = $query->latest()->paginate(20);
        return view('ventas.index', compact('ventas'));
    }

    public function show(Venta $venta)
    {
        $venta->load(['cliente', 'empleado', 'sucursal', 'detalles.producto', 'cordialVentas']);
        return view('ventas.show', compact('venta'));
    }

    public function create() { return redirect()->route('ventas.pos'); }
    public function edit(Venta $venta) { return redirect()->route('ventas.show', $venta); }
    public function update(Request $request, Venta $venta) { abort(405); }
    public function destroy(Venta $venta)
    {
        $this->authorize('delete-ventas');
        $venta->update(['estado' => 'anulada']);
        return back()->with('success', 'Venta anulada correctamente.');
    }
}
