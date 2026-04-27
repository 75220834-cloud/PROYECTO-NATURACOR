@extends('layouts.app')
@section('title', 'Nuevo Producto')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('productos.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📦 Nuevo Producto</h4>
        <small class="text-muted">Agregar producto al inventario</small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre del producto *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        placeholder="Ej: Aloe Vera 500ml" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Tipo *</label>
                    <select name="tipo" class="form-select rounded-3" required>
                        <option value="natural" {{ old('tipo')=='natural'?'selected':'' }}>🌿 Natural</option>
                        <option value="cordial" {{ old('tipo')=='cordial'?'selected':'' }}>🧃 Cordial</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">Descripción</label>
                    <textarea name="descripcion" rows="3" class="form-control rounded-3" placeholder="Descripción del producto, propiedades, beneficios...">{{ old('descripcion') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Precio (S/) *</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio" value="{{ old('precio') }}" class="form-control rounded-end-3 @error('precio') is-invalid @enderror"
                            step="0.01" min="0" required placeholder="0.00">
                    </div>
                    @error('precio')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Stock inicial *</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" class="form-control rounded-3" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Stock mínimo (alerta) *</label>
                    <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 5) }}" class="form-control rounded-3" min="0" required>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Sucursal</label>
                    <select name="sucursal_id" class="form-select rounded-3">
                        <option value="">Todas las sucursales</option>
                        @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}" {{ old('sucursal_id')==$suc->id?'selected':'' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">📷 Imagen del producto</label>
                    <input type="file" name="imagen" class="form-control rounded-3 @error('imagen') is-invalid @enderror" accept="image/*">
                    <small class="text-muted" style="font-size:11px;">JPG, PNG o WebP. Máximo 2MB. Se mostrará en el catálogo público.</small>
                    @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">🔖 Código de barras</label>
                    <div class="input-group">
                        <input type="text" name="codigo_barras" id="codigo_barras_create"
                            value="{{ old('codigo_barras') }}"
                            class="form-control rounded-start-3 @error('codigo_barras') is-invalid @enderror"
                            placeholder="Escanea o escribe el código"
                            maxlength="50" autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" title="Enfocar para escanear"
                            onclick="document.getElementById('codigo_barras_create').focus()">
                            <i class="bi bi-upc-scan"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:11px;">Opcional. Apunta el escáner USB aquí y escanea. Debe ser único.</small>
                    @error('codigo_barras')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                </div>        
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="frecuente" id="frecuente" value="1" {{ old('frecuente')?'checked':'' }}>
                        <label class="form-check-label fw-semibold" for="frecuente" style="font-size:13px;">
                            ⚡ Producto frecuente (aparece en botones rápidos del POS)
                        </label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-plus-circle me-1"></i> Crear Producto
                </button>
                <a href="{{ route('productos.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
