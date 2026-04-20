<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Enfermedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IAController extends Controller
{
    public function index()
    {
        $analisis = $this->analizarNegocio();
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

        return view('ia.index', compact('analisis', 'modoOnline'));
    }

    public function analizar(Request $request)
    {
        $consulta = $request->consulta ?? 'Analiza el negocio y dame recomendaciones';
        $analisis = $this->analizarNegocio();

        // 1. Intentar Groq primero (Llama 3 — api.groq.com)
        $apiKeyGroq = config('naturacor.groq_api_key');
        Log::info('IA analizar - Groq check', [
            'key_present' => !empty($apiKeyGroq),
            'key_length'  => strlen($apiKeyGroq ?? ''),
        ]);
        if (!empty($apiKeyGroq)) {
            $respuesta = $this->consultarGroq($consulta, $analisis);
            if ($respuesta) {
                return response()->json(['modo' => 'online', 'resultado' => $respuesta, 'analisis' => $analisis]);
            }
            Log::warning('IA analizar - Groq returned null, falling through to Gemini');
        }

        // 2. Intentar Gemini como alternativa
        $apiKeyGemini = config('naturacor.gemini_api_key');
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
            $apiKey   = config('naturacor.gemini_api_key');

            $systemInstruction =
                "Eres NATURA, una inteligencia artificial avanzada integrada al sistema NATURACOR. " .
                "Puedes responder CUALQUIER tipo de pregunta en español: ciencia, salud, tecnología, marketing, " .
                "recetas, consejos de vida, matemáticas, historia, o cualquier otro tema. " .
                "Cuando la pregunta esté relacionada con el negocio, usa los datos reales disponibles. " .
                "Cuando sea una pregunta general, responde de forma completa, clara y útil en español. " .
                "Datos actuales del negocio NATURACOR:\n{$contexto}";

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

    private function formatearContexto(array $analisis): string
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

        return $ctx;
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
