@extends('layouts.app')
@section('title', 'Nuevo Cliente')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('clientes.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👤 Nuevo Cliente</h4>
        <small class="text-muted">Registro de cliente para fidelización</small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">DNI / Documento *</label>
                <input type="text" name="dni" value="{{ old('dni') }}" class="form-control rounded-3 @error('dni') is-invalid @enderror"
                    placeholder="Ej: 12345678" maxlength="20" required>
                @error('dni')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        placeholder="María" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido') }}" class="form-control rounded-3"
                        placeholder="García">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Teléfono / Celular</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-control rounded-3"
                    placeholder="987654321" maxlength="20">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control rounded-3"
                    placeholder="cliente@email.com">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-lg me-1"></i> Registrar Cliente
                </button>
                <a href="{{ route('clientes.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
