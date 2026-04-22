<?php

namespace App\Http\Controllers;

use App\Models\CordialVenta;
use App\Models\Venta;
use App\Models\CajaSesion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CordialController extends Controller
{
    /**
     * Lista de ventas de cordiales.
     */
    public function index(Request $request)
    {
        $query = CordialVenta::with(['venta.cliente', 'venta.empleado'])
            ->when(auth()->user()->sucursal_id, fn($q, $sid) =>
                $q->whereHas('venta', fn($sub) => $sub->where('sucursal_id', $sid))
            );

        if ($request->fecha) {
            $query->whereDate('created_at', $request->fecha);
        }

        $cordiales  = $query->latest()->paginate(20);
        $tipos      = CordialVenta::$labels;
        $precios    = CordialVenta::$precios;

        return view('cordiales.index', compact('cordiales', 'tipos', 'precios'));
    }

    /**
     * Formulario para registrar venta directa de cordial.
     */
    public function create()
    {
        $tipos   = CordialVenta::$labels;
        $precios = CordialVenta::$precios;
        return view('cordiales.create', compact('tipos', 'precios'));
    }

    /**
     * Registrar una venta de cordial de forma directa (sin producto del inventario).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'               => 'required|in:' . implode(',', array_keys(CordialVenta::$precios)),
            'cantidad'           => 'required|integer|min:1|max:20',
            'es_invitado'        => 'nullable|boolean',
            'motivo_invitado'    => 'nullable|string|max:255',
            'metodo_pago'        => 'required|string',
            'cliente_id'         => 'nullable|exists:clientes,id',
        ]);

        DB::beginTransaction();
        try {
            $esInvitado  = (bool) ($data['es_invitado'] ?? false);
            $precio      = $esInvitado ? 0 : (CordialVenta::$precios[$data['tipo']] ?? 0);
            $sucursalId  = auth()->user()->sucursal_id ?? 1;
            $cajaActiva  = CajaSesion::where('estado', 'abierta')
                ->where('user_id', auth()->id())
                ->first();

            $clienteId = $data['cliente_id'] ?? null;
            $cliente   = $clienteId ? \App\Models\Cliente::find($clienteId) : null;

            // Crear venta contenedora
            $venta = Venta::create([
                'cliente_id'     => $clienteId,
                'user_id'        => auth()->id(),
                'sucursal_id'    => $sucursalId,
                'subtotal'       => $precio * $data['cantidad'],
                'igv'            => 0,
                'total'          => $precio * $data['cantidad'],
                'descuento_total'=> 0,
                'metodo_pago'    => $data['metodo_pago'],
                'estado'         => 'completada',
                'caja_sesion_id' => $cajaActiva?->id,
            ]);
            $venta->numero_boleta = $venta->generarNumeroBoleta();
            $venta->save();

            // Registrar detalle de cordial
            CordialVenta::create([
                'venta_id'          => $venta->id,
                'tipo'              => $data['tipo'],
                'precio'            => $precio,
                'cantidad'          => $data['cantidad'],
                'es_invitado'       => $esInvitado,
                'empleado_invita_id'=> auth()->id(),
                'motivo_invitado'   => $data['motivo_invitado'] ?? null,
            ]);

            // Promo: litro puro S/80 → 1 toma llevar_s5 gratis por unidad comprada
            if (!$esInvitado && $data['tipo'] === 'litro_puro_s80') {
                for ($i = 0; $i < $data['cantidad']; $i++) {
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
            }


            // Actualizar caja
            if ($cajaActiva && $precio > 0) {
                $campo = match($data['metodo_pago']) {
                    'yape' => 'total_yape', 'plin' => 'total_plin',
                    'efectivo' => 'total_efectivo', default => 'total_otros',
                };
                $cajaActiva->increment($campo, $precio * $data['cantidad']);
                $cajaActiva->increment('total_esperado', $precio * $data['cantidad']);
            }

            DB::commit();
            return redirect()->route('cordiales.index')
                ->with('success', 'Venta de cordial registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Precios disponibles de cordiales (para uso con AJAX desde el POS).
     */
    public function precios()
    {
        return response()->json([
            'precios' => CordialVenta::$precios,
            'labels'  => CordialVenta::$labels,
        ]);
    }
}
