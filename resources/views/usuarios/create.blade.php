@extends('layouts.app')
@section('title', 'Nuevo Usuario')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('usuarios.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0" style="color:#1a2e1a">👤 Nuevo Usuario</h4>
</div>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('usuarios.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control rounded-3 @error('name') is-invalid @enderror" required placeholder="María García">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" required placeholder="usuario@naturacor.com">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Contraseña *</label>
                    <input type="password" name="password" class="form-control rounded-3 @error('password') is-invalid @enderror" required placeholder="Mínimo 8 caracteres">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Confirmar contraseña *</label>
                    <input type="password" name="password_confirmation" class="form-control rounded-3" required>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Rol *</label>
                    <select name="rol" class="form-select rounded-3" required>
                        <option value="empleado" {{ old('rol')=='empleado'?'selected':'' }}>👤 Empleado</option>
                        <option value="admin" {{ old('rol')=='admin'?'selected':'' }}>⭐ Administrador</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Sucursal</label>
                    <select name="sucursal_id" class="form-select rounded-3">
                        <option value="">Sin asignar</option>
                        @foreach($sucursales as $suc)
                        <option value="{{ $suc->id }}" {{ old('sucursal_id')==$suc->id?'selected':'' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-person-plus me-1"></i> Crear Usuario</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
