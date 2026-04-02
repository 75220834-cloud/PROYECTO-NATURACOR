@extends('layouts.app')
@section('title', "Venta #{{ $venta->numero_boleta }}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ventas.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">Venta <code>{{ $venta->numero_boleta }}</code></h4>
        <small class="text-muted">{{ $venta->created_at->format('d/m/Y H:i') }}</small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('boletas.show', $venta) }}" class="btn btn-success btn-sm">
            <i class="bi bi-receipt me-1"></i>Ver boleta
        </a>
        <a href="{{ route('boletas.pdf', $venta) }}" class="btn btn-outline-success btn-sm" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>PDF
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Información</h6>
                <div class="mb-2"><span class="text-muted" style="font-size:12px;">Estado</span><br>
                    @if($venta->estado === 'completada')
                        <span class="badge" style="background:#dcfce7;color:#15803d;">✅ Completada</span>
                    @elseif($venta->estado === 'anulada')
                        <span class="badge" style="background:#fef2f2;color:#dc2626;">❌ Anulada</span>
                    @else
                        <span class="badge bg-warning text-dark">⏳ Pendiente</span>
                    @endif
                </div>
                <div class="mb-2"><span class="text-muted" style="font-size:12px;">Método de pago</span><br>
                    <strong style="text-transform:capitalize;">{{ $venta->metodo_pago }}</strong>
                </div>
                <div class="mb-2"><span class="text-muted" style="font-size:12px;">Empleado</span><br>
                    <strong>{{ $venta->empleado?->name ?? '—' }}</strong>
                </div>
                <div class="mb-2"><span class="text-muted" style="font-size:12px;">Sucursal</span><br>
                    <strong>{{ $venta->sucursal?->nombre ?? '—' }}</strong>
                </div>
            </div>
        </div>

        @if($venta->cliente)
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">👤 Cliente</h6>
                <div class="fw-semibold">{{ $venta->cliente->nombreCompleto() }}</div>
                <div class="text-muted" style="font-size:13px;">DNI: {{ $venta->cliente->dni }}</div>
                @if($venta->cliente->telefono)
                <div class="text-muted" style="font-size:13px;">Tel: {{ $venta->cliente->telefono }}</div>
                @endif
                <div class="mt-2">
                    <span class="badge" style="background:#f0fdf4;color:#15803d;font-size:11px;">
                        💚 Acumulado: S/ {{ number_format($venta->cliente->acumulado_naturales, 2) }}
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 px-4 py-3">
                <h6 class="fw-bold mb-0">📦 Productos vendidos</h6>
            </div>
            <div class="card-body px-4 pb-4">
                @foreach($venta->detalles as $d)
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom" style="border-color:#f0f0f0!important;">
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:14px;">{{ $d->nombre_producto }}</div>
                        <div class="text-muted" style="font-size:12px;">
                            S/ {{ number_format($d->precio_unitario, 2) }} × {{ $d->cantidad }}
                            @if($d->descuento > 0)
                            <span class="text-danger">(-S/ {{ number_format($d->descuento, 2) }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="fw-bold" style="color:#16a34a;">S/ {{ number_format($d->subtotal, 2) }}</div>
                </div>
                @endforeach

                <div class="mt-3 pt-2">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">Subtotal</span>
                        <span>S/ {{ number_format($venta->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span class="text-muted">IGV (18%)</span>
                        <span>S/ {{ number_format($venta->igv, 2) }}</span>
                    </div>
                    @if($venta->descuento_total > 0)
                    <div class="d-flex justify-content-between mb-1 text-danger" style="font-size:13px;">
                        <span>Descuento</span>
                        <span>-S/ {{ number_format($venta->descuento_total, 2) }}</span>
                    </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>TOTAL</span>
                        <span style="color:#16a34a;font-size:18px;">S/ {{ number_format($venta->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
