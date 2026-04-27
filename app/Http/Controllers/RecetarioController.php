<?php

namespace App\Http\Controllers;

use App\Models\Enfermedad;
use App\Models\Producto;
use App\Exports\EnfermedadesExport;
use App\Imports\EnfermedadesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class RecetarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Enfermedad::with('productos')->where('activa', true);
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('categoria', 'like', "%{$request->search}%");
            });
        }
        $enfermedades = $query->orderBy('nombre')->get();
        return view('recetario.index', compact('enfermedades'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->where('tipo', 'natural')->orderBy('nombre')->get();
        return view('recetario.create', compact('productos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'productos' => 'nullable|array',
            'productos.*.id' => 'exists:productos,id',
            'productos.*.instrucciones' => 'nullable|string',
        ]);

        $enfermedad = Enfermedad::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria' => $data['categoria'] ?? null,
        ]);

        if (!empty($data['productos'])) {
            $sync = [];
            foreach ($data['productos'] as $i => $p) {
                $sync[$p['id']] = ['instrucciones' => $p['instrucciones'] ?? null, 'orden' => $i];
            }
            $enfermedad->productos()->sync($sync);
        }

        return redirect()->route('recetario.index')->with('success', 'Enfermedad registrada en el recetario.');
    }

    public function show(Enfermedad $recetario)
    {
        $recetario->load('productos');
        return view('recetario.show', compact('recetario'));
    }

    public function edit(Enfermedad $recetario)
    {
        $recetario->load('productos');
        $productos = Producto::where('activo', true)->where('tipo', 'natural')->orderBy('nombre')->get();
        return view('recetario.edit', compact('recetario', 'productos'));
    }

    public function update(Request $request, Enfermedad $recetario)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'productos' => 'nullable|array',
        ]);

        $recetario->update($data);
        $sync = [];
        foreach ($request->productos ?? [] as $i => $p) {
            $sync[$p['id']] = ['instrucciones' => $p['instrucciones'] ?? null, 'orden' => $i];
        }
        $recetario->productos()->sync($sync);

        return redirect()->route('recetario.index')->with('success', 'Recetario actualizado.');
    }

    public function destroy(Enfermedad $recetario)
    {
        $recetario->delete();
        return redirect()->route('recetario.index')->with('success', 'Eliminado del recetario.');
    }

    /**
     * Descarga el catálogo del recetario como Excel.
     */
    public function exportar()
    {
        $nombre = 'recetario_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new EnfermedadesExport(), $nombre);
    }

    /**
     * Descarga una plantilla Excel vacía con los encabezados y un ejemplo
     * para que el usuario sepa cómo llenarla.
     */
    public function plantilla()
    {
        // Generamos un export "ad-hoc" con una sola fila de ejemplo
        $plantilla = new class implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithStyles,
            \Maatwebsite\Excel\Concerns\WithColumnWidths
        {
            public function array(): array
            {
                return [[
                    'Gastritis',
                    'Digestivo',
                    'Inflamación de la mucosa gástrica',
                    'Manzanilla 100g | Aloe Vera 500ml',
                ]];
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

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '16a34a'],
                        ],
                    ],
                ];
            }

            public function columnWidths(): array
            {
                return ['A' => 30, 'B' => 18, 'C' => 45, 'D' => 60];
            }
        };

        return Excel::download($plantilla, 'plantilla_recetario.xlsx');
    }

    /**
     * Procesa el Excel subido por el usuario y crea/actualiza enfermedades + relaciones.
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5 MB
        ]);

        $import = new EnfermedadesImport();
        Excel::import($import, $request->file('archivo'));

        // Construir mensaje resumen para el usuario
        $resumen = "Importación completada. Creadas: {$import->creadas} | Actualizadas: {$import->actualizadas}";
        if (!empty($import->errores)) {
            $resumen .= ' | Errores: ' . count($import->errores);
        }

        return redirect()->route('recetario.index')
            ->with('success', $resumen)
            ->with('errores_import', $import->errores);
    }
}
