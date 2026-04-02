@extends('layouts.app')
@section('title', "Cliente: {$cliente->nombre}")
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('clientes.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">👤 {{ $cliente->nombre }} {{ $cliente->apellido }}</h4>
        <small class="text-muted">DNI: {{ $cliente->dni }}</small>
    </div>
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-success btn-sm ms-auto">
        <i class="bi bi-pencil me-1"></i> Editar
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#bbf7d0,#86efac);display:flex;align-items:center;justify-content:center;font-weight:700;color:#15803d;font-size:28px;margin:0 auto 12px;">
                {{ strtoupper(substr($cliente->nombre,0,1)) }}
            </div>
            <h5 class="fw-bold mb-1">{{ $cliente->nombre }} {{ $cliente->apellido }}</h5>
            <p class="text-muted mb-0" style="font-size:13px;">{{ $cliente->telefono ?? 'Sin teléfono' }}</p>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div style="font-size:26px;font-weight:800;color:#22c55e;">{{ $cliente->ventas->where('estado','completada')->count() }}</div>
                    <small class="text-muted">Compras totales</small>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                    <div style="font-size:26px;font-weight:800;color:#22c55e;">S/ {{ number_format($totalCompras, 2) }}</div>
                    <small class="text-muted">Total gastado</small>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 p-3 mt-3">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;letter-spacing:0.5px;margin-bottom:8px;">Información de contacto</div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted" style="font-size:13px;">DNI</td><td><code>{{ $cliente->dni }}</code></td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Teléfono</td><td>{{ $cliente->telefono ?? '—' }}</td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Email</td><td>{{ $cliente->email ?? '—' }}</td></tr>
                <tr><td class="text-muted" style="font-size:13px;">Cliente desde</td><td>{{ $cliente->created_at->format('d/m/Y') }}</td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Historial de compras -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 px-4 py-3">
        <h6 class="fw-bold mb-0">🧾 Historial de Compras</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr style="background:#f0fdf4;font-size:12px;text-transform:uppercase;color:#6b7280;">
                        <th class="px-4 py-3">Boleta</th>
                        <th>Fecha</th>
                        <th>Método Pago</th>
                        <th class="text-end px-4">Total</th>
                        <th class="text-center">Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cliente->ventas->sortByDesc('created_at')->take(20) as $venta)
                    <tr>
                        <td class="px-4"><code style="font-size:12px;">{{ $venta->numero_boleta ?? 'N/A' }}</code></td>
                        <td style="font-size:13px;">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge" style="background:#f0fdf4;color:#15803d;font-size:11px;">{{ $venta->metodo_pago }}</span></td>
                        <td class="text-end px-4 fw-semibold" style="color:#16a34a;">S/ {{ number_format($venta->total,2) }}</td>
                        <td class="text-center">
                            @if($venta->estado === 'completada')
                                <span class="badge" style="background:#dcfce7;color:#15803d;">Completada</span>
                            @elseif($venta->estado === 'anulada')
                                <span class="badge" style="background:#fef2f2;color:#dc2626;">Anulada</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('boletas.show', $venta) }}" class="btn btn-light btn-sm"><i class="bi bi-receipt"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Sin compras registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
