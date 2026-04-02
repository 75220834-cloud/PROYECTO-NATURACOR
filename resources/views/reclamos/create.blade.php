@extends('layouts.app')
@section('title', 'Nuevo Reclamo')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📋 Registrar Reclamo</h4>
        <small class="text-muted">Documenta la queja o reclamo del cliente</small>
    </div>
    <a href="{{ route('reclamos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('reclamos.store') }}">
            @csrf

            {{-- Cliente (opcional) --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Cliente <span class="text-muted fw-normal">(opcional)</span></label>
                <select name="cliente_id" class="form-select rounded-3 @error('cliente_id') is-invalid @enderror">
                    <option value="">— Cliente no registrado / anónimo —</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>
                            {{ $cliente->nombre }} {{ $cliente->apellido }} — DNI: {{ $cliente->dni }}
                        </option>
                    @endforeach
                </select>
                @error('cliente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Tipo --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Tipo de Reclamo <span class="text-danger">*</span></label>
                <select name="tipo" class="form-select rounded-3 @error('tipo') is-invalid @enderror" required>
                    <option value="">Selecciona el tipo...</option>
                    <option value="producto"  @selected(old('tipo')=='producto')>🛒 Producto (defectuoso, vencido, incorrecto)</option>
                    <option value="servicio"  @selected(old('tipo')=='servicio')>🙋 Servicio (atención, tiempo de espera)</option>
                    <option value="otro"      @selected(old('tipo')=='otro')>📌 Otro</option>
                </select>
                @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Descripción --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">Descripción del Reclamo <span class="text-danger">*</span></label>
                <textarea name="descripcion" rows="4"
                    class="form-control rounded-3 @error('descripcion') is-invalid @enderror"
                    placeholder="Describe detalladamente el problema o queja del cliente..."
                    required minlength="10" maxlength="1000">{{ old('descripcion') }}</textarea>
                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Mínimo 10 caracteres. Sé específico para facilitar la resolución.</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-circle me-1"></i> Registrar Reclamo
                </button>
                <a href="{{ route('reclamos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
