@extends('layouts.app')
@section('title', 'Recetario Natural')
@section('page-title', '📋 Recetario Natural')
@section('content')
{{-- Modal Importar --}}
<div id="modalImportarRecetario" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.70);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:rgba(7,26,16,0.97);border:1px solid rgba(40,199,111,0.30);border-radius:20px;padding:32px;width:100%;max-width:440px;">
        <h5 class="fw-bold mb-3" style="color:#28c76f;"><i class="bi bi-upload me-2"></i>Importar Recetario</h5>
        <p style="font-size:13px;color:rgba(255,255,255,0.60);">
            Sube un archivo Excel (.xlsx) con el formato de la plantilla.
            Las enfermedades existentes por nombre serán actualizadas y los productos se agregarán
            sin borrar los que ya tenías.
        </p>
        <form action="{{ route('recetario.importar') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input type="file" name="archivo" accept=".xlsx,.xls,.csv" class="form-control" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light flex-grow-1"
                        onclick="document.getElementById('modalImportarRecetario').style.display='none'">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-naturacor flex-grow-1">
                    <i class="bi bi-upload me-1"></i>Importar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-1 fw-700">Enfermedades y Tratamientos Naturales</h5>
        <span class="text-muted" style="font-size:13px;">Guía de productos recomendados por enfermedad</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('recetario.create') }}" class="btn btn-naturacor">
            <i class="bi bi-plus-lg me-2"></i>Nueva Entrada
        </a>
        <a href="{{ route('recetario.exportar') }}" class="btn btn-naturacor-outline">
            <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
        </a>
        <a href="{{ route('recetario.plantilla') }}" class="btn btn-naturacor-outline">
            <i class="bi bi-download me-1"></i>Plantilla Excel
        </a>
        <button type="button" class="btn btn-naturacor-outline"
                onclick="document.getElementById('modalImportarRecetario').style.display='flex'">
            <i class="bi bi-upload me-1"></i>Importar Excel
        </button>
    </div>
</div>

{{-- Errores de import (si los hubo) --}}
@if(session('errores_import') && count(session('errores_import')) > 0)
<div class="nc-card mb-4" style="border:1px solid rgba(231,76,60,0.30); background:rgba(231,76,60,0.07);">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <h6 class="fw-bold mb-0" style="color:#fca5a5;">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Avisos de la importación ({{ count(session('errores_import')) }})
        </h6>
        <small style="color:rgba(255,255,255,0.45);">Las enfermedades sí se procesaron — los productos no encontrados quedaron sin asociar.</small>
    </div>
    <ul style="font-size:13px; color:rgba(255,255,255,0.75); margin:0; padding-left:18px;">
        @foreach(session('errores_import') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Buscador -->
<div class="nc-card mb-4">
    <form method="GET" class="d-flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" class="nc-input form-control" placeholder="🔍 Buscar enfermedad o categoría...">
        <button type="submit" class="btn btn-naturacor">Buscar</button>
        @if(request('search'))<a href="{{ route('recetario.index') }}" class="btn btn-naturacor-outline">Limpiar</a>@endif
    </form>
</div>

<!-- Cards de enfermedades -->
<div class="row g-4">
    @forelse($enfermedades as $enfermedad)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="nc-card h-100" style="transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1 fw-700" style="color: #ffffff;">{{ $enfermedad->nombre }}</h6>
                    @if($enfermedad->categoria)
                    <span class="badge" style="background: rgba(40,199,111,0.15); color: #86efac; border-radius:20px; font-size:11px; font-weight:600;">{{ $enfermedad->categoria }}</span>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('recetario.edit', $enfermedad) }}" class="btn btn-sm btn-naturacor-outline" style="padding:4px 8px;"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('recetario.destroy', $enfermedad) }}" onsubmit="return confirm('¿Eliminar?')">
                        @csrf @method('DELETE')
                        <button title="Eliminar" type="submit" class="btn btn-sm" style="border:1.5px solid #fecdd3; color:#e11d48; border-radius:8px; padding:4px 8px;"><i class="bi bi-trash3"></i></button>
                    </form>
                </div>
            </div>
            @if($enfermedad->descripcion)
            <p style="font-size:13px; color:#6b7280; margin-bottom:12px;">{{ Str::limit($enfermedad->descripcion, 100) }}</p>
            @endif
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-capsule me-2" style="color: var(--nc-green-600);"></i>
                <span style="font-size: 12px; font-weight: 600; color: var(--nc-green-700);">Productos recomendados ({{ $enfermedad->productos->count() }})</span>
            </div>
            @if($enfermedad->productos->count())
                @foreach($enfermedad->productos->take(3) as $p)
                <div class="d-flex align-items-center gap-2 mb-1" style="background: rgba(40,199,111,0.08); border-radius:8px; padding:6px 10px;">
                    <i class="bi bi-dot" style="color: var(--nc-green-500); font-size:20px; margin:-4px;"></i>
                    <div style="flex:1;">
                        <span style="font-size:13px; font-weight:500; color:#ffffff;">{{ $p->nombre }}</span>
                        <span style="font-size:12px; color: var(--nc-green-700); float:right;">S/ {{ number_format($p->precio, 2) }}</span>
                    </div>
                </div>
                @if($p->pivot->instrucciones)
                <div style="font-size:11px; color:#9caea4; padding: 0 10px 4px 24px; font-style:italic;">{{ $p->pivot->instrucciones }}</div>
                @endif
                @endforeach
                @if($enfermedad->productos->count() > 3)
                <div style="font-size:12px; color:var(--nc-green-600); text-align:center; margin-top:4px;">+ {{ $enfermedad->productos->count() - 3 }} productos más</div>
                @endif
            @else
                <div style="font-size:12px; color:#d1d5db; font-style:italic;">Sin productos asignados</div>
            @endif
            <div class="mt-3 text-end">
                <a href="{{ route('recetario.show', $enfermedad) }}" class="btn btn-sm btn-naturacor-outline">Ver detalle</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="nc-card text-center py-5">
            <i class="bi bi-journal-medical" style="font-size:48px; color: var(--nc-green-200);"></i>
            <h5 class="mt-3" style="color:#6b7280;">Recetario vacío</h5>
            <p class="text-muted">Empieza agregando enfermedades y sus tratamientos naturales.</p>
            <a href="{{ route('recetario.create') }}" class="btn btn-naturacor mt-2">Agregar primera entrada</a>
        </div>
    </div>
    @endforelse
</div>
@endsection
