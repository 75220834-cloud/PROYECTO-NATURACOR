@extends('layouts.app')
@section('title', $recetario->nombre)
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('recetario.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#ffffff">📖 {{ $recetario->nombre }}</h4>
        @if($recetario->categoria)
        <span class="badge" style="background:rgba(40,199,111,0.15);color:#86efac;font-size:11px;">{{ $recetario->categoria }}</span>
        @endif
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('recetario.edit', $recetario) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-pencil me-1"></i> Editar</a>
        <form action="{{ route('recetario.destroy', $recetario) }}" method="POST" onsubmit="return confirm('¿Eliminar esta entrada?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Eliminar</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,#bbf7d0,#86efac);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:16px;">🌿</div>
                <h5 class="fw-bold">{{ $recetario->nombre }}</h5>
                @if($recetario->categoria)
                <span class="badge" style="background:#f0fdf4;color:#15803d;">{{ $recetario->categoria }}</span>
                @endif
                @if($recetario->descripcion)
                <p class="text-muted mt-3" style="font-size:13px;">{{ $recetario->descripcion }}</p>
                @endif
                <hr>
                <div style="font-size:13px;color:#9caea4;">
                    {{ $recetario->productos->count() }} producto(s) recomendado(s)
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 px-4 py-3">
                <h6 class="fw-bold mb-0">🌿 Productos Recomendados</h6>
            </div>
            <div class="card-body px-4 pb-4">
                @forelse($recetario->productos as $prod)
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2" style="background:rgba(40,199,111,0.07);border:1px solid rgba(40,199,111,0.15);">
                    <div style="width:40px;height:40px;background:rgba(40,199,111,0.20);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">🌿</div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:14px;">{{ $prod->nombre }}</div>
                        @if($prod->pivot->instrucciones)
                        <small class="text-muted">{{ $prod->pivot->instrucciones }}</small>
                        @endif
                    </div>
                    <div class="fw-bold" style="color:var(--neon);">S/ {{ number_format($prod->precio, 2) }}</div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-basket" style="font-size:32px;opacity:0.3;"></i>
                    <div class="mt-2">Sin productos recomendados aún</div>
                    <a href="{{ route('recetario.edit', $recetario) }}" class="btn btn-success btn-sm mt-2">Agregar productos</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
