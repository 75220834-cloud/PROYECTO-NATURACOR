@extends('layouts.app')
@section('title', "Editar: {$producto->nombre}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('productos.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">✏️ Editar Producto</h4>
        <small class="text-muted">{{ $producto->nombre }}</small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('productos.update', $producto) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="form-control rounded-3" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Tipo *</label>
                    <select name="tipo" class="form-select rounded-3" required>
                        <option value="natural" {{ $producto->tipo=='natural'?'selected':'' }}>🌿 Natural</option>
                        <option value="cordial" {{ $producto->tipo=='cordial'?'selected':'' }}>🧃 Cordial</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">Descripción</label>
                    <textarea name="descripcion" rows="3" class="form-control rounded-3">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Precio (S/) *</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio" value="{{ old('precio', $producto->precio) }}" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Stock *</label>
                    <input type="number" name="stock" value="{{ old('stock', $producto->stock) }}" class="form-control rounded-3" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Stock mínimo</label>
                    <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}" class="form-control rounded-3" min="0">
                </div>
                @if(auth()->user()->isAdmin())
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Sucursal</label>
                    <select name="sucursal_id" class="form-select rounded-3">
                        <option value="">Todas</option>
                        @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}" {{ $producto->sucursal_id==$suc->id?'selected':'' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">📷 Imagen del producto</label>
                    @if($url = producto_image_url($producto))
                    <div class="mb-2">
                        <img src="{{ $url }}" alt="{{ $producto->nombre }}" style="height:80px;border-radius:8px;border:1px solid rgba(255,255,255,0.10);">
                        <small class="text-muted d-block" style="font-size:11px;">Imagen actual. Sube otra para reemplazarla.</small>
                    </div>
                    @endif
                    <input type="file" name="imagen" class="form-control rounded-3 @error('imagen') is-invalid @enderror" accept="image/*">
                    <small class="text-muted" style="font-size:11px;">JPG, PNG o WebP. Máximo 2MB.</small>
                    @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">🔖 Código de barras</label>
                    <div class="input-group">
                        <input type="text" name="codigo_barras" id="codigo_barras_edit"
                            value="{{ old('codigo_barras', $producto->codigo_barras) }}"
                            class="form-control rounded-start-3 @error('codigo_barras') is-invalid @enderror"
                            placeholder="Escanea o escribe el código"
                            maxlength="50" autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" title="Enfocar para escanear"
                            onclick="document.getElementById('codigo_barras_edit').focus()">
                            <i class="bi bi-upc-scan"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:11px;">Opcional. Apunta el escáner USB aquí y escanea. Debe ser único.</small>
                    @error('codigo_barras')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="frecuente" id="frecuente" value="1" {{ $producto->frecuente?'checked':'' }}>
                        <label class="form-check-label" for="frecuente" style="font-size:13px;">⚡ Producto frecuente (POS)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" {{ $producto->activo?'checked':'' }}>
                        <label class="form-check-label" for="activo" style="font-size:13px;">✅ Activo</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">💾 Guardar cambios</button>
                <a href="{{ route('productos.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
