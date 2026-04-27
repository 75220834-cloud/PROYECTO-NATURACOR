<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryUploader;
use App\Exports\ProductosExport;
use App\Imports\ProductosImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    public function __construct(private readonly CloudinaryUploader $uploader)
    {
    }
    public function index(Request $request)
    {
        $query = Producto::with('sucursal')
            ->when(auth()->user()->sucursal_id && !auth()->user()->isAdmin(), fn($q) => $q->where('sucursal_id', auth()->user()->sucursal_id));

        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%");
        }
        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->stock_bajo) {
            $query->whereColumn('stock', '<=', 'stock_minimo');
        }

        $productos = $query->orderBy('nombre')->paginate(20);
        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $sucursales = \App\Models\Sucursal::where('activa', true)->get();
        return view('productos.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'precio'        => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'tipo'          => 'required|in:natural,cordial',
            'frecuente'     => 'boolean',
            'sucursal_id'   => 'nullable|exists:sucursales,id',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'codigo_barras' => 'nullable|string|max:50|unique:productos,codigo_barras',
        ]);
        $data['activo'] = true;
        $data['frecuente'] = $request->boolean('frecuente');

        if (!auth()->user()->isAdmin()) {
            $data['sucursal_id'] = auth()->user()->sucursal_id;
        }

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->uploader->upload($request->file('imagen'));
        }

        Producto::create($data);
        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto)
    {
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        $sucursales = \App\Models\Sucursal::where('activa', true)->get();
        return view('productos.edit', compact('producto', 'sucursales'));
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:255',
            'descripcion'   => 'nullable|string',
            'precio'        => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'tipo'          => 'required|in:natural,cordial',
            'frecuente'     => 'boolean',
            'activo'        => 'boolean',
            'imagen'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'codigo_barras' => 'nullable|string|max:50|unique:productos,codigo_barras,' . $producto->id,
        ]);
        $data['frecuente'] = $request->boolean('frecuente');
        $data['activo'] = $request->boolean('activo');

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->uploader->upload($request->file('imagen'), $producto->imagen);
        }

        $producto->update($data);
        return redirect()->route('productos.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado.');
    }

    // API para búsqueda AJAX en el POS
    public function buscar(Request $request)
    {
        $productos = Producto::where('activo', true)
            ->where('nombre', 'like', "%{$request->q}%")
            ->where('tipo', 'natural')
            ->limit(10)->get(['id', 'nombre', 'precio', 'stock', 'frecuente']);

        return response()->json($productos);
    }

    // API para búsqueda por código de barras (escáner USB)
    public function buscarBarcode(Request $request)
    {
        $codigo = $request->get('codigo');
        if (!$codigo) {
            return response()->json(['found' => false, 'message' => 'Código no proporcionado']);
        }

        $producto = Producto::where('activo', true)
            ->where('codigo_barras', $codigo)
            ->first(['id', 'nombre', 'precio', 'stock', 'codigo_barras']);

        if ($producto) {
            return response()->json(['found' => true, 'producto' => $producto]);
        }

return response()->json(['found' => false, 'message' => 'Producto no encontrado con ese código']);
    }

    public function exportar()
    {
        return Excel::download(new ProductosExport(), 'productos_naturacor.xlsx');
    }

    public function plantilla()
    {
        $headers = [
            'Nombre', 'Tipo', 'Descripción', 'Precio',
            'Stock', 'Stock mínimo', 'Sucursal', 'Frecuente (sí/no)', 'Código de barras'
        ];
        return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private array $h) {}
            public function headings(): array { return $this->h; }
            public function array(): array { return [['Ejemplo Producto', 'natural', 'Descripción opcional', '10.00', '50', '5', '', 'no', '']]; }
        }, 'plantilla_productos.xlsx');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new ProductosImport();
        Excel::import($import, $request->file('archivo'));

        if (!empty($import->errores)) {
            $msg = 'Importación con errores: ' . implode(' | ', $import->errores);
            return redirect()->route('productos.index')->with('error', $msg);
        }

        return redirect()->route('productos.index')->with('success', 'Productos importados correctamente.');
    }
}
