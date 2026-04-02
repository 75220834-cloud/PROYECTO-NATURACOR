@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">✏️ Editar Cliente</h4>
        <small class="text-muted">{{ $cliente->nombre }} {{ $cliente->apellido }}</small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('clientes.update', $cliente) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">DNI / Documento</label>
                <input type="text" class="form-control rounded-3" value="{{ $cliente->dni }}" disabled>
                <small class="text-muted">El DNI no se puede modificar</small>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" class="form-control rounded-3 @error('nombre') is-invalid @enderror" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido', $cliente->apellido) }}" class="form-control rounded-3">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="form-control rounded-3">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Correo</label>
                <input type="email" name="email" value="{{ old('email', $cliente->email) }}" class="form-control rounded-3">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">💾 Guardar cambios</button>
                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
