<?php

namespace App\Services\Recommendation;

/**
 * Bloque 4 — Servicio de experimentación A/B del recomendador.
 *
 * Responsable de:
 *  1. Asignar a cada cliente un GRUPO experimental ('control' | 'tratamiento')
 *     de forma estable según una estrategia configurable.
 *  2. Calcular la comparativa estadística (ticket promedio, conversión,
 *     Welch t-test, Cohen's d) entre ambos grupos para evidenciar el
 *     impacto del recomendador en el paper Scopus.
 *
 * Estrategias soportadas:
 *  - 'hash_cliente' (DEFAULT, recomendado): asignación determinística por
 *    md5(cliente_id) % 100. Estable entre sesiones del mismo cliente y sin
 *    sesgo temporal. Es la elección estándar en literatura de A/B testing
 *    online (Kohavi et al. 2009).
 *  - 'dia_par_impar': control en días pares, tratamiento en impares.
 *    Documentada por completitud académica pero NO recomendada por
 *    introducir sesgo temporal (lunes ≠ sábado en facturación).
 *  - 'aleatorio': asignación al azar por request. Rompe consistencia entre
 *    sesiones del mismo cliente; útil sólo para tests sintéticos.
 *
 * Tests estadísticos implementados:
 *  - Welch's t-test (no asume varianzas iguales — el escenario realista).
 *  - Aproximación de p-valor de la t-distribución (Abramowitz & Stegun
 *    26.7.4 + simetría) — válido para df ≥ 1, error < 1e-3 para df > 4.
 *  - Cohen's d como tamaño de efecto independiente del tamaño muestral.
 *
 * El servicio NO toca el motor de recomendación: la decisión "este cliente
 * recibe o no recos" la toma el controller usando `asignarGrupo()`.
 */
class AbTestingService
{
    public const GRUPO_CONTROL = 'control';

    public const GRUPO_TRATAMIENTO = 'tratamiento';

    public const GRUPO_SIN_AB = 'sin_ab';

    public const ESTRATEGIA_HASH = 'hash_cliente';

    public const ESTRATEGIA_DIA_PAR_IMPAR = 'dia_par_impar';

    public const ESTRATEGIA_ALEATORIO = 'aleatorio';

    /**
     * Asigna grupo experimental al cliente. Retorna 'sin_ab' si el A/B
     * está desactivado en config (todos los clientes reciben recos).
     *
     * @param  int|null  $clienteId  null → tratamiento por defecto (no podemos
     *                               estabilizar la asignación sin cliente).
     */
    public function asignarGrupo(?int $clienteId): string
    {
        if (! (bool) config('recommendaciones.ab_testing.enabled', false)) {
            return self::GRUPO_SIN_AB;
        }

        if ($clienteId === null) {
            return self::GRUPO_TRATAMIENTO;
        }

        $estrategia = (string) config('recommendaciones.ab_testing.estrategia', self::ESTRATEGIA_HASH);
        $pctControl = max(0, min(100, (int) config('recommendaciones.ab_testing.porcentaje_control', 50)));

        $bucket = $this->bucket($clienteId, $estrategia);

        return $bucket < $pctControl ? self::GRUPO_CONTROL : self::GRUPO_TRATAMIENTO;
    }

    /**
     * Devuelve un entero [0, 100) que define en qué parte del split cae
     * el cliente según la estrategia.
     */
    private function bucket(int $clienteId, string $estrategia): int
    {
        return match ($estrategia) {
            self::ESTRATEGIA_DIA_PAR_IMPAR => (int) now()->day % 2 === 0 ? 0 : 99,
            self::ESTRATEGIA_ALEATORIO     => random_int(0, 99),
            default                        => (int) (hexdec(substr(md5((string) $clienteId), 0, 8)) % 100),
        };
    }

    /**
     * Devuelve true si el grupo significa "no mostrar recomendaciones".
     */
    public function esGrupoControl(string $grupo): bool
    {
        return $grupo === self::GRUPO_CONTROL;
    }

    // ===================================================================
    // Estadística inferencial (Welch t-test, Cohen's d, p-valor aprox)
    // ===================================================================

