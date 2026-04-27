<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClientePerfilAfinidad;
use App\Models\DetalleVenta;
use App\Models\Enfermedad;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Recommendation\PerfilSaludService;
use App\Services\Recommendation\RecomendacionEngine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IAController extends Controller
{
    public function __construct(
        private readonly PerfilSaludService $perfilSaludService,
        private readonly RecomendacionEngine $recomendacionEngine
    ) {}

    public function index()
    {
        $analisis = $this->analizarNegocio();
        $clientes = Cliente::query()
            ->orderByDesc('updated_at')
            ->limit(120)
            ->get(['id', 'dni', 'nombre', 'apellido']);

        $groqKey  = config('naturacor.groq_api_key');
        $geminiKey = config('naturacor.gemini_api_key');
        $tieneApiKey = !empty($groqKey) || !empty($geminiKey);
        $modoOnline  = $tieneApiKey && $this->verificarConexion();

        // Debug: log para verificar detección de API keys
        Log::info('IA index - API key check', [
            'groq_key_present'   => !empty($groqKey),
            'gemini_key_present' => !empty($geminiKey),
            'tiene_api_key'      => $tieneApiKey,
            'modo_online'        => $modoOnline,
        ]);

        return view('ia.index', compact('analisis', 'modoOnline', 'clientes'));
    }

    public function analizar(Request $request)
    {
        $data = $request->validate([
            'consulta' => 'nullable|string|max:2000',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);
        $consulta = $data['consulta'] ?? 'Analiza el negocio y dame recomendaciones';
        $cliente = isset($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;

        $analisis = $this->analizarNegocio();
        $contextoCliente = $this->construirContextoCliente($cliente, $request);
        $contextoIa = $this->formatearContexto($analisis, $contextoCliente);
        $promptDinamico = $this->construirPromptDinamico($consulta, $contextoIa);

        // 1) Intentar Groq primero
        $apiKeyGroq = config('naturacor.groq_api_key');
        if (!empty($apiKeyGroq)) {
            $respuesta = $this->consultarGroq($consulta, $promptDinamico);
            if ($respuesta) {
                return response()->json([
                    'modo' => 'online',
                    'resultado' => $respuesta,
                    'analisis' => $analisis,
                    'cliente_contexto' => $contextoCliente,
                ]);
            }
        }

        // 2) Intentar Gemini
        $apiKeyGemini = config('naturacor.gemini_api_key');
        if (!empty($apiKeyGemini)) {
            $respuesta = $this->consultarGemini($consulta, $promptDinamico);
            if ($respuesta) {
                return response()->json([
                    'modo' => 'online',
                    'resultado' => $respuesta,
                    'analisis' => $analisis,
                    'cliente_contexto' => $contextoCliente,
                ]);
            }
        }

        // 3) Modo offline
        $respuestaInteligente = $this->generarRespuestaInteligente($consulta, $analisis, $contextoCliente);
        return response()->json([
            'modo'      => 'offline',
            'resultado' => $respuestaInteligente,
            'analisis'  => $analisis,
            'cliente_contexto' => $contextoCliente,
        ]);
    }


    private function consultarGemini(string $consulta, string $promptDinamico): ?string
    {
        try {
            $apiKey   = config('naturacor.gemini_api_key');

            $systemInstruction = $this->systemPromptBase()."\n\n".$promptDinamico;

            $response = Http::withOptions([
                'connect_timeout' => 10,
                'timeout'         => 30,
                'verify'          => false, // XAMPP/Windows SSL fix
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                [
                    'system_instruction' => [
                        'parts' => [['text' => $systemInstruction]]
                    ],
                    'contents' => [
                        ['parts' => [['text' => $consulta]]]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 1500,
                        'temperature'     => 0.8,
                    ],
                ]
            );

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? null;
            }

            // Log del error para debug
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Gemini exception: ' . $e->getMessage());
            return null;
        }
    }

    private function consultarGroq(string $consulta, string $promptDinamico): ?string
    {
        try {
            $systemPrompt = $this->systemPromptBase()."\n\n".$promptDinamico;

            $response = Http::withOptions([
                'connect_timeout' => 10,
                'timeout'         => 30,
                'verify'          => false, // XAMPP/Windows SSL fix
            ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('naturacor.groq_api_key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $consulta],
                    ],
                    'max_tokens'  => 1500,
                    'temperature' => 0.8,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? null;
            }
            Log::error('Groq error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Groq exception: ' . $e->getMessage());
            return null;
        }
    }

    private function consultarOpenAI(string $consulta, string $promptDinamico): ?string
    {
        try {
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . config('naturacor.openai_api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPromptBase()."\n\n".$promptDinamico],
                    ['role' => 'user', 'content' => $consulta],
                ],
                'max_tokens' => 600,
            ]);

            return $response->successful()
                ? ($response->json('choices.0.message.content') ?? null)
                : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generarRespuestaInteligente(string $consulta, array $analisis, ?array $contextoCliente): string
    {
        $consulta = strtolower($consulta);
        $kw = fn($words) => collect($words)->some(fn($w) => str_contains($consulta, $w));

        $lineas = ["📊 **Análisis de NATURACOR** — " . now()->format('d/m/Y H:i'), ""];

        if ($contextoCliente) {
            $lineas[] = "👤 **Cliente analizado:** {$contextoCliente['cliente']['nombre']} (DNI {$contextoCliente['cliente']['dni']})";
            $lineas[] = "• Frecuencia estimada: {$contextoCliente['patrones']['frecuencia_30d']} compra(s) en 30 días";
            $lineas[] = "• Acumulado fidelización: S/".number_format((float) $contextoCliente['cliente']['acumulado_naturales'], 2);
            $lineas[] = "";
            $lineas[] = "🧠 **Condiciones inferidas por patrón de compra (no diagnóstico):**";
            if (! empty($contextoCliente['enfermedades_inferidas'])) {
                foreach ($contextoCliente['enfermedades_inferidas'] as $enf) {
                    $lineas[] = "• {$enf['nombre']} (score {$enf['score_pct']}%, evidencias: {$enf['evidencias']})";
                }
            } else {
                $lineas[] = "• Aún no hay señales suficientes en el historial.";
            }
            $lineas[] = "";
            $lineas[] = "🌿 **Productos recomendados por motor real:**";
            if (! empty($contextoCliente['recomendaciones']['items'])) {
                foreach (array_slice($contextoCliente['recomendaciones']['items'], 0, 6) as $it) {
                    $lineas[] = "• {$it['producto']['nombre']} — score {$it['score_final']}";
                }
            } else {
                $lineas[] = "• Sin recomendaciones para este cliente en este momento.";
            }
            $lineas[] = "";
        }

        // Respuesta basada en la consulta
        if ($kw(['vend', 'top', 'popular', 'más'])) {
            $lineas[] = "🏆 **Productos más vendidos (últimos 7 días):**";
            if ($analisis['top_productos']->isNotEmpty()) {
                foreach ($analisis['top_productos'] as $nombre => $total) {
                    $lineas[] = "  • {$nombre}: {$total} unidades";
                }
            } else {
                $lineas[] = "  Sin ventas registradas esta semana.";
            }
        } elseif ($kw(['stock', 'reponer', 'inventario', 'falt'])) {
            $lineas[] = "⚠️ **Productos que necesitan reposición:**";
            if ($analisis['stock_critico']->isNotEmpty()) {
                foreach ($analisis['stock_critico'] as $p) {
                    $valor = $p->stock == 0 ? "🔴 AGOTADO" : "🟡 Stock: {$p->stock} (mín: {$p->stock_minimo})";
                    $lineas[] = "  • {$p->nombre}: {$valor}";
                }
            } else {
                $lineas[] = "  ✅ Todos los productos tienen stock suficiente.";
            }
        } elseif ($kw(['venta', 'ingreso', 'ganancia', 'dinero', 'recaudado'])) {
            $lineas[] = "💰 **Resumen financiero:**";
            $lineas[] = "  • Hoy: S/ " . number_format($analisis['ventas_hoy']['total'], 2) . " ({$analisis['ventas_hoy']['count']} ventas)";
            $lineas[] = "  • Esta semana: S/ " . number_format($analisis['ventas_semana']['total'], 2) . " ({$analisis['ventas_semana']['count']} ventas)";
            $lineas[] = "  • Este mes: S/ " . number_format($analisis['ventas_mes']['total'], 2) . " ({$analisis['ventas_mes']['count']} ventas)";
        } elseif ($kw(['cliente', 'fideliz', 'leal'])) {
            $lineas[] = "👥 **Análisis de clientes:**";
            $lineas[] = "  • Total clientes registrados: {$analisis['clientes_total']}";
            $lineas[] = "  • Clientes frecuentes: {$analisis['clientes_frecuentes']}";
            if ($analisis['top_cliente']) {
                $lineas[] = "  • Mejor cliente: {$analisis['top_cliente']->nombre} (S/ " . number_format($analisis['top_cliente']->total_gastado, 2) . ")";
            }
        } else {
            // Resumen general
            $lineas[] = "💰 **Rendimiento de ventas:**";
            $lineas[] = "  • Hoy: S/ " . number_format($analisis['ventas_hoy']['total'], 2) . " ({$analisis['ventas_hoy']['count']} ventas)";
            $lineas[] = "  • Esta semana: S/ " . number_format($analisis['ventas_semana']['total'], 2);
            $lineas[] = "  • Este mes: S/ " . number_format($analisis['ventas_mes']['total'], 2);
            $lineas[] = "";
            $lineas[] = "📦 **Inventario:**";
            $lineas[] = "  • Productos activos: {$analisis['productos_activos']}";
            $lineas[] = "  • Con stock bajo: {$analisis['stock_critico']->count()}";
            if ($analisis['stock_critico']->isNotEmpty()) {
                $lineas[] = "  ⚠️ Urgente reponer: " . $analisis['stock_critico']->pluck('nombre')->implode(', ');
            }
        }

        $lineas[] = "";
        $lineas[] = "💡 **Recomendaciones:**";
        foreach ($this->generarRecomendaciones($analisis) as $rec) {
            $lineas[] = "  {$rec}";
        }

        return implode("\n", $lineas);
    }

    private function generarRecomendaciones(array $analisis): array
    {
        $recs = [];
        if ($analisis['stock_critico']->count() > 0) {
            $recs[] = "🔴 Reponer urgente: " . $analisis['stock_critico']->pluck('nombre')->implode(', ');
        }
        if ($analisis['ventas_hoy']['count'] == 0) {
            $recs[] = "📣 No hubo ventas hoy. Considera ofrecer una promoción.";
        } elseif ($analisis['ventas_hoy']['total'] > ($analisis['ventas_semana']['total'] / 7) * 1.5) {
            $recs[] = "🚀 Día excepcional: las ventas de hoy superan el promedio diario.";
        }
        if ($analisis['clientes_total'] < 10) {
            $recs[] = "👥 Pocos clientes registrados. Incentiva el registro de DNI en cada venta.";
        }
        if (empty($recs)) {
            $recs[] = "✅ El negocio opera normalmente. Mantén el buen servicio al cliente.";
        }
        return $recs;
    }

    private function analizarNegocio(): array
    {
        $hoy = Carbon::today();
        $semana = Carbon::now()->subDays(7);
        $mes = Carbon::now()->startOfMonth();

        $ventasHoy = Venta::where('estado', 'completada')->whereDate('created_at', $hoy);
        $ventasSemana = Venta::where('estado', 'completada')->where('created_at', '>=', $semana);
        $ventasMes = Venta::where('estado', 'completada')->where('created_at', '>=', $mes);

        $topCliente = DB::table('clientes')
            ->join('ventas', 'clientes.id', '=', 'ventas.cliente_id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.created_at', '>=', $mes)
            ->select('clientes.id', 'clientes.nombre', 'clientes.apellido', DB::raw('SUM(ventas.total) as total_gastado'))
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido')
            ->orderByDesc('total_gastado')
            ->first();


        return [
            'ventas_hoy' => [
                'count' => (clone $ventasHoy)->count(),
                'total' => (clone $ventasHoy)->sum('total'),
            ],
            'ventas_semana' => [
                'count' => (clone $ventasSemana)->count(),
                'total' => (clone $ventasSemana)->sum('total'),
                'por_dia' => Venta::where('estado', 'completada')
                    ->where('created_at', '>=', $semana)
                    ->selectRaw('DATE(created_at) as fecha, COUNT(*) as ventas, SUM(total) as total')
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
            ],
            'ventas_mes' => [
                'count' => (clone $ventasMes)->count(),
                'total' => (clone $ventasMes)->sum('total'),
            ],
            'top_productos' => DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'completada')
                ->where('ventas.created_at', '>=', $semana)
                ->selectRaw('nombre_producto, SUM(cantidad) as total')
                ->groupBy('nombre_producto')
                ->orderByDesc('total')
                ->limit(5)
                ->pluck('total', 'nombre_producto'),
            'stock_critico' => Producto::where('activo', true)
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->orderBy('stock')
                ->get(['nombre', 'stock', 'stock_minimo']),
            'productos_activos' => Producto::where('activo', true)->count(),
            'clientes_total' => Cliente::count(),
            'clientes_frecuentes' => Cliente::where('frecuente', true)->count(),
            'top_cliente' => $topCliente,
            'pago_metodos' => Venta::where('estado', 'completada')
                ->where('created_at', '>=', $mes)
                ->selectRaw('metodo_pago, COUNT(*) as count, SUM(total) as total')
                ->groupBy('metodo_pago')
                ->get(),
            // Catálogo completo de productos
            'catalogo_productos' => Producto::where('activo', true)
                ->orderBy('nombre')
                ->get(['nombre', 'descripcion', 'precio', 'stock', 'stock_minimo']),
            // Recetario: enfermedades con productos recomendados
            'recetario' => Enfermedad::where('activa', true)
                ->with(['productos' => fn($q) => $q->where('activo', true)])
                ->get(),
            // Clientes con acumulado de fidelización
            'lista_clientes' => Cliente::orderByDesc('acumulado_naturales')
                ->get(['nombre', 'apellido', 'dni', 'telefono', 'acumulado_naturales', 'frecuente']),
        ];
    }

    private function formatearContexto(array $analisis, ?array $contextoCliente): string
    {
        $ctx = "DATOS DEL NEGOCIO NATURACOR:\n";
        $ctx .= "Ventas hoy: {$analisis['ventas_hoy']['count']} (S/{$analisis['ventas_hoy']['total']}). ";
        $ctx .= "Esta semana: {$analisis['ventas_semana']['count']} (S/{$analisis['ventas_semana']['total']}). ";
        $ctx .= "Este mes: {$analisis['ventas_mes']['count']} (S/{$analisis['ventas_mes']['total']}). ";
        $ctx .= "Clientes registrados: {$analisis['clientes_total']}.\n\n";

        // Catálogo de productos
        $ctx .= "CATÁLOGO DE PRODUCTOS DISPONIBLES EN TIENDA:\n";
        foreach ($analisis['catalogo_productos'] as $p) {
            $estado = $p->stock <= $p->stock_minimo ? ' ⚠️STOCK BAJO' : '';
            $ctx .= "- {$p->nombre}: S/{$p->precio} | Stock: {$p->stock} unidades{$estado}";
            if ($p->descripcion) $ctx .= " | {$p->descripcion}";
            $ctx .= "\n";
        }

        // Recetario
        if ($analisis['recetario']->isNotEmpty()) {
            $ctx .= "\nRECETARIO NATURACOR (enfermedades y productos recomendados):\n";
            foreach ($analisis['recetario'] as $enfermedad) {
                $ctx .= "Condición: {$enfermedad->nombre}";
                if ($enfermedad->descripcion) $ctx .= " — {$enfermedad->descripcion}";
                $ctx .= "\n";
                foreach ($enfermedad->productos as $prod) {
                    $ctx .= "  → Producto recomendado: {$prod->nombre} (S/{$prod->precio})";
                    if ($prod->pivot->instrucciones) {
                        $ctx .= " — Instrucciones: {$prod->pivot->instrucciones}";
                    }
                    $ctx .= "\n";
                }
            }
        }

        // Clientes y fidelización
        $umbral = config('naturacor.fidelizacion_monto', 500);
        $ctx .= "\nCLIENTES REGISTRADOS Y ACUMULADO DE FIDELIZACIÓN (meta: S/{$umbral} para premio):\n";
        foreach ($analisis['lista_clientes'] as $c) {
            $nombre = trim("{$c->nombre} {$c->apellido}");
            $acum = number_format($c->acumulado_naturales, 2);
            $falta = max(0, $umbral - $c->acumulado_naturales);
            $estado = $c->acumulado_naturales >= $umbral ? ' 🏆 LISTO PARA PREMIO' : '';
            $ctx .= "- {$nombre} (DNI: {$c->dni}): Acumulado S/{$acum} | Falta S/" . number_format($falta, 2) . " para premio{$estado}\n";
        }

        if ($contextoCliente) {
            $ctx .= "\nCONTEXTO PERSONALIZADO DEL CLIENTE:\n";
            $ctx .= json_encode($contextoCliente, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $ctx .= "\n";
        }

        return $ctx;
    }

    private function construirPromptDinamico(string $consulta, string $contextoIa): string
    {
        return "ROL: Analista de salud naturista basado en datos de consumo y ventas reales.\n"
            ."INSTRUCCIONES:\n"
            ."- No des respuestas genéricas.\n"
            ."- Si hay cliente en contexto, explica patrones reales, condiciones inferidas y recomendaciones concretas.\n"
            ."- Explica por qué recomiendas cada producto según historial/perfil/tendencia.\n"
            ."- Distingue explícitamente: inferencia de compra != diagnóstico médico.\n"
            ."- Responde en español, con estructura clara y accionable.\n\n"
            ."CONTEXTO DEL SISTEMA:\n{$contextoIa}\n\n"
            ."CONSULTA DEL USUARIO:\n{$consulta}";
    }

    private function systemPromptBase(): string
    {
        return "Eres NATURA, un analista de salud naturista para NATURACOR. "
            ."Tu prioridad es responder con base en datos reales del sistema: compras, perfil inferido, recetario y recomendaciones del motor. "
            ."Evita respuestas vacías o genéricas; justifica con evidencia del contexto proporcionado.";
    }

    private function construirContextoCliente(?Cliente $cliente, Request $request): ?array
    {
        if (! $cliente) {
            return null;
        }

        $this->perfilSaludService->asegurarPerfilReciente((int) $cliente->id, false);
        $sucursalId = $request->user()?->sucursal_id;
        $reco = $this->recomendacionEngine->recomendar($cliente, $sucursalId, 6, false);

        $perfil = ClientePerfilAfinidad::query()
            ->where('cliente_id', $cliente->id)
            ->with('enfermedad:id,nombre,categoria')
            ->orderByDesc('score')
            ->limit(6)
            ->get();

        $compras30 = Venta::query()
            ->where('cliente_id', $cliente->id)
            ->where('estado', 'completada')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $detalleReciente = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->where('ventas.cliente_id', $cliente->id)
            ->where('ventas.estado', 'completada')
            ->where('ventas.created_at', '>=', now()->subDays(90))
            ->selectRaw('productos.nombre as producto, SUM(detalle_ventas.cantidad) as unidades')
            ->groupBy('productos.nombre')
            ->orderByDesc('unidades')
            ->limit(8)
            ->get();

        return [
            'cliente' => [
                'id' => $cliente->id,
                'dni' => $cliente->dni,
                'nombre' => $cliente->nombreCompleto(),
                'acumulado_naturales' => (float) $cliente->acumulado_naturales,
            ],
            'patrones' => [
                'frecuencia_30d' => $compras30,
                'productos_frecuentes_90d' => $detalleReciente,
            ],
            'enfermedades_inferidas' => $perfil->map(function ($fila) {
                return [
                    'enfermedad_id' => $fila->enfermedad_id,
                    'nombre' => $fila->enfermedad->nombre ?? ('#'.$fila->enfermedad_id),
                    'categoria' => $fila->enfermedad->categoria,
                    'score_pct' => round(((float) $fila->score) * 100, 2),
                    'evidencias' => (int) $fila->evidencia_count,
                ];
            })->values()->all(),
            'recomendaciones' => [
                'perfil_filas' => $reco['perfil_filas'] ?? 0,
                'items' => $reco['items'] ?? [],
                'meta' => $reco['meta'] ?? [],
            ],
        ];
    }

    private function verificarConexion(): bool
    {
        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 5])
                ->get('https://api.groq.com');
            return true;
        } catch (\Exception $e) {
            try {
                Http::withOptions(['verify' => false, 'timeout' => 3])
                    ->get('https://www.google.com');
                return true;
            } catch (\Exception $e2) {
                Log::warning('IA - No internet connection detected: ' . $e2->getMessage());
                return false;
            }
        }
    }
}
