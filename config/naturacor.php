<?php

return [
    'igv_porcentaje' => env('IGV_PORCENTAJE', 18),
    'fidelizacion_monto'           => env('FIDELIZACION_MONTO', 500),
    'fidelizacion_maximo_premio'   => env('FIDELIZACION_MAXIMO_PREMIO', 30),
    'fidelizacion_cordiales_monto' => env('FIDELIZACION_CORDIALES_MONTO', 500),
    'fidelizacion_inicio'          => env('FIDELIZACION_INICIO', '2026-01-01'),
    'fidelizacion_fin'             => env('FIDELIZACION_FIN', '2026-12-31'),
    'stock_minimo_default' => env('STOCK_MINIMO_DEFAULT', 5),

    // API keys para IA (BUG #5 FIX: usar config() en vez de env() en controllers)
    'gemini_api_key' => env('GEMINI_API_KEY', ''),
    'groq_api_key'   => env('GROQ_API_KEY', ''),
    'openai_api_key' => env('OPENAI_API_KEY', ''),

    'supabase_url' => env('SUPABASE_URL', ''),
    'supabase_key' => env('SUPABASE_KEY', ''),
    'empresa' => [
        'nombre' => 'NATURACOR',
        'ruc' => '20000000000',
        'direccion' => 'Lima, Perú',
        'telefono' => '',
        'email' => 'info@naturacor.pe',
    ],
];
