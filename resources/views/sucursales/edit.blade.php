@extends('layouts.app')
@section('title', "Editar: {$sucursale->nombre}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('sucursales.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0" style="color:#1a2e1a">✏️ Editar Sucursal</h4>
</div>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('sucursales.update', $sucursale) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre', $sucursale->nombre) }}" class="form-control rounded-3" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion', $sucursale->direccion) }}" class="form-control rounded-3">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $sucursale->telefono) }}" class="form-control rounded-3">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">RUC</label>
                <input type="text" name="ruc" value="{{ old('ruc', $sucursale->ruc) }}" class="form-control rounded-3">
            </div>
            <div class="mb-4 form-check">
                <input class="form-check-input" type="checkbox" name="activa" id="activa" value="1" {{ $sucursale->activa?'checked':'' }}>
                <label class="form-check-label fw-semibold" for="activa" style="font-size:13px;">Sucursal activa</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">💾 Guardar</button>
                <a href="{{ route('sucursales.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
