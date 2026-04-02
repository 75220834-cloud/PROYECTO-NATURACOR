@extends('layouts.app')
@section('title', "Editar Usuario")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('usuarios.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0" style="color:#1a2e1a">✏️ Editar Usuario</h4>
</div>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" class="form-control rounded-3" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-control rounded-3" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nueva contraseña</label>
                    <input type="password" name="password" class="form-control rounded-3" placeholder="Dejar en blanco para no cambiar">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control rounded-3">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Rol *</label>
                    <select name="rol" class="form-select rounded-3" required>
                        <option value="empleado" {{ $usuario->hasRole('empleado')?'selected':'' }}>👤 Empleado</option>
                        <option value="admin" {{ $usuario->hasRole('admin')?'selected':'' }}>⭐ Administrador</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:13px;">Sucursal</label>
                    <select name="sucursal_id" class="form-select rounded-3">
                        <option value="">Sin asignar</option>
                        @foreach($sucursales as $suc)
                        <option value="{{ $suc->id }}" {{ $usuario->sucursal_id==$suc->id?'selected':'' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-4 form-check">
                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" {{ $usuario->activo?'checked':'' }}>
                <label class="form-check-label fw-semibold" for="activo" style="font-size:13px;">Usuario activo</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">💾 Guardar cambios</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
