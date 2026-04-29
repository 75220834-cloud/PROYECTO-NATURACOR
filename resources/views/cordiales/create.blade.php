@extends('layouts.app')
@section('title', 'Registrar Venta de Cordial')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1a2e1a">🥤 Registrar Venta de Cordial</h4>
        <small class="text-muted">Registra el consumo o venta de cordial</small>
    </div>
    <a href="{{ route('cordiales.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('cordiales.store') }}" id="formCordial">
                    @csrf

                    {{-- Cliente (opcional) --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">👤 Cliente (DNI)</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="clienteDni" class="form-control rounded-3" placeholder="DNI del cliente" maxlength="15">
                            <button type="button" class="btn btn-outline-success btn-sm px-3" onclick="buscarClienteCordial()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="clienteInfo" class="mt-2" style="display:none;">
                            <div style="background:#f0fdf4; border-radius:8px; padding:8px 12px; font-size:13px;">
                                <span id="clienteNombre" style="font-weight:600; color:#15803d;"></span>
                                <span id="clienteFidelizacion" class="ms-2"></span>
                            </div>
                        </div>
                        <input type="hidden" name="cliente_id" id="clienteId" value="">
                    </div>

                    {{-- Tipo de cordial --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Cordial <span class="text-danger">*</span></label>
                        <select name="tipo" id="tipoCordial" class="form-select rounded-3 @error('tipo') is-invalid @enderror" required
                            onchange="actualizarPrecio(this)">
                            <option value="">Selecciona el tipo...</option>
                            @foreach($tipos as $clave => $label)
                                <option value="{{ $clave }}"
                                    data-precio="{{ $precios[$clave] ?? 0 }}"
                                    @selected(old('tipo') == $clave)>
                                    {{ $label }} — S/ {{ number_format($precios[$clave] ?? 0, 0) }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Precio mostrado --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Precio Unitario</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="text" id="precioMostrado" class="form-control rounded-end" readonly value="—" style="background:#f9fafb;">
                        </div>
                    </div>

                    {{-- Cantidad --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" id="cantidadInput"
                            class="form-control rounded-3 @error('cantidad') is-invalid @enderror"
                            value="{{ old('cantidad', 1) }}" min="1" max="20" required
                            oninput="calcularTotal()">
                        @error('cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Total --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="text" id="totalMostrado" class="form-control fw-bold rounded-end" readonly value="0.00"
                                style="background:#f0fdf4; color:#15803d; font-size:18px;">
                        </div>
                    </div>

                    {{-- ¿Es invitado? --}}
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="es_invitado" id="esInvitado"
                                value="1" @checked(old('es_invitado'))
                                onchange="toggleInvitado(this)">
                            <label class="form-check-label fw-semibold" for="esInvitado">
                                🎁 Cordial de cortesía (invitado — precio S/0)
                            </label>
                        </div>
                    </div>

                    {{-- Motivo invitado (oculto por defecto) --}}
                    <div class="mb-3" id="motivoInvitadoDiv" style="display:none;">
                        <label class="form-label fw-semibold">Motivo del cordial gratuito</label>
                        <input type="text" name="motivo_invitado"
                            class="form-control rounded-3"
                            placeholder="Ej: cliente fidelizado, cortesía de bienvenida..."
                            value="{{ old('motivo_invitado') }}">
                    </div>

                    {{-- Método de pago --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            @foreach(['efectivo' => '💵 Efectivo', 'yape' => '📱 Yape', 'plin' => '📲 Plin'] as $val => $label)
                            <div class="form-check form-check-inline flex-grow-1 m-0">
                                <input class="form-check-input visually-hidden" type="radio" name="metodo_pago"
                                    id="pago_{{ $val }}" value="{{ $val }}" @checked(old('metodo_pago', 'efectivo') == $val) required>
                                <label class="btn btn-outline-success w-100 btn-sm" for="pago_{{ $val }}">
                                    {{ $label }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('metodo_pago')<div class="text-danger mt-1" style="font-size:13px;">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-check-circle me-1"></i> Registrar Venta de Cordial
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Referencia de precios --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px; letter-spacing:1px;">📋 Precios de Referencia</h6>
                <table class="table table-sm table-borderless mb-0">
                    <thead class="visually-hidden">
                        <tr><th scope="col">Tipo</th><th scope="col">Precio</th></tr>
                    </thead>
                    <tbody>
                    @foreach($tipos as $clave => $label)
                    @if($clave !== 'invitado')
                    <tr>
                        <td style="font-size:13px;">{{ $label }}</td>
                        <td class="text-end fw-bold" style="color:#16a34a;">
                            S/ {{ number_format($precios[$clave] ?? 0, 0) }}
                        </td>
                    </tr>
                    @endif
                    @endforeach
                    <tr class="table-warning">
                        <td style="font-size:13px;">🎁 Invitado (cortesía)</td>
                        <td class="text-end fw-bold text-warning">Gratis</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let precioActual = 0;

    function actualizarPrecio(select) {
        const opt = select.options[select.selectedIndex];
        precioActual = parseFloat(opt.dataset.precio) || 0;
        document.getElementById('precioMostrado').value = precioActual.toFixed(2);
        calcularTotal();
    }

    function calcularTotal() {
        const qty = parseInt(document.getElementById('cantidadInput').value) || 0;
        const esInv = document.getElementById('esInvitado').checked;
        const total = esInv ? 0 : precioActual * qty;
        document.getElementById('totalMostrado').value = total.toFixed(2);
    }

    function toggleInvitado(cb) {
        document.getElementById('motivoInvitadoDiv').style.display = cb.checked ? 'block' : 'none';
        calcularTotal();
    }

    function buscarClienteCordial() {
        const dni = document.getElementById('clienteDni').value.trim();
        if (!dni) return;
        fetch(`/api/clientes/dni?dni=${dni}`, {
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}
        })
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                document.getElementById('clienteId').value = data.cliente.id;
                document.getElementById('clienteNombre').textContent = data.cliente.nombre + ' ' + (data.cliente.apellido || '');
                const montoNat = parseFloat(data.cliente.acumulado_naturales || 0);
                document.getElementById('clienteFidelizacion').textContent = montoNat > 0 ? `💚 Acum: S/${montoNat.toFixed(2)}/500` : '';
                document.getElementById('clienteInfo').style.display = '';
            } else {
                alert('Cliente no encontrado.');
                document.getElementById('clienteId').value = '';
                document.getElementById('clienteInfo').style.display = 'none';
            }
        });
    }

    document.getElementById('clienteDni').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); buscarClienteCordial(); }
    });
</script>
@endsection
