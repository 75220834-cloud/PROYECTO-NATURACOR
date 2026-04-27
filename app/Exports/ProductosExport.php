<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductosExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Producto::with('sucursal')
            ->orderBy('nombre')
            ->get()
            ->map(fn($p) => [
                'nombre'        => $p->nombre,
                'tipo'          => $p->tipo,
                'descripcion'   => $p->descripcion ?? '',
                'precio'        => $p->precio,
                'stock'         => $p->stock,
                'stock_minimo'  => $p->stock_minimo,
                'sucursal'      => $p->sucursal?->nombre ?? '',
                'frecuente'     => $p->frecuente ? 'sí' : 'no',
                'codigo_barras' => $p->codigo_barras ?? '',
            ]);
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Tipo',
            'Descripción',
            'Precio',
            'Stock',
            'Stock mínimo',
            'Sucursal',
            'Frecuente (sí/no)',
            'Código de barras',
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
            'A' => 30, 'B' => 12, 'C' => 40,
            'D' => 12, 'E' => 10, 'F' => 14,
            'G' => 20, 'H' => 18, 'I' => 20,
        ];
    }
}
