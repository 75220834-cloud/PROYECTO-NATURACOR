@extends('layouts.app')
@section('title', 'Nueva Entrada en Recetario')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('recetario.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">📖 Nueva Entrada en Recetario</h4>
        <small class="text-muted">Registrar enfermedad y productos recomendados</small>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="{{ route('recetario.store') }}" method="POST" id="recetarioForm">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:13px;">Nombre de la enfermedad / condición *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                        placeholder="Ej: Diabetes tipo 2, Artritis, Gastritis..." required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">Categoría</label>
                    <input type="text" name="categoria" value="{{ old('categoria') }}" class="form-control rounded-3"
                        placeholder="Ej: Digestivo, Circulatorio...">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:13px;">Descripción</label>
                    <textarea name="descripcion" rows="3" class="form-control rounded-3"
                        placeholder="Descripción de la enfermedad o condición...">{{ old('descripcion') }}</textarea>
                </div>
            </div>

            <hr>
            <h6 class="fw-bold mb-3">🌿 Productos Recomendados</h6>
            <div id="productosContainer">
                <!-- Producto rows will be added here -->
            </div>

            <button type="button" id="addProducto" class="btn btn-outline-success btn-sm mb-4">
                <i class="bi bi-plus-circle me-1"></i> Agregar producto recomendado
            </button>

            <!-- Hidden inputs for productos -->
            <div id="productosInputs"></div>

            <!-- Selector de producto (oculto) -->
            <div class="mb-3" style="display:none;" id="selectorArea">
                <label class="form-label fw-semibold" style="font-size:13px;">Seleccionar producto</label>
                <select id="productoSelect" class="form-select rounded-3">
                    <option value="">-- Seleccionar --</option>
                    @foreach($productos as $p)
                    <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">{{ $p->nombre }} (S/ {{ number_format($p->precio,2) }})</option>
                    @endforeach
                </select>
                <input type="text" id="instruccionesInput" class="form-control rounded-3 mt-2" placeholder="Instrucciones de uso (opcional)">
                <button type="button" id="confirmarProducto" class="btn btn-success btn-sm mt-2">Agregar</button>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-lg me-1"></i> Guardar en Recetario</button>
                <a href="{{ route('recetario.index') }}" class="btn btn-light px-4">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
let productosAgregados = [];

document.getElementById('addProducto').addEventListener('click', function() {
    document.getElementById('selectorArea').style.display = 'block';
});

document.getElementById('confirmarProducto').addEventListener('click', function() {
    const sel = document.getElementById('productoSelect');
    const instrucciones = document.getElementById('instruccionesInput').value;
    const id = sel.value;
    const nombre = sel.options[sel.selectedIndex].dataset.nombre;
    if (!id) return;

    const idx = productosAgregados.length;
    productosAgregados.push({ id, nombre, instrucciones });

    const container = document.getElementById('productosContainer');
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 mb-2 p-3 rounded-3';
    row.style.background = '#f0fdf4';
    row.innerHTML = `
        <div class="flex-grow-1">
            <div class="fw-semibold" style="font-size:13px;">🌿 ${nombre}</div>
            ${instrucciones ? `<small class="text-muted">${instrucciones}</small>` : ''}
        </div>
        <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.parentElement.remove(); removeProducto(${idx})">✕</button>
        <input type="hidden" name="productos[${idx}][id]" value="${id}">
        <input type="hidden" name="productos[${idx}][instrucciones]" value="${instrucciones}">
    `;
    container.appendChild(row);

    sel.value = '';
    document.getElementById('instruccionesInput').value = '';
    document.getElementById('selectorArea').style.display = 'none';
});

function removeProducto(idx) {
    productosAgregados[idx] = null;
}
</script>
@endsection
