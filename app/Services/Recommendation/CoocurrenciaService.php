<?php

namespace App\Services\Recommendation;

use App\Models\ProductoCoocurrencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bloque 2 — Filtrado colaborativo basado en co-ocurrencia (item-item CF).
 *
 * Computa, persiste y consulta una matriz de similitud producto↔producto
 * derivada de las "boletas/canastas" del histórico de ventas. Esta señal
 * convierte el sistema de un híbrido parcial (perfil + tendencia) en uno
 * completo (perfil + tendencia + colaborativo), tal como propone la literatura
 * clásica:
 *   - Sarwar et al. (2001) "Item-based collaborative filtering recommendation algorithms"
 *   - Bouma (2009) "Normalized (Pointwise) Mutual Information in Collocation Extraction"
 *
 * Métricas implementadas:
 *  - Jaccard: J(A,B) = co(A,B) / (n_A + n_B − co(A,B))                  ∈ [0, 1]
 *  - PMI:    log( P(A,B) / (P(A) · P(B)) )                              ∈ ℝ
 *  - NPMI:   PMI(A,B) / -log P(A,B)                                     ∈ [-1, 1]
 *
 * Diseño:
 *  - Cómputo offline (artisan command), no en cada request.
 *  - Truncate + insert en batch dentro de transacción.
 *  - Pares ordenados (a < b) para evitar duplicados (a,b)/(b,a).
 *  - Filtro de ruido: descarta pares con co_count < min_co_count.
 *
 * Esta clase es PURA respecto a config/cache: depende solo de DB y del modelo.
 * Eso facilita los tests unitarios (no requiere Laravel facades de cache).
 */
class CoocurrenciaService
{
    public const METRICA_JACCARD = 'jaccard';

    public const METRICA_NPMI = 'npmi';

    /**
     * Reconstruye la matriz completa de co-ocurrencias.
     * Lee del histórico de ventas completadas dentro de la ventana, calcula
     * Jaccard y NPMI para cada par y reemplaza atómicamente la tabla.
     *
     * @param  int|null     $diasVentana  Días hacia atrás. null → usa config.
     * @param  int|null     $minCoCount   Mínimo de co-ocurrencias. null → usa config.
     * @param  string|null  $metrica      'jaccard' | 'npmi'. null → usa config.
     * @return array{
     *   transacciones: int,
     *   productos: int,
     *   pares_calculados: int,
     *   pares_persistidos: int,
     *   metrica: string,
     *   dias_ventana: int,
     *   computed_at: string,
     * }
     */
    public function recomputar(
        ?int $diasVentana = null,
        ?int $minCoCount = null,
        ?string $metrica = null,
    ): array {
        $diasVentana = $diasVentana ?? (int) config('recommendaciones.cooccurrencia.dias_ventana', 90);
        $minCoCount = $minCoCount ?? (int) config('recommendaciones.cooccurrencia.min_co_count', 2);
        $metrica = strtolower($metrica ?? (string) config('recommendaciones.cooccurrencia.metrica', self::METRICA_JACCARD));

        if (! in_array($metrica, [self::METRICA_JACCARD, self::METRICA_NPMI], true)) {
            throw new \InvalidArgumentException("Métrica desconocida: {$metrica}. Use 'jaccard' o 'npmi'.");
        }

        $diasVentana = max(1, $diasVentana);
        $minCoCount = max(1, $minCoCount);

        $now = Carbon::now();
        $desde = $now->copy()->subDays($diasVentana);

        $canastas = $this->cargarCanastas($desde);

        if ($canastas->isEmpty()) {
            DB::transaction(function () use ($metrica, $diasVentana) {
                ProductoCoocurrencia::query()->delete();
            });

            return [
                'transacciones' => 0,
                'productos' => 0,
                'pares_calculados' => 0,
                'pares_persistidos' => 0,
                'metrica' => $metrica,
                'dias_ventana' => $diasVentana,
                'computed_at' => $now->toDateTimeString(),
            ];
        }

        [$count, $coCount, $totalTransacciones] = $this->acumularEstadisticas($canastas);

        $rows = $this->calcularScores(
            $count,
            $coCount,
            $totalTransacciones,
            $minCoCount,
            $metrica,
            $diasVentana,
            $now,
        );

        $persistidos = $this->reemplazarTabla($rows);

        return [
            'transacciones' => $totalTransacciones,
            'productos' => count($count),
            'pares_calculados' => $this->contarPares($coCount),
            'pares_persistidos' => $persistidos,
            'metrica' => $metrica,
            'dias_ventana' => $diasVentana,
            'computed_at' => $now->toDateTimeString(),
        ];
    }

