<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\Sucursal;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class ProductosImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $errores = [];

    public function model(array $row)
    {
        // Buscar sucursal si viene
        $sucursalId = null;
        if (!empty($row['sucursal'])) {
            $sucursal = Sucursal::where('nombre', $row['sucursal'])->first();
            if (!$sucursal) {
                $this->errores[] = "Sucursal no encontrada: {$row['sucursal']}";
                return null;
            }
            $sucursalId = $sucursal->id;
        }

        $frecuente = in_array(strtolower($row['frecuente_sino'] ?? $row['frecuente'] ?? 'no'), ['sí', 'si', 'yes', '1', 'true']);

        // Si existe por nombre → actualizar solo campos llenos
        $producto = Producto::where('nombre', $row['nombre'])->first();

        if ($producto) {
            $producto->update(array_filter([
                'tipo'          => $row['tipo'] ?? null,
                'descripcion'   => $row['descripcion'] ?? null,
                'precio'        => !empty($row['precio']) ? $row['precio'] : null,
                'stock'         => isset($row['stock']) && $row['stock'] !== '' ? $row['stock'] : null,
                'stock_minimo'  => !empty($row['stock_minimo']) ? $row['stock_minimo'] : null,
                'sucursal_id'   => $sucursalId,
                'frecuente'     => $frecuente,
                'codigo_barras' => !empty($row['codigo_barras']) ? $row['codigo_barras'] : null,
            ], fn($v) => $v !== null && $v !== ''));
            return null;
        }

        // Si no existe → crear
        return new Producto([
            'nombre'        => $row['nombre'],
            'tipo'          => $row['tipo'] ?? 'natural',
            'descripcion'   => $row['descripcion'] ?? null,
            'precio'        => $row['precio'] ?? 0,
            'stock'         => $row['stock'] ?? 0,
            'stock_minimo'  => $row['stock_minimo'] ?? 5,
            'sucursal_id'   => $sucursalId,
            'frecuente'     => $frecuente,
            'codigo_barras' => !empty($row['codigo_barras']) ? $row['codigo_barras'] : null,
            'activo'        => true,
        ]);
    }
}
