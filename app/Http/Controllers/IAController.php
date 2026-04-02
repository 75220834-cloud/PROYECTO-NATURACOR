<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IAController extends Controller
{
    public function index()
    {
        $analisis = $this->analizarNegocio();
        $tieneApiKey = !empty(env('GEMINI_API_KEY')) || !empty(env('GROQ_API_KEY'));
        $modoOnline  = $tieneApiKey && $this->verificarConexion();
        return view('ia.index', compact('analisis', 'modoOnline'));
    }

    public function analizar(Request $request)
    {
        $consulta = $request->consulta ?? 'Analiza el negocio y dame recomendaciones';
        $analisis = $this->analizarNegocio();

        // 1. Intentar Groq primero (Llama 3 — api.groq.com)
        $apiKeyGroq = env('GROQ_API_KEY');
        if (!empty($apiKeyGroq)) {
            $respuesta = $this->consultarGroq($consulta, $analisis);
            if ($respuesta) {
                return response()->json(['modo' => 'online', 'resultado' => $respuesta, 'analisis' => $analisis]);
            }
        }

        // 2. Intentar Gemini como alternativa
        $apiKeyGemini = env('GEMINI_API_KEY');
        if (!empty($apiKeyGemini)) {
            $respuesta = $this->consultarGemini($consulta, $analisis);
            if ($respuesta) {
                return response()->json(['modo' => 'online', 'resultado' => $respuesta, 'analisis' => $analisis]);
            }
        }

        // 3. Modo offline — análisis local
        $respuestaInteligente = $this->generarRespuestaInteligente($consulta, $analisis);
        return response()->json([
            'modo'      => 'offline',
            'resultado' => $respuestaInteligente,
            'analisis'  => $analisis,
        ]);
    }


    private function consultarGemini(string $consulta, array $analisis): ?string
    {
        try {
            $contexto = $this->formatearContexto($analisis);
            $apiKey   = env('GEMINI_API_KEY');

            $systemInstruction =
                "Eres NATURA, una inteligencia artificial avanzada integrada al sistema NATURACOR. " .
                "Puedes responder CUALQUIER tipo de pregunta en español: ciencia, salud, tecnología, marketing, " .
                "recetas, consejos de vida, matemáticas, historia, o cualquier otro tema. " .
                "Cuando la pregunta esté relacionada con el negocio, usa los datos reales disponibles. " .
                "Cuando sea una pregunta general, responde de forma completa, clara y útil en español. " .
                "Datos actuales del negocio NATURACOR:\n{$contexto}";

            $response = Http::withOptions([
                'verify'          => false,
                'connect_timeout' => 10,
                'timeout'         => 30,
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
            \Illuminate\Support\Facades\Log::error('Gemini API error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);
            return null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gemini exception: ' . $e->getMessage());
            return null;
        }
    }

    private function consultarGroq(string $consulta, array $analisis): ?string
    {
        try {
            $contexto = $this->formatearContexto($analisis);
            $systemPrompt =
                "Eres NATURA, una inteligencia artificial avanzada integrada al sistema NATURACOR (tienda de productos naturales en Perú). " .
                "Puedes responder CUALQUIER tipo de pregunta en español: ciencia, salud, tecnología, marketing, " .
                "recetas, historia, matemáticas o cualquier otro tema. " .
                "Cuando la pregunta esté relacionada con el negocio, usa los datos reales disponibles. " .
                "Cuando sea una pregunta general, responde de forma completa, clara y útil. " .
                "Siempre responde en español.\n\n" .
                "Datos actuales del negocio NATURACOR (úsalos si son relevantes):\n{$contexto}";

            $certPath = 'C:/xampp/php/cacert.pem';
            $verifyOpt = file_exists($certPath) ? $certPath : false;

            $response = Http::withOptions([
                'verify'          => $verifyOpt,
                'connect_timeout' => 10,
                'timeout'         => 30,
                'curl'            => [
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT        => 30,
                ],
            ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
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
            \Illuminate\Support\Facades\Log::error('Groq error', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            return null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Groq exception: ' . $e->getMessage());
            return null;
        }
    }

    private function consultarOpenAI(string $consulta, array $analisis): ?string
    {
        try {
            $contexto = $this->formatearContexto($analisis);
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . config('naturacor.openai_api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => "Eres un asistente experto en negocios de productos naturales en Perú. Analiza los datos y responde en español. Datos: {$contexto}"],
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

    private function generarRespuestaInteligente(string $consulta, array $analisis): string
    {
        $consulta = strtolower($consulta);
        $kw = fn($words) => collect($words)->some(fn($w) => str_contains($consulta, $w));

        $lineas = ["📊 **Análisis de NATURACOR** — " . now()->format('d/m/Y H:i'), ""];

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
        ];
    }

    private function formatearContexto(array $analisis): string
    {
        return "Ventas hoy: {$analisis['ventas_hoy']['count']} (S/{$analisis['ventas_hoy']['total']}). " .
               "Esta semana: {$analisis['ventas_semana']['count']} (S/{$analisis['ventas_semana']['total']}). " .
               "Este mes: {$analisis['ventas_mes']['count']} (S/{$analisis['ventas_mes']['total']}). " .
               "Productos con stock bajo: {$analisis['stock_critico']->count()}. " .
               "Clientes registrados: {$analisis['clientes_total']}.";
    }

    private function verificarConexion(): bool
    {
        try {
            Http::timeout(3)->get('https://www.google.com');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