    /**
     * Devuelve los top-K productos co-comprados con un producto dado, ordenados
     * por la métrica activa (campo `score` denormalizado en la tabla).
     *
     * @param  int    $productoId
     * @param  int    $limite
     * @param  float  $scoreMinimo  Umbral inferior (Jaccard ∈ [0,1], NPMI ∈ [-1,1]).
     * @return Collection<int, array{
     *   producto_id: int, score: float, score_jaccard: float, score_npmi: float, co_count: int
     * }>
     */
    public function productosRelacionados(int $productoId, int $limite = 10, float $scoreMinimo = 0.0): Collection
    {
        $limite = max(1, $limite);

        $rows = DB::table('producto_coocurrencias')
            ->where(function ($q) use ($productoId) {
                $q->where('producto_a_id', $productoId)
                    ->orWhere('producto_b_id', $productoId);
            })
            ->where('score', '>=', $scoreMinimo)
            ->orderByDesc('score')
            ->limit($limite)
            ->get([
                'producto_a_id',
                'producto_b_id',
                'score',
                'score_jaccard',
                'score_npmi',
                'co_count',
            ]);

        return $rows->map(function ($r) use ($productoId) {
            $relacionado = ((int) $r->producto_a_id === $productoId)
                ? (int) $r->producto_b_id
                : (int) $r->producto_a_id;

            return [
                'producto_id'   => $relacionado,
                'score'         => (float) $r->score,
                'score_jaccard' => (float) $r->score_jaccard,
                'score_npmi'    => (float) $r->score_npmi,
                'co_count'      => (int) $r->co_count,
            ];
        });
    }

    /**
     * Versión batch: dado un set de productos (p.ej. el carrito del POS),
     * devuelve un mapa `vecino_id => mejor_score` agregando todas las
     * señales: si A y B están en el carrito y ambos apuntan al producto C,
     * C recibe el MAX(score(A,C), score(B,C)).
     *
     * Se descartan los propios productos del carrito.
     *
     * @param  list<int>  $productoIds
     * @param  int        $limitePorBase
     * @param  float      $scoreMinimo
     * @return Collection<int, float>  vecino_id => score
     */
    public function vecindarioBatch(array $productoIds, int $limitePorBase = 20, float $scoreMinimo = 0.0): Collection
    {
        $productoIds = array_values(array_unique(array_map('intval', $productoIds)));
        if ($productoIds === []) {
            return collect();
        }

        $rows = DB::table('producto_coocurrencias')
            ->where(function ($q) use ($productoIds) {
                $q->whereIn('producto_a_id', $productoIds)
                    ->orWhereIn('producto_b_id', $productoIds);
            })
            ->where('score', '>=', $scoreMinimo)
            ->orderByDesc('score')
            ->limit(max(1, $limitePorBase) * count($productoIds))
            ->get(['producto_a_id', 'producto_b_id', 'score']);

        $excluir = array_flip($productoIds);
        $mejor = [];

        foreach ($rows as $r) {
            $a = (int) $r->producto_a_id;
            $b = (int) $r->producto_b_id;
            $vecino = isset($excluir[$a]) ? $b : $a;

            if (isset($excluir[$vecino])) {
                continue;
            }

            $score = (float) $r->score;
            if (! isset($mejor[$vecino]) || $mejor[$vecino] < $score) {
                $mejor[$vecino] = $score;
            }
        }

        arsort($mejor);

        return collect($mejor);
    }

    /**
     * Carga todas las canastas (set único de productos por venta completada)
     * dentro de la ventana. Devuelve venta_id => Collection<producto_id>.
     *
     * @return Collection<int, Collection<int,int>>
     */
    protected function cargarCanastas(Carbon $desde): Collection
    {
        return DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereNotNull('detalle_ventas.producto_id')
            ->where('ventas.created_at', '>=', $desde)
            ->select('detalle_ventas.venta_id as v', 'detalle_ventas.producto_id as p')
            ->get()
            ->groupBy('v')
            ->map(fn ($items) => $items->pluck('p')
                ->map(fn ($pid) => (int) $pid)
                ->unique()
                ->sort()
                ->values());
    }

