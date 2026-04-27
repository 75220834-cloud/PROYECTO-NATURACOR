@extends('layouts.app')
@section('title', "Editar: {$recetario->nombre}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('recetario.show', $recetario) }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">✏️ Editar: {{ $recetario->nombre }}</h4>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('recetario.update', $recetario) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $recetario->nombre) }}" class="form-control rounded-3" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Categoría</label>
                    <input type="text" name="categoria" value="{{ old('categoria', $recetario->categoria) }}" class="form-control rounded-3">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">Descripción</label>
                    <textarea name="descripcion" rows="3" class="form-control rounded-3">{{ old('descripcion', $recetario->descripcion) }}</textarea>
                </div>
            </div>

            <hr>
            <h6 class="fw-bold mb-3">🌿 Productos Recomendados</h6>
            <div id="productosContainer">
                @foreach($recetario->productos as $i => $prod)
                <div class="d-flex align-items-center gap-2 mb-2 p-3 rounded-3" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);">
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:13px;">🌿 {{ $prod->nombre }}</div>
                        <input type="text" name="productos[{{ $i }}][instrucciones]" value="{{ $prod->pivot->instrucciones }}"
                            class="form-control form-control-sm mt-1 rounded-2" placeholder="Instrucciones de uso...">
                        <input type="hidden" name="productos[{{ $i }}][id]" value="{{ $prod->id }}">
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mb-4 mt-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Agregar producto recomendado</label>
                <div class="d-flex gap-2">
                    <select id="nuevoProducto" class="form-select rounded-3">
                        <option value="">-- Seleccionar producto --</option>
                        @foreach($productos as $p)
                        <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="addBtn" class="btn btn-outline-success">+ Agregar</button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">💾 Guardar cambios</button>
                <a href="{{ route('recetario.show', $recetario) }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
let nextIdx = {{ $recetario->productos->count() }};
document.getElementById('addBtn').addEventListener('click', function() {
    const sel = document.getElementById('nuevoProducto');
    if (!sel.value) return;
    const nombre = sel.options[sel.selectedIndex].dataset.nombre;
    const id = sel.value;
    const container = document.getElementById('productosContainer');
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 mb-2 p-3 rounded-3';
    row.style.background = 'rgba(255,255,255,0.05)';
    row.style.border = '1px solid rgba(255,255,255,0.1)';
    row.innerHTML = `
        <div class="flex-grow-1">
            <div class="fw-semibold" style="font-size:13px;">🌿 ${nombre}</div>
            <input type="hidden" name="productos[${nextIdx}][id]" value="${id}">
            <input type="text" name="productos[${nextIdx}][instrucciones]" class="form-control form-control-sm mt-1 rounded-2" placeholder="Instrucciones de uso...">
        </div>
        <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(row);
    nextIdx++;
    sel.value = '';
});
</script>
@endsection
