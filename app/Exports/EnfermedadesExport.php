<?php

namespace App\Exports;

use App\Models\Enfermedad;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Exporta el catálogo del recetario a Excel.
 *
 * Formato:
 *   - Una fila por enfermedad activa.
 *   - Productos relacionados en una sola celda separados por " | ".
 *   - Encabezados con estilo NATURACOR (verde + texto blanco + negrita).
 *
 * Mantiene paridad de estilo con App\Exports\ProductosExport.
 */
class EnfermedadesExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Enfermedad::with('productos:id,nombre')
            ->where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn($e) => [
                'nombre'      => $e->nombre,
                'categoria'   => $e->categoria ?? '',
                'descripcion' => $e->descripcion ?? '',
                'productos'   => $e->productos->pluck('nombre')->implode(' | '),
            ]);
    }

    public function headings(): array
    {
        return [
            'Nombre enfermedad',
            'Categoría',
            'Descripción',
            'Productos Recomendados (separados por |)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16a34a']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Nombre enfermedad
            'B' => 18, // Categoría
            'C' => 45, // Descripción
            'D' => 60, // Productos
        ];
    }
}
