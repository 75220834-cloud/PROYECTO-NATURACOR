<?php

namespace App\Imports;

use App\Models\Enfermedad;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa enfermedades + relaciones a productos desde Excel.
 *
 * Reglas:
 *   - Match por nombre (case-insensitive). Si existe → update solo de campos llenos.
 *   - Productos asociados con syncWithoutDetaching (nunca borra relaciones existentes).
 *   - Separadores aceptados entre productos: "|" y ";".
 *   - Productos no encontrados se reportan en $errores pero NO frenan la fila —
 *     la enfermedad se procesa con los productos sí encontrados.
 *   - Filas con nombre vacío se ignoran silenciosamente (Excel suele tener filas en blanco).
 *
 * Encabezados esperados en el Excel (la librería los normaliza a snake_case):
 *   - "Nombre enfermedad"                      → nombre_enfermedad
 *   - "Categoría"                              → categoria
 *   - "Descripción"                            → descripcion
 *   - "Productos Recomendados (separados por |)" → productos_recomendados_separados_por
 */
class EnfermedadesImport implements ToCollection, WithHeadingRow
{
    public int $creadas = 0;
    public int $actualizadas = 0;
    public array $errores = [];

    public function collection(Collection $rows): void
    {
        // Pre-cargar todos los productos a memoria para no hacer N+1
        // (con 50-200 productos esto es trivial; mucho más rápido que un find por fila)
        $productosPorNombre = Producto::pluck('id', 'nombre')
            ->mapWithKeys(fn($id, $nombre) => [Str::lower(trim($nombre)) => $id]);

        foreach ($rows as $i => $row) {
            // Excel suele tener filas vacías al final, las saltamos sin error
            $nombre = trim((string) ($row['nombre_enfermedad'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            // Número de fila legible para los mensajes de error (fila 1 = encabezado)
            $filaExcel = $i + 2;

            $categoria   = trim((string) ($row['categoria'] ?? '')) ?: null;
            $descripcion = trim((string) ($row['descripcion'] ?? '')) ?: null;

            // Buscar enfermedad existente case-insensitive
            $enfermedad = Enfermedad::whereRaw('LOWER(nombre) = ?', [Str::lower($nombre)])->first();

            if ($enfermedad) {
                // Actualizar solo campos llenos (no pisa con null lo que ya hay)
                $datos = array_filter([
                    'categoria'   => $categoria,
                    'descripcion' => $descripcion,
                ], fn($v) => $v !== null);

                if (!empty($datos)) {
                    $enfermedad->update($datos);
                }
                $this->actualizadas++;
            } else {
                $enfermedad = Enfermedad::create([
                    'nombre'      => $nombre,
                    'categoria'   => $categoria,
                    'descripcion' => $descripcion,
                    'activa'      => true,
                ]);
                $this->creadas++;
            }

            // Procesar productos: aceptar separador | o ;
            $productosCelda = trim((string) ($row['productos_recomendados_separados_por'] ?? ''));
            if ($productosCelda === '') {
                continue;
            }

            $nombresProductos = preg_split('/[|;]/', $productosCelda);
            $idsAVincular = [];

            foreach ($nombresProductos as $nombreProducto) {
                $nombreProducto = trim($nombreProducto);
                if ($nombreProducto === '') {
                    continue;
                }

                $key = Str::lower($nombreProducto);
                if (isset($productosPorNombre[$key])) {
                    $idsAVincular[] = $productosPorNombre[$key];
                } else {
                    $this->errores[] = "Fila {$filaExcel}: producto no encontrado «{$nombreProducto}» (enfermedad «{$nombre}»).";
                }
            }

            if (!empty($idsAVincular)) {
                // syncWithoutDetaching: agrega los nuevos sin borrar los que ya tenía
                $enfermedad->productos()->syncWithoutDetaching($idsAVincular);
            }
        }
    }
}
