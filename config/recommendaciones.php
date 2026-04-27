<?php

/**
 * Parámetros del módulo de recomendación (Fase 1).
 * Ajustables por .env sin tocar código.
 */
return [

    /** Días hacia atrás para considerar compras al construir el perfil */
    'ventana_dias' => (int) env('REC_VENTANA_DIAS', 365),

    /**
     * Decaimiento exponencial por antigüedad de la venta: peso_linea *= exp(-lambda * días).
     * Valores mayores = las compras antiguas pesan menos.
     */
    'decaimiento_lambda' => (float) env('REC_LAMBDA', 0.008),

    /** Cuántas enfermedades con mayor score se usan para buscar productos candidatos */
    'top_enfermedades' => (int) env('REC_TOP_ENFERMEDADES', 10),

    /** Ventana para “más vendidos recientes” por sucursal (ventas completadas de esa tienda) */
    'trending_dias' => (int) env('REC_TRENDING_DIAS', 14),

    /** Evita recomendar productos comprados por el cliente en los últimos N días */
    'excluir_comprados_dias' => (int) env('REC_EXCLUIR_COMPRADOS_DIAS', 0),

    /** Peso relativo del componente perfil vs tendencia (sin ML; solo fusión lineal) */
    'peso_perfil' => (float) env('REC_PESO_PERFIL', 1.0),
    'peso_trending' => (float) env('REC_PESO_TRENDING', 0.45),

    'limite_default' => (int) env('REC_LIMITE_DEFAULT', 10),
    'limite_max' => (int) env('REC_LIMITE_MAX', 30),

    /**
     * Horas mínimas entre recálculos del perfil (tabla cliente_perfil_afinidad).
     * Reduce carga en cada request al endpoint de recomendaciones.
     */
    'perfil_horas_validez' => (int) env('REC_PERFIL_HORAS', 6),

    /**
     * Minutos de caché del JSON de recomendaciones por cliente/sucursal/límite.
     */
    'cache_minutos' => (int) env('REC_CACHE_MINUTOS', 10),

    /** Horas hacia atrás desde la venta para atribuir "comprada" a una exposición previa del mismo producto */
    'metricas_lookback_horas' => (int) env('REC_METRICAS_LOOKBACK_HORAS', 72),

    /** k para precision@k (debe alinearse con el límite típico mostrado en POS, ej. 6) */
    'metricas_precision_k' => (int) env('REC_METRICAS_K', 6),

    /** Días por defecto en el dashboard de métricas */
    'metricas_dashboard_dias' => (int) env('REC_METRICAS_DASHBOARD_DIAS', 30),

    /**
     * [BUG 2 FIX] Score base [0,1] para padecimientos declarados explícitamente
     * por el cliente en `cliente_padecimientos`.
     *
     * Funciona como FLOOR (piso): el motor garantiza que toda enfermedad declarada
     * tenga al menos este score, aunque el cliente no haya comprado nunca productos
     * relacionados. Permite generar recomendaciones útiles para clientes nuevos
     * con diagnóstico previo (caso clave de la tesis).
     */
    'padecimiento_score_base' => (float) env('REC_PADECIMIENTO_SCORE_BASE', 0.80),

    /**
     * [BLOQUE 2] Filtrado colaborativo basado en co-ocurrencia de productos.
     *
     * Tres parámetros principales:
     *  - dias_ventana:        cuántos días hacia atrás considerar para computar la matriz.
     *  - min_co_count:        mínimo de transacciones donde A y B aparecen juntos para
     *                         considerar el par estadísticamente significativo (filtra ruido).
     *  - metrica:             'jaccard' (sesgo a productos populares) o 'npmi' (sesgo a
     *                         asociaciones más sorprendentes/específicas).
     *  - peso_en_fusion:      peso del componente colaborativo en la fusión final del engine
     *                         (junto con peso_perfil y peso_trending).
     *  - top_k_persistir:     top-K vecinos por producto que se usarán al consultar online
     *                         (no afecta lo que se persiste en la matriz).
     *  - boost_carrito:       multiplicador adicional cuando el producto candidato es vecino
     *                         de algo que el usuario ya tiene en el carrito (cross-sell POS).
     */
    'cooccurrencia' => [
        'dias_ventana'    => (int)    env('REC_COO_DIAS_VENTANA', 90),
        'min_co_count'    => (int)    env('REC_COO_MIN_COCOUNT', 2),
        'metrica'         => (string) env('REC_COO_METRICA', 'jaccard'),
        'peso_en_fusion'  => (float)  env('REC_COO_PESO', 0.35),
        'top_k_persistir' => (int)    env('REC_COO_TOP_K', 50),
        'boost_carrito'   => (float)  env('REC_COO_BOOST_CARRITO', 1.5),
    ],

    /**
     * [BLOQUE 3] Jobs nocturnos del motor de recomendación.
     *
     * Schedule registrado en `routes/console.php`:
     *   - perfiles_hora       (default 02:00) → ReconstruirPerfilesJob
     *   - cooccurrencia_hora  (default 02:30) → ReconstruirCoocurrenciaJob
     *
     * Ambos jobs son enable-ables por entorno: en testing puedes
     * desactivarlos vía REC_JOB_PERFILES_ENABLED=false para que el
     * scheduler no los dispare automáticamente.
     *
     * - perfiles_chunk: tamaño de página al iterar clientes activos en
     *                   ReconstruirPerfilesJob (evita OOM en bases grandes).
     * - cola:           nombre de la cola Laravel donde se encolan ambos
     *                   jobs. Útil para usar workers dedicados con
     *                   `php artisan queue:work --queue=recomendaciones`.
     */
    'jobs' => [
        'perfiles_enabled'      => (bool)   env('REC_JOB_PERFILES_ENABLED', true),
        'perfiles_hora'         => (string) env('REC_JOB_PERFILES_HORA', '02:00'),
        'perfiles_chunk'        => (int)    env('REC_JOB_PERFILES_CHUNK', 200),
        'cooccurrencia_enabled' => (bool)   env('REC_JOB_COO_ENABLED', true),
        'cooccurrencia_hora'    => (string) env('REC_JOB_COO_HORA', '02:30'),
        'cola'                  => (string) env('REC_JOB_COLA', 'default'),
    ],

    /**
     * [BLOQUE 4] Experimento A/B documentado — evidencia científica del impacto
     * del recomendador (crítico para artículo Scopus).
     *
     * - enabled:               true → la mitad (configurable) de clientes NO ve recos
     *                          (grupo control), permitiendo medir el efecto causal.
     * - estrategia:            'hash_cliente' (RECOMENDADO; estable e insesgada),
     *                          'dia_par_impar' (legacy/débil), 'aleatorio' (sin estabilidad).
     * - porcentaje_control:    [0..100] cuánto % de clientes va al grupo control
     *                          (50 = split clásico, también funciona 80/20 si no se quiere
     *                          sacrificar mucho ingreso al grupo control durante la prueba).
     * - tamano_muestra_minimo: ventas por grupo necesarias para considerar el resultado
     *                          concluyente (regla práctica: 30 mínimo, 100+ ideal para
     *                          detectar diferencias de 10% en ticket promedio).
     */
    'ab_testing' => [
        'enabled'               => (bool)   env('REC_AB_ENABLED', false),
        'estrategia'            => (string) env('REC_AB_ESTRATEGIA', 'hash_cliente'),
        'porcentaje_control'    => (int)    env('REC_AB_PCT_CONTROL', 50),
        'tamano_muestra_minimo' => (int)    env('REC_AB_MIN_MUESTRA', 30),
    ],

    /**
     * [BLOQUE 5] DemandaForecastService — Suavizado Exponencial Simple (SES).
     *
     * - alpha:               Parámetro de suavizado SES (0,1). Más alto =
     *                        más reactivo a la última semana; más bajo =
     *                        más suave. Valores típicos: 0.3-0.5.
     * - historia_semanas:    Cuántas semanas hacia atrás materializa el job
     *                        en `producto_demanda_semana`. 16 ≈ 4 meses.
     * - min_observaciones:   Mínimo de semanas con dato para que el modelo
     *                        prediga. < 8 produce predicciones inestables;
     *                        en producción real no bajar de 8.
     * - top_riesgo_widget:   Cuántos productos en riesgo muestra el dashboard.
     * - job_enabled / hora / dia_semana: Schedule semanal (lunes 03:00 default).
     *                        El cron Laravel respeta dia_semana en formato 0-6
     *                        (0=domingo, 1=lunes ... como Carbon::dayOfWeek).
     */
    'forecast' => [
        'alpha'              => (float)  env('REC_FORECAST_ALPHA', 0.4),
        'historia_semanas'   => (int)    env('REC_FORECAST_HISTORIA', 16),
        'min_observaciones'  => (int)    env('REC_FORECAST_MIN_OBS', 8),
        'top_riesgo_widget'  => (int)    env('REC_FORECAST_TOP_WIDGET', 10),
        'job_enabled'        => (bool)   env('REC_JOB_DEMANDA_ENABLED', true),
        'job_hora'           => (string) env('REC_JOB_DEMANDA_HORA', '03:00'),
        'job_dia_semana'     => (int)    env('REC_JOB_DEMANDA_DIA', 1), // 1 = lunes
    ],

    /**
     * [BLOQUE 6] Mapa de calor de enfermedades.
     *
     * - dias_default:        Ventana inicial al cargar el panel (configurable
     *                        por el usuario con el filtro de la vista).
     * - umbral_score:        Score mínimo en `cliente_perfil_afinidad` para
     *                        considerar que un cliente "padece observadamente"
     *                        una enfermedad. 0.20 ≈ 4-5 compras significativas.
     * - top_por_sucursal:    Cuántas enfermedades top se muestran por sucursal
     *                        en la sección de "insights de negocio".
     * - cluster_enabled:     Permite desactivar el clustering jerárquico si el
     *                        recetario crece > 200 enfermedades (es O(n³)).
     */
    'heatmap_enfermedades' => [
        'dias_default'      => (int)   env('REC_HEATMAP_DIAS', 90),
        'umbral_score'      => (float) env('REC_HEATMAP_UMBRAL_SCORE', 0.20),
        'top_por_sucursal'  => (int)   env('REC_HEATMAP_TOP_SUC', 3),
        'cluster_enabled'   => (bool)  env('REC_HEATMAP_CLUSTER', true),
    ],
];