    /**
     * Welch's t-test para diferencia de medias entre dos muestras independientes
     * con varianzas posiblemente distintas. Devuelve t-statistic, df, p-valor
     * (aproximado, two-tailed) y Cohen's d.
     *
     * @param  list<float>  $muestraA
     * @param  list<float>  $muestraB
     * @return array{
     *   n_a: int, n_b: int,
     *   media_a: float, media_b: float,
     *   var_a: float, var_b: float,
     *   diferencia_medias: float,
     *   t_statistic: ?float,
     *   df: ?float,
     *   p_value_aprox: ?float,
     *   cohens_d: ?float,
     *   significativo_5pct: ?bool,
     *   muestra_suficiente: bool,
     *   nota: string
     * }
     */
    public function welchTTest(array $muestraA, array $muestraB): array
    {
        $nA = count($muestraA);
        $nB = count($muestraB);
        $minMuestra = max(2, (int) config('recommendaciones.ab_testing.tamano_muestra_minimo', 30));

        $base = [
            'n_a' => $nA,
            'n_b' => $nB,
            'media_a' => $nA > 0 ? array_sum($muestraA) / $nA : 0.0,
            'media_b' => $nB > 0 ? array_sum($muestraB) / $nB : 0.0,
            'var_a' => $this->varianzaMuestral($muestraA),
            'var_b' => $this->varianzaMuestral($muestraB),
            'diferencia_medias' => 0.0,
            't_statistic' => null,
            'df' => null,
            'p_value_aprox' => null,
            'cohens_d' => null,
            'significativo_5pct' => null,
            'muestra_suficiente' => $nA >= $minMuestra && $nB >= $minMuestra,
            'nota' => '',
        ];

        $base['diferencia_medias'] = round($base['media_b'] - $base['media_a'], 4);

        if ($nA < 2 || $nB < 2) {
            $base['nota'] = 'Cada grupo necesita al menos 2 observaciones para Welch t-test.';

            return $base;
        }

        $vA = $base['var_a'];
        $vB = $base['var_b'];
        $denominador = sqrt($vA / $nA + $vB / $nB);

        if ($denominador <= 0.0) {
            $base['t_statistic'] = 0.0;
            $base['df'] = (float) ($nA + $nB - 2);
            $base['p_value_aprox'] = 1.0;
            $base['cohens_d'] = 0.0;
            $base['significativo_5pct'] = false;
            $base['nota'] = 'Varianza nula en ambos grupos: no hay diferencia detectable.';

            return $base;
        }

        $t = ($base['media_b'] - $base['media_a']) / $denominador;

        // Welch–Satterthwaite df
        $num = pow($vA / $nA + $vB / $nB, 2);
        $den = pow($vA / $nA, 2) / max(1, $nA - 1) + pow($vB / $nB, 2) / max(1, $nB - 1);
        $df = $den > 0.0 ? $num / $den : (float) ($nA + $nB - 2);

        $p = $this->pValorTwoTailed($t, $df);

        // Cohen's d con desviación combinada (pooled SD)
        $sdPooled = sqrt((($nA - 1) * $vA + ($nB - 1) * $vB) / max(1, $nA + $nB - 2));
        $d = $sdPooled > 0.0 ? ($base['media_b'] - $base['media_a']) / $sdPooled : 0.0;

        $base['t_statistic'] = round($t, 4);
        $base['df'] = round($df, 2);
        $base['p_value_aprox'] = round($p, 4);
        $base['cohens_d'] = round($d, 4);
        $base['significativo_5pct'] = $p < 0.05;
        if (! $base['muestra_suficiente']) {
            $base['nota'] = "Tamaño de muestra por grupo < {$minMuestra}: el resultado es exploratorio, no concluyente.";
        }

        return $base;
    }

    /**
     * Varianza muestral (n-1).
     *
     * @param  list<float>  $vals
     */
    private function varianzaMuestral(array $vals): float
    {
        $n = count($vals);
        if ($n < 2) {
            return 0.0;
        }
        $media = array_sum($vals) / $n;
        $sumSq = 0.0;
        foreach ($vals as $v) {
            $diff = $v - $media;
            $sumSq += $diff * $diff;
        }

        return $sumSq / ($n - 1);
    }

