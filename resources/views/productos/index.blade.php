@extends('layouts.app')
@section('title', 'Productos')
@section('page-title', 'Gestión de Productos')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1" style="font-weight:700;">Inventario de Productos</h5>
        <span class="text-muted" style="font-size:13px;">{{ $productos->total() }} productos registrados</span>
    </div>
    <a href="{{ route('productos.create') }}" class="btn btn-naturacor">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Producto
    </a>
</div>

<!-- Filtros -->
<div class="nc-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nombre del producto...">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select">
                <option value="">Todos</option>
                <option value="natural" {{ request('tipo')=='natural' ? 'selected' : '' }}>🌿 Natural</option>
                <option value="cordial" {{ request('tipo')=='cordial' ? 'selected' : '' }}>🍹 Cordial</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="stock_bajo" value="1" {{ request('stock_bajo') ? 'checked' : '' }}>
                <label class="form-check-label" style="font-size:13px; font-weight:500; color:rgba(255,255,255,0.80);">⚠️ Solo stock bajo</label>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-naturacor w-100">Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="nc-card">
    <div class="table-responsive">
        <table class="table nc-table mb-0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Frecuente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $p)
                <tr>
                    <td>
                        <div class="fw-bold" style="color:#ffffff;">{{ $p->nombre }}</div>
                        @if($p->descripcion)
                        <div class="text-muted" style="font-size:12px;">{{ Str::limit($p->descripcion, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($p->tipo === 'natural')
                            <span class="badge-natural">🌿 Natural</span>
                        @else
                            <span class="badge-cordial">🍹 Cordial</span>
                        @endif
                    </td>
                    <td><strong style="color:#ffffff;">S/ {{ number_format($p->precio, 2) }}</strong></td>
                    <td>
                        @if($p->stock == 0)
                            <span class="badge-stock-zero">Sin stock</span>
                        @elseif($p->tieneStockBajo())
                            <span class="badge-stock-low">{{ $p->stock }} ⚠️</span>
                        @else
                            <span class="badge-stock-ok">{{ $p->stock }} ✔</span>
                        @endif
                        <div class="text-muted" style="font-size:10px;">Mín: {{ $p->stock_minimo }}</div>
                    </td>
                    <td>
                        @if($p->frecuente)
                            <span style="color:var(--neon); font-size:18px;">⚡</span>
                        @else
                            <span style="color:rgba(255,255,255,0.25);">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-naturacor-outline" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('productos.destroy', $p) }}"
                                style="display:inline;"
                                data-confirm="¿Eliminar el producto '{{ $p->nombre }}'?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-box-seam" style="font-size:40px; opacity:0.3;"></i>
                        <p class="mt-2">No hay productos registrados</p>
                        <a href="{{ route('productos.create') }}" class="btn btn-naturacor btn-sm">Agregar primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $productos->withQueryString()->links() }}</div>
</div>
@endsection
