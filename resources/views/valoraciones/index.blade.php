@extends('layouts.app')
@section('title', 'Valoraciones')
@section('page-title', 'Moderación de Valoraciones')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1" style="font-weight:700;">Valoraciones de Clientes</h5>
        <span class="text-muted" style="font-size:13px;">{{ $valoraciones->total() }} valoraciones totales</span>
    </div>
</div>

<div class="nc-card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cliente</th>
                    <th>Estrellas</th>
                    <th>Comentario</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($valoraciones as $v)
                <tr>
                    <td style="font-size:13px;font-weight:500;">{{ $v->producto?->nombre ?? 'Eliminado' }}</td>
                    <td style="font-size:13px;">{{ $v->nombre_cliente }}</td>
                    <td>
                        @for($i = 1; $i <= 5; $i++)
                        <i class="bi {{ $i <= $v->estrellas ? 'bi-star-fill' : 'bi-star' }}" style="color:#fbbf24;font-size:13px;"></i>
                        @endfor
                    </td>
                    <td style="font-size:12px;color:rgba(255,255,255,0.6);max-width:250px;">{{ Str::limit($v->comentario, 80) }}</td>
                    <td style="font-size:12px;">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($v->aprobada)
                        <span class="badge bg-success bg-opacity-25 text-success" style="font-size:11px;">Aprobada</span>
                        @else
                        <span class="badge bg-warning bg-opacity-25 text-warning" style="font-size:11px;">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if(!$v->aprobada)
                            <form method="POST" action="{{ route('valoraciones.aprobar', $v) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success" style="font-size:12px;padding:4px 10px;" title="Aprobar">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('valoraciones.rechazar', $v) }}" onsubmit="return confirm('¿Eliminar esta valoración?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" style="font-size:12px;padding:4px 10px;" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-star" style="font-size:40px;opacity:0.3;"></i>
                        <p class="mt-2">No hay valoraciones aún</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $valoraciones->links() }}</div>
</div>
@endsection
