@extends('layouts.app')
@section('title', 'Nueva Sucursal')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('sucursales.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0" style="color:#1a2e1a">🏪 Nueva Sucursal</h4>
</div>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('sucursales.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control rounded-3 @error('nombre') is-invalid @enderror" placeholder="Ej: Sede Principal" required>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion') }}" class="form-control rounded-3" placeholder="Av. Los Pinos 123">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-control rounded-3" placeholder="987654321">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">RUC</label>
                <input type="text" name="ruc" value="{{ old('ruc') }}" class="form-control rounded-3" placeholder="20123456789">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus me-1"></i> Crear Sucursal</button>
                <a href="{{ route('sucursales.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
