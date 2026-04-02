<?php

return [
    'igv_porcentaje' => env('IGV_PORCENTAJE', 18),
    'fidelizacion_monto' => env('FIDELIZACION_MONTO', 250),
    'fidelizacion_maximo_premio' => env('FIDELIZACION_MAXIMO_PREMIO', 30),
    'stock_minimo_default' => env('STOCK_MINIMO_DEFAULT', 5),
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
