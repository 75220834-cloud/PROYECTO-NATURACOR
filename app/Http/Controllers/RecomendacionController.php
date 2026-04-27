<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\RecomendacionEvento;
use App\Services\Recommendation\AbTestingService;
use App\Services\Recommendation\MetricsService;
use App\Services\Recommendation\RecomendacionEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API interna del módulo de recomendación (Fase 1).
 *
 * Ejemplo de uso (autenticado, mismo dominio de sesión):
 *   GET /api/recomendaciones/12?limite=8
 *   Authorization: sesión web (cookie) o Sanctum en el futuro.
 *
 * Respuesta JSON: cliente_id, perfil_filas, items[], meta{ respuesta_desde_cache, perfil_recalculado, reco_sesion_id }.
 *
 * Query opcionales:
 *   - limite (1..max)
 *   - refresh=1  Fuerza recálculo de perfil y omite caché de la respuesta (uso puntual, no abusar).
 *
 * Métricas (tesis): POST /api/recomendaciones/evento para registrar clic o agregada desde el POS.
 */
class RecomendacionController extends Controller
{
    public function __construct(
        private readonly RecomendacionEngine $engine,
        private readonly MetricsService $metrics,
        private readonly AbTestingService $ab,
    ) {}

    public function show(Request $request, Cliente $cliente): JsonResponse
    {
        Log::info('RECO SHOW CALLED', [
            'cliente_id' => (int) $cliente->id,
            'timestamp' => now()->toIso8601String(),
            'full_url' => $request->fullUrl(),
            'refresh' => $request->boolean('refresh'),
            'user_id' => (int) $request->user()->id,
            'sucursal_id' => $request->user()->sucursal_id,
            'ip' => $request->ip(),
        ]);

        $limiteDefault = (int) config('recommendaciones.limite_default', 10);
        $limiteMax = (int) config('recommendaciones.limite_max', 30);

        $limite = (int) $request->query('limite', $limiteDefault);
        $limite = max(1, min($limite, $limiteMax));

        // BUG 1 FIX: Admin sin sucursal asignada caía en "vista global".
        // Con el fallback, ve sugerencias y trending de la sucursal por defecto (1).
        $sucursalId = $request->user()->sucursal_id ?? 1;
        $forzar = $request->boolean('refresh');

        // [BLOQUE 4] Asignación A/B antes de invocar al motor: si el cliente
        // cae en grupo control con A/B activo, devolvemos respuesta vacía
        // para que el POS no muestre recos. Eso permite medir el contrafactual.
        $grupoAb = $this->ab->asignarGrupo((int) $cliente->id);
        if ($this->ab->esGrupoControl($grupoAb)) {
            return response()->json($this->respuestaControl((int) $cliente->id, $grupoAb));
        }

        // [BLOQUE 2 FASE B] Carrito actual del POS (cross-sell colaborativo).
        // Acepta CSV: ?producto_ids=3,7,12  (más simple que array y compatible con cache fingerprint).
        // Defensa: máximo 50 IDs para evitar payloads abusivos; descarta IDs inválidos.
        $cestaActual = $this->parsearCarrito($request->query('producto_ids'));

        $payload = $this->engine->recomendar($cliente, $sucursalId, $limite, $forzar, $cestaActual);

        // Etiquetar el payload con el grupo experimental para trazabilidad
        // y para que el frontend pueda registrar correctamente eventos del POS.
        $payload['meta']['grupo_ab'] = $grupoAb;

        $desdeCache = (bool) ($payload['meta']['respuesta_desde_cache'] ?? false);
        $recoSesionId = $payload['meta']['reco_sesion_id'] ?? null;

        if (! $desdeCache && $recoSesionId && ! empty($payload['items'])) {
            $this->metrics->registrarMostradas(
                $recoSesionId,
                (int) $cliente->id,
                $sucursalId,
                (int) $request->user()->id,
                $payload['items'],
                $grupoAb
            );
        }

        return response()->json($payload);
    }

    /**
     * Respuesta canónica para clientes asignados al grupo control.
     * Estructura compatible con el cliente JS del POS pero con items=[]
     * y bandera explícita meta.grupo_ab='control' para auditoría.
     *
     * @return array<string, mixed>
     */
    private function respuestaControl(int $clienteId, string $grupoAb): array
    {
        return [
            'cliente_id' => $clienteId,
            'perfil_filas' => 0,
            'items' => [],
            'meta' => [
                'respuesta_desde_cache' => false,
                'perfil_recalculado' => false,
                'reco_sesion_id' => null,
                'cesta_size' => 0,
                'coocurrencia_activa' => false,
                'grupo_ab' => $grupoAb,
            ],
        ];
    }

    public function registrarEvento(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reco_sesion_id' => 'required|uuid',
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'accion' => 'required|string|in:'.RecomendacionEvento::ACCION_AGREGADA.','.RecomendacionEvento::ACCION_CLIC,
        ]);

        try {
            $ok = $this->metrics->registrarInteraccionPos(
                $data['accion'],
                $data['reco_sesion_id'],
                (int) $data['cliente_id'],
                (int) $data['producto_id'],
                $request->user()->sucursal_id,
                (int) $request->user()->id,
                $this->ab->asignarGrupo((int) $data['cliente_id'])
            );

            if (! $ok) {
                return response()->json(['ok' => false, 'message' => 'Evento descartado por inconsistencia de sesión.'], 422);
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Error registrando evento de recomendación', [
                'error' => $e->getMessage(),
                'payload' => $data,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json(['ok' => false, 'message' => 'No se pudo registrar el evento.'], 500);
        }
    }

    /**
     * Convierte un parámetro `producto_ids` (CSV o array) en una lista de enteros únicos.
     * Limita a 50 IDs y descarta cualquier valor no numérico o ≤ 0.
     *
     * @param  mixed  $raw
     * @return list<int>
     */
    private function parsearCarrito(mixed $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $items = is_array($raw) ? $raw : explode(',', (string) $raw);

        $ids = [];
        foreach ($items as $item) {
            $id = (int) trim((string) $item);
            if ($id > 0) {
                $ids[$id] = true;
                if (count($ids) >= 50) {
                    break;
                }
            }
        }

        return array_keys($ids);
    }
}