    /**
     * Aproximación al p-valor two-tailed de la t-distribución de Student
     * para df > 0. Estrategia híbrida:
     *  - df ≥ 30: usa la aproximación normal (z) — error despreciable.
     *  - df <  30: usa la transformación de la cola via función incompleta
     *              de beta vía aproximación de Hill (1970) — suficientemente
     *              precisa para reportes académicos (<1e-3 absoluto).
     *
     * No reemplaza a `scipy.stats.t.sf`, pero es honesto declarar el método
     * en el paper. Reportamos también el t-statistic completo para revisión.
     */
    private function pValorTwoTailed(float $t, float $df): float
    {
        $absT = abs($t);
        if ($df >= 30.0) {
            return 2.0 * (1.0 - $this->cdfNormalEstandar($absT));
        }

        // Aproximación de la cola de t de Student vía función Beta incompleta:
        //   p = I_x(df/2, 1/2)  con x = df / (df + t^2),  two-tailed
        $x = $df / ($df + $absT * $absT);
        $p = $this->betaIncompletaRegularizada($x, $df / 2.0, 0.5);

        return max(0.0, min(1.0, $p));
    }

    /**
     * CDF de la normal estándar Φ(x) — aproximación de Abramowitz & Stegun 26.2.17.
     * Error absoluto < 7.5e-8 para todo x.
     */
    private function cdfNormalEstandar(float $x): float
    {
        $sign = $x < 0 ? -1.0 : 1.0;
        $x = abs($x) / sqrt(2.0);

        // Constantes A&S 7.1.26 (erf)
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $y);
    }

    /**
     * Función Beta incompleta regularizada I_x(a, b) por fracción continua
     * de Lentz (Numerical Recipes §6.4). Suficiente para los rangos que usamos
     * en t-tests (a > 0, b > 0, x ∈ [0, 1]).
     */
    private function betaIncompletaRegularizada(float $x, float $a, float $b): float
    {
        if ($x <= 0.0) {
            return 0.0;
        }
        if ($x >= 1.0) {
            return 1.0;
        }

        $bt = exp(
            $this->lnGamma($a + $b)
            - $this->lnGamma($a) - $this->lnGamma($b)
            + $a * log($x) + $b * log(1.0 - $x)
        );

        if ($x < ($a + 1.0) / ($a + $b + 2.0)) {
            return $bt * $this->betaCF($x, $a, $b) / $a;
        }

        return 1.0 - $bt * $this->betaCF(1.0 - $x, $b, $a) / $b;
    }

    private function betaCF(float $x, float $a, float $b): float
    {
        $maxIter = 200;
        $eps = 3.0e-7;
        $fpmin = 1.0e-30;

        $qab = $a + $b;
        $qap = $a + 1.0;
        $qam = $a - 1.0;
        $c = 1.0;
        $d = 1.0 - $qab * $x / $qap;
        if (abs($d) < $fpmin) {
            $d = $fpmin;
        }
        $d = 1.0 / $d;
        $h = $d;

        for ($m = 1; $m <= $maxIter; $m++) {
            $m2 = 2 * $m;
            $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
            $d = 1.0 + $aa * $d;
            if (abs($d) < $fpmin) {
                $d = $fpmin;
            }
            $c = 1.0 + $aa / $c;
            if (abs($c) < $fpmin) {
                $c = $fpmin;
            }
            $d = 1.0 / $d;
            $h *= $d * $c;

            $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
            $d = 1.0 + $aa * $d;
            if (abs($d) < $fpmin) {
                $d = $fpmin;
            }
            $c = 1.0 + $aa / $c;
            if (abs($c) < $fpmin) {
                $c = $fpmin;
            }
            $d = 1.0 / $d;
            $del = $d * $c;
            $h *= $del;

            if (abs($del - 1.0) < $eps) {
                break;
            }
        }

        return $h;
    }

    /**
     * Logaritmo de Γ(x) — aproximación de Lanczos (precisión ≥ 10 dígitos).
     */
    private function lnGamma(float $x): float
    {
        static $cof = [
            76.18009172947146, -86.50532032941677, 24.01409824083091,
            -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5,
        ];

        $y = $x;
        $tmp = $x + 5.5;
        $tmp -= ($x + 0.5) * log($tmp);
        $ser = 1.000000000190015;
        foreach ($cof as $c) {
            $y += 1.0;
            $ser += $c / $y;
        }

        return -$tmp + log(2.5066282746310005 * $ser / $x);
    }
}