    /**
     * Recorre canastas y acumula:
     *  - count[p]            : apariciones individuales
     *  - coCount[a][b]       : co-ocurrencias del par ordenado (a < b)
     *  - totalTransacciones  : N (todas las canastas, incluso de un solo ítem)
     *
     * @param  Collection<int, Collection<int,int>>  $canastas
     * @return array{0: array<int,int>, 1: array<int, array<int,int>>, 2: int}
     */
    protected function acumularEstadisticas(Collection $canastas): array
    {
        $count = [];
        $coCount = [];
        $totalTransacciones = 0;

        foreach ($canastas as $items) {
            $totalTransacciones++;
            $arr = $items->all();
            $n = count($arr);
            for ($i = 0; $i < $n; $i++) {
                $pid = $arr[$i];
                $count[$pid] = ($count[$pid] ?? 0) + 1;
                for ($j = $i + 1; $j < $n; $j++) {
                    $a = $arr[$i];
                    $b = $arr[$j];
                    $coCount[$a][$b] = ($coCount[$a][$b] ?? 0) + 1;
                }
            }
        }

        return [$count, $coCount, $totalTransacciones];
    }

    /**
     * Calcula Jaccard y NPMI para cada par y arma las filas listas para insert.
     *
     * @param  array<int,int>                $count
     * @param  array<int, array<int,int>>    $coCount
     * @return list<array<string,mixed>>
     */
    protected function calcularScores(
        array $count,
        array $coCount,
        int $totalTransacciones,
        int $minCoCount,
        string $metrica,
        int $diasVentana,
        Carbon $now,
    ): array {
        $rows = [];
        $nowStr = $now->toDateTimeString();
        $N = max(1, $totalTransacciones);

        foreach ($coCount as $a => $bs) {
            foreach ($bs as $b => $co) {
                if ($co < $minCoCount) {
                    continue;
                }
                $cA = $count[$a] ?? 0;
                $cB = $count[$b] ?? 0;
                if ($cA <= 0 || $cB <= 0) {
                    continue;
                }

                $denomJ = $cA + $cB - $co;
                $jaccard = $denomJ > 0 ? $co / $denomJ : 0.0;

                $pAB = $co / $N;
                $pA = $cA / $N;
                $pB = $cB / $N;
                if ($pAB > 0.0 && $pA > 0.0 && $pB > 0.0) {
                    $pmi = log($pAB / ($pA * $pB), 2);
                    $hAB = -log($pAB, 2);
                    $npmi = $hAB > 0.0 ? ($pmi / $hAB) : 0.0;
                } else {
                    $npmi = 0.0;
                }

                $score = $metrica === self::METRICA_NPMI ? $npmi : $jaccard;

                $rows[] = [
                    'producto_a_id' => $a,
                    'producto_b_id' => $b,
                    'co_count' => $co,
                    'count_a' => $cA,
                    'count_b' => $cB,
                    'total_transacciones' => $totalTransacciones,
                    'score_jaccard' => round($jaccard, 6),
                    'score_npmi' => round($npmi, 6),
                    'metrica_principal' => $metrica,
                    'score' => round($score, 6),
                    'dias_ventana' => $diasVentana,
                    'computed_at' => $nowStr,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
        }

        return $rows;
    }

    /**
     * Reemplaza atómicamente toda la tabla por las nuevas filas.
     * Usa delete+insert en transacción (compatible con SQLite/MySQL/Postgres).
     *
     * @param  list<array<string,mixed>>  $rows
     */
    protected function reemplazarTabla(array $rows): int
    {
        $persistidos = 0;
        DB::transaction(function () use ($rows, &$persistidos) {
            ProductoCoocurrencia::query()->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                ProductoCoocurrencia::insert($chunk);
                $persistidos += count($chunk);
            }
        });

        if ($persistidos === 0 && $rows !== []) {
            Log::warning('CoocurrenciaService: hubo filas para insertar pero ninguna se persistió.');
        }

        return $persistidos;
    }

    /**
     * @param  array<int, array<int,int>>  $coCount
     */
    protected function contarPares(array $coCount): int
    {
        $total = 0;
        foreach ($coCount as $bs) {
            $total += count($bs);
        }

        return $total;
    }
}
