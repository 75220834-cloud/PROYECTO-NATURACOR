<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\CordialVenta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\CajaSesion;
use App\Services\Fidelizacion\FidelizacionService;
use App\Services\Recommendation\AbTestingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function __construct(
        private readonly FidelizacionService $fidelizacionService,
        private readonly AbTestingService $ab,
    ) {}

    // POS - Pantalla principal de venta
    public function pos()
    {
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
        $tieneItems   = $request->filled('items')   && is_array($request->items)   && count($request->items) > 0;
        $tieneCordial = $request->filled('cordial') && is_array($request->cordial) && count($request->cordial) > 0;

        if (!$tieneItems && !$tieneCordial) {
            return response()->json(['success' => false, 'message' => 'Agrega al menos un producto al carrito'], 422);
        }

        $rules = [
            'metodo_pago' => 'required|string',
            'cliente_id'  => 'nullable|exists:clientes,id',
        ];
        if ($tieneItems) {
            $rules['items']                  = 'array';
            $rules['items.*.producto_id']    = 'required|exists:productos,id';
            $rules['items.*.cantidad']       = 'required|integer|min:1';
            $rules['items.*.descuento']      = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $cliente    = $request->cliente_id ? Cliente::find($request->cliente_id) : null;
            $igvFactor  = config('naturacor.igv_porcentaje', 18) / (100 + config('naturacor.igv_porcentaje', 18));
            $sucursalId = auth()->user()->sucursal_id ?? 1;
            $cajaActiva = CajaSesion::where('estado', 'abierta')->where('user_id', auth()->id())->first();

            $subtotal      = 0;
            $descuentoTotal = 0;
            $lineas        = [];

            if ($tieneItems) {
                foreach ($request->items as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para {$producto->nombre}");
                    }
                    $descuento   = $item['descuento'] ?? 0;
                    $precioFinal = ($producto->precio - $descuento) * $item['cantidad'];
                    $lineas[]    = ['producto' => $producto, 'cantidad' => $item['cantidad'], 'descuento' => $descuento, 'subtotal' => $precioFinal];
                    $subtotal      += $precioFinal;
                    $descuentoTotal += $descuento * $item['cantidad'];
                }
            }

            $igv   = round($subtotal * $igvFactor, 2);

            // Calcular total de cordiales (no llevan IGV)
            $totalCordiales = 0;
            if ($tieneCordial) {
                foreach ($request->cordial as $c) {
                    $esInvitado = ($c['tipo'] === 'invitado');
                    $precioCordial = $esInvitado ? 0 : (CordialVenta::$precios[$c['tipo']] ?? 0);
                    $totalCordiales += $precioCordial * ($c['cantidad'] ?? 1);
                }
            }

            $total = $subtotal + $totalCordiales;

            $venta                    = new Venta();
            $venta->cliente_id        = $cliente?->id;
            $venta->user_id           = auth()->id();
            $venta->sucursal_id       = $sucursalId;
            $venta->subtotal          = round($subtotal - $igv, 2);
            $venta->igv               = $igv;
            $venta->total             = $total;
            $venta->descuento_total   = $descuentoTotal;
            $venta->metodo_pago       = $request->metodo_pago;
            $venta->metodos_pago_detalle = $request->metodos_pago_detalle ?? null;
            $venta->caja_sesion_id    = $cajaActiva?->id;
            $venta->estado            = 'completada';
            // [BLOQUE 4] Estampamos el grupo A/B del cliente al momento de la
            // venta. Si el cliente no está identificado (venta walk-in),
            // queda en 'sin_ab' porque no podríamos atribuirla al experimento.
            $venta->grupo_ab          = $this->ab->asignarGrupo($cliente?->id);
            $venta->save();

            $venta->numero_boleta = $venta->generarNumeroBoleta();
            $venta->save();

            foreach ($lineas as $linea) {
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $linea['producto']->id,
                    'nombre_producto' => $linea['producto']->nombre,
                    'precio_unitario' => $linea['producto']->precio,
                    'cantidad'        => $linea['cantidad'],
                    'descuento'       => $linea['descuento'],
                    'subtotal'        => $linea['subtotal'],
                ]);
                $linea['producto']->decrement('stock', $linea['cantidad']);
            }

            // Registrar cordiales y aplicar promo litro puro
            $promosGeneradas = [];
            if ($request->has('cordial')) {
                foreach ($request->cordial as $c) {
                    CordialVenta::create([
                        'venta_id'          => $venta->id,
                        'tipo'              => $c['tipo'],
                        'precio'            => CordialVenta::$precios[$c['tipo']] ?? 0,
                        'cantidad'          => $c['cantidad'] ?? 1,
                        'es_invitado'       => ($c['tipo'] === 'invitado'),
                        'empleado_invita_id'=> $c['empleado_invita_id'] ?? auth()->id(),
                        'motivo_invitado'   => $c['motivo_invitado'] ?? null,
                    ]);

                    // Promo: litro puro S/80 → 1 toma llevar_s5 gratis por unidad
                    if ($c['tipo'] === 'litro_puro_s80') {
                        $cantidad = $c['cantidad'] ?? 1;
                        for ($i = 0; $i < $cantidad; $i++) {
                            CordialVenta::create([
                                'venta_id'          => $venta->id,
                                'tipo'              => 'llevar_s5',
                                'precio'            => 0,
                                'cantidad'          => 1,
                                'es_invitado'       => true,
                                'empleado_invita_id'=> auth()->id(),
                                'motivo_invitado'   => 'Promo: 1 toma gratis por litro puro S/80',
                            ]);
                        }
                        $promosGeneradas[] = '1 toma llevar S/5 gratis por litro puro';
                    }
                }
            }

            // Fidelización 2026
            $canjesGenerados = $this->procesarFidelizacion($venta, $cliente, $lineas, $request);

            if ($cajaActiva) {
                $this->actualizarCaja($cajaActiva, $venta);
            }

            \App\Models\LogAuditoria::create([
                'user_id'        => auth()->id(),
                'accion'         => 'venta.creada',
                'tabla_afectada' => 'ventas',
                'registro_id'    => $venta->id,
                'datos_nuevos'   => ['total' => $venta->total, 'items' => count($lineas)],
                'ip'             => request()->ip(),
                'sucursal_id'    => $sucursalId,
            ]);

            DB::commit();

            return response()->json([
                'success'         => true,
                'venta_id'        => $venta->id,
                'numero_boleta'   => $venta->numero_boleta,
                'premio_generado' => count($canjesGenerados) > 0,
                'canjes'          => $canjesGenerados,
                'promos'          => $promosGeneradas,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Fidelización permanente:
     * - acumulado histórico sin reset
     * - premios emitidos por hitos: floor(acumulado / umbral)
     */
    private function procesarFidelizacion(Venta $venta, ?Cliente $cliente, array $lineas, Request $request): array
    {
        if (!$cliente) return [];

        $hoy    = now()->format('Y-m-d');
        $inicio = config('naturacor.fidelizacion_inicio', '2026-01-01');
        $fin    = config('naturacor.fidelizacion_fin',    '2026-12-31');
        if ($hoy < $inicio || $hoy > $fin) return [];

        // 1) Sumar los que pasaron como productos regulares (naturales o cordiales)
        $montoProductos = collect($lineas)
            ->filter(fn($l) => in_array($l['producto']->tipo, ['natural', 'cordial']))
            ->sum('subtotal');

        // 2) Sumar cordiales rápidos (excluyendo invitados/gratis)
        $montoCordiales = 0;
        if ($request->has('cordial')) {
            foreach ($request->cordial as $c) {
                if ($c['tipo'] !== 'invitado') {
                    $precio = CordialVenta::$precios[$c['tipo']] ?? 0;
                    $montoCordiales += $precio * ($c['cantidad'] ?? 1);
                }
            }
        }

        $montoTotal = $montoProductos + $montoCordiales;
        return $this->fidelizacionService->registrarAcumuladoYGenerarCanjes(
            $venta,
            $cliente,
            (float) $montoTotal
        );
    }

    private function actualizarCaja(CajaSesion $caja, Venta $venta)
    {
        $campo = match($venta->metodo_pago) {
            'yape'    => 'total_yape',
            'plin'    => 'total_plin',
            'efectivo'=> 'total_efectivo',
            default   => 'total_otros',
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
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Solo el administrador puede anular ventas.');
        }
        $venta->update(['estado' => 'anulada']);
        return back()->with('success', 'Venta anulada correctamente.');
    }
}
