@extends('layouts.app')
@section('title', $producto->nombre)
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('productos.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📦 {{ $producto->nombre }}</h4>
        <small class="text-muted">{{ $producto->tipo === 'natural' ? '🌿 Natural' : '🧃 Cordial' }}</small>
    </div>
    <a href="{{ route('productos.edit', $producto) }}" class="btn btn-outline-success btn-sm ms-auto">
        <i class="bi bi-pencil me-1"></i> Editar
    </a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <div style="width:80px;height:80px;background:linear-gradient(135deg,#bbf7d0,#86efac);border-radius:20px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:36px;">
                    {{ $producto->tipo === 'natural' ? '🌿' : '🧃' }}
                </div>
                <div style="font-size:28px;font-weight:900;color:#16a34a;">S/ {{ number_format($producto->precio,2) }}</div>
                <small class="text-muted">Precio de venta</small>
                <hr>
                <div class="d-flex justify-content-around">
                    <div class="text-center">
                        <div class="fw-bold {{ $producto->stock <= $producto->stock_minimo ? 'text-danger' : 'text-success' }}" style="font-size:22px;">
                            {{ $producto->stock }}
                        </div>
                        <small class="text-muted">Stock actual</small>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-muted" style="font-size:22px;">{{ $producto->stock_minimo }}</div>
                        <small class="text-muted">Stock mínimo</small>
                    </div>
                </div>
                @if($producto->stock <= $producto->stock_minimo)
                <div class="alert alert-warning mt-3 mb-0 py-2" style="font-size:12px;">
                    ⚠️ Stock bajo — necesita reposición
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <table class="table table-sm mb-0">
                    <thead class="visually-hidden">
                        <tr><th scope="col">Campo</th><th scope="col">Valor</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="text-muted" style="width:140px;">Nombre</td><td class="fw-semibold">{{ $producto->nombre }}</td></tr>
                        <tr><td class="text-muted">Tipo</td><td>{{ $producto->tipo === 'natural' ? '🌿 Natural' : '🍵 Cordial' }}</td></tr>
                        <tr><td class="text-muted">Descripción</td><td>{{ $producto->descripcion ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Sucursal</td><td>{{ $producto->sucursal?->nombre ?? 'Todas' }}</td></tr>
                        <tr><td class="text-muted">Frecuente</td><td>{{ $producto->frecuente ? '⚡ Sí (POS rápido)' : 'No' }}</td></tr>
                        <tr><td class="text-muted">Estado</td>
                            <td><span class="badge {{ $producto->activo ? 'bg-success' : 'bg-secondary' }}">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        </tr>
                        <tr><td class="text-muted">Registrado</td><td>{{ $producto->created_at->format('d/m/Y') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('productos.edit', $producto) }}" class="btn btn-success px-4">
                <i class="bi bi-pencil me-1"></i> Editar producto
            </a>
            <form action="{{ route('productos.destroy', $producto) }}" method="POST" onsubmit="return confirm('¿Eliminar este producto?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger px-4">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
