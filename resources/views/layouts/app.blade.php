<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NATURACOR') - Sistema de Gestión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --nc-green-50: #f0fdf4;
            --nc-green-100: #dcfce7;
            --nc-green-200: #bbf7d0;
            --nc-green-300: #86efac;
            --nc-green-400: #4ade80;
            --nc-green-500: #22c55e;
            --nc-green-600: #16a34a;
            --nc-green-700: #15803d;
            --nc-green-800: #166534;
            --nc-sidebar-bg: #1a2e1a;
            --nc-sidebar-hover: rgba(74,222,128,0.15);
            --nc-sidebar-active: rgba(74,222,128,0.25);
            --nc-body-bg: #f8fdf8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--nc-body-bg);
            color: #1a1f1a;
        }
        /* Sidebar */
        .nc-sidebar {
            width: 260px; min-height: 100vh;
            background: var(--nc-sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 1000;
            display: flex; flex-direction: column;
            transition: width 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }
        .nc-sidebar.collapsed { width: 64px; }
        .nc-sidebar-brand {
            padding: 20px 18px; display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .nc-sidebar-brand .logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--nc-green-400), var(--nc-green-600));
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .nc-sidebar-brand .brand-name {
            font-size: 18px; font-weight: 700; color: white; letter-spacing: 0.5px;
            white-space: nowrap; overflow: hidden;
        }
        .nc-sidebar nav { padding: 12px 0; flex: 1; overflow-y: auto; }
        .nc-sidebar .nav-section {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 1px; color: rgba(255,255,255,0.3);
            padding: 12px 18px 4px; white-space: nowrap; overflow: hidden;
        }
        .nc-sidebar .nav-item a {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 18px; color: rgba(255,255,255,0.7);
            text-decoration: none; border-radius: 0 12px 12px 0;
            margin: 2px 8px 2px 0; font-size: 14px; font-weight: 500;
            transition: all 0.2s; white-space: nowrap; overflow: hidden;
        }
        .nc-sidebar .nav-item a:hover { background: var(--nc-sidebar-hover); color: white; }
        .nc-sidebar .nav-item a.active {
            background: var(--nc-sidebar-active);
            color: var(--nc-green-400); font-weight: 600;
            border-right: 3px solid var(--nc-green-400);
        }
        .nc-sidebar .nav-item i { font-size: 18px; flex-shrink: 0; }
        /* Topbar */
        .nc-topbar {
            position: fixed; top: 0; right: 0;
            left: 260px; height: 60px; background: white;
            border-bottom: 1px solid #e8f5e8; z-index: 999;
            display: flex; align-items: center; padding: 0 24px;
            gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: left 0.3s;
        }
        .nc-content {
            margin-left: 260px; margin-top: 60px;
            padding: 24px; min-height: calc(100vh - 60px);
            transition: margin-left 0.3s;
        }
        /* Cards */
        .nc-card {
            background: white; border-radius: 16px;
            border: 1px solid #e8f5e8; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .nc-card-header {
            font-size: 16px; font-weight: 600; color: #1a2e1a;
            margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;
        }
        /* KPI Cards */
        .kpi-card {
            background: white; border-radius: 16px;
            padding: 20px; border: 1px solid #e8f5e8;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .kpi-icon-green { background: #dcfce7; color: var(--nc-green-600); }
        .kpi-icon-blue { background: #dbeafe; color: #2563eb; }
        .kpi-icon-amber { background: #fef3c7; color: #d97706; }
        .kpi-icon-rose { background: #ffe4e6; color: #e11d48; }
        .kpi-value { font-size: 28px; font-weight: 700; color: #1a2e1a; }
        .kpi-label { font-size: 13px; color: #6b7280; font-weight: 500; }
        /* Buttons */
        .btn-naturacor { background: var(--nc-green-600); color: white; border: none; border-radius: 10px; font-weight: 500; padding: 8px 18px; }
        .btn-naturacor:hover { background: var(--nc-green-700); color: white; }
        .btn-naturacor-outline { border: 2px solid var(--nc-green-600); color: var(--nc-green-600); background: transparent; border-radius: 10px; font-weight: 500; padding: 8px 18px; }
        .btn-naturacor-outline:hover { background: var(--nc-green-50); color: var(--nc-green-700); }
        /* Badges */
        .badge-natural { background: #dcfce7; color: var(--nc-green-700); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-cordial { background: #fdf2f8; color: #9d174d; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-stock-ok { background: #dcfce7; color: var(--nc-green-700); }
        .badge-stock-low { background: #fef3c7; color: #d97706; }
        .badge-stock-zero { background: #ffe4e6; color: #e11d48; }
        /* Tables */
        .nc-table { border-radius: 12px; overflow: hidden; border: 1px solid #e8f5e8; }
        .nc-table th { background: var(--nc-green-50); color: var(--nc-green-800); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 12px 16px; }
        .nc-table td { border-color: #e8f5e8; padding: 12px 16px; vertical-align: middle; }
        .nc-table tbody tr:hover { background: var(--nc-green-50); }
        /* Forms */
        .nc-input { border-radius: 10px; border: 1.5px solid #d1fae5; padding: 10px 14px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
        .nc-input:focus { border-color: var(--nc-green-400); box-shadow: 0 0 0 3px rgba(74,222,128,0.15); outline: none; }
        /* Alerts */
        .alert-naturacor { background: #dcfce7; border: 1px solid var(--nc-green-200); color: var(--nc-green-800); border-radius: 12px; }
        .alert-danger-nc { background: #ffe4e6; border: 1px solid #fecdd3; color: #9f1239; border-radius: 12px; }
        /* Connection badge */
        .connection-badge { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 20px; }
        .connection-badge.online { background: #dcfce7; color: var(--nc-green-700); }
        .connection-badge.offline { background: #fef3c7; color: #d97706; }
        .connection-dot { width: 8px; height: 8px; border-radius: 50%; animation: pulse 2s infinite; }
        .connection-dot.online { background: var(--nc-green-500); }
        .connection-dot.offline { background: #f59e0b; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
        /* Responsive */
        @media(max-width:768px) {
            .nc-sidebar { width: 0; overflow: hidden; }
            .nc-topbar, .nc-content { left: 0; margin-left: 0; }
        }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--nc-green-200); border-radius: 3px; }
    </style>
    @yield('styles')
</head>
<body>
<!-- Sidebar -->
<aside class="nc-sidebar" id="sidebar">
    <div class="nc-sidebar-brand">
        <div class="logo">🌿</div>
        <span class="brand-name">NATURACOR</span>
    </div>
    <nav>
        <div class="nav-section">Principal</div>
        <div class="nav-item">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap"></i> <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ventas.pos') }}" class="{{ request()->routeIs('ventas.pos') ? 'active' : '' }}">
                <i class="bi bi-cart3"></i> <span>Punto de Venta</span>
            </a>
        </div>
        <div class="nav-section">Gestión</div>
        <div class="nav-item">
            <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> <span>Productos</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> <span>Clientes</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.index') || request()->routeIs('ventas.show') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> <span>Ventas</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('caja.index') }}" class="{{ request()->routeIs('caja*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> <span>Caja</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('fidelizacion.index') }}" class="{{ request()->routeIs('fidelizacion*') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i> <span>Fidelización</span>
            </a>
        </div>
        <div class="nav-section">Información</div>
        <div class="nav-item">
            <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> <span>Reportes</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('recetario.index') }}" class="{{ request()->routeIs('recetario*') ? 'active' : '' }}">
                <i class="bi bi-journal-medical"></i> <span>Recetario</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ia.index') }}" class="{{ request()->routeIs('ia*') ? 'active' : '' }}">
                <i class="bi bi-robot"></i> <span>Asistente IA</span>
            </a>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="nav-section">Administración</div>
        <div class="nav-item">
            <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> <span>Usuarios</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('sucursales.index') }}" class="{{ request()->routeIs('sucursales*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i> <span>Sucursales</span>
            </a>
        </div>
        @endif
    </nav>
    <div style="padding: 16px 18px; border-top: 1px solid rgba(255,255,255,0.08);">
        <div style="font-size: 12px; color: rgba(255,255,255,0.5);">v1.0 — NATURACOR © 2026</div>
    </div>
</aside>

<!-- Topbar -->
<header class="nc-topbar">
    <button class="btn btn-sm" onclick="toggleSidebar()" style="border: none; background: transparent; font-size: 20px; color: #4b5563;">
        <i class="bi bi-list"></i>
    </button>
    <div class="flex-grow-1">
        <h6 class="mb-0 fw-600" style="color: #1a2e1a;">@yield('page-title', 'Dashboard')</h6>
    </div>
    <!-- Conexión -->
    <div class="connection-badge online" id="connectionBadge">
        <span class="connection-dot online" id="connectionDot"></span>
        <span id="connectionText">En línea</span>
    </div>
    <!-- Sucursal -->
    @if(auth()->user()->sucursal)
    <span style="font-size: 12px; color: #6b7280; background: var(--nc-green-50); padding: 5px 12px; border-radius: 20px; border: 1px solid var(--nc-green-100);">
        <i class="bi bi-shop"></i> {{ auth()->user()->sucursal->nombre }}
    </span>
    @endif
    <!-- Usuario -->
    <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown"
            style="background: var(--nc-green-50); border: 1px solid var(--nc-green-100); border-radius: 30px; padding: 6px 14px;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--nc-green-400); display: flex; align-items: center; justify-content: center; color: white; font-size: 13px; font-weight: 700;">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <span style="font-size: 13px; font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ auth()->user()->name }}
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 12px; border: 1px solid #e8f5e8; box-shadow: 0 8px 24px rgba(0,0,0,0.1);">
            <li><span class="dropdown-item-text text-muted" style="font-size: 12px; padding: 8px 16px;">
                {{ auth()->user()->hasRole('admin') ? '👑 Administrador' : '👤 Empleado' }}
            </span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item" style="color: #e11d48;">
                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>

<!-- Main Content -->
<main class="nc-content">
    @if(session('success'))
        <div class="alert alert-naturacor alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1) brightness(0.5);"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger-nc alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.nc-topbar').style.left = document.getElementById('sidebar').classList.contains('collapsed') ? '64px' : '260px';
    document.querySelector('.nc-content').style.marginLeft = document.getElementById('sidebar').classList.contains('collapsed') ? '64px' : '260px';
}

// Online/Offline detection
function updateConnectionStatus() {
    const badge = document.getElementById('connectionBadge');
    const dot = document.getElementById('connectionDot');
    const text = document.getElementById('connectionText');
    if (navigator.onLine) {
        badge.className = 'connection-badge online';
        dot.className = 'connection-dot online';
        text.textContent = 'En línea';
    } else {
        badge.className = 'connection-badge offline';
        dot.className = 'connection-dot offline';
        text.textContent = 'Sin conexión';
    }
}
window.addEventListener('online', updateConnectionStatus);
window.addEventListener('offline', updateConnectionStatus);
updateConnectionStatus();

// CSRF Token for AJAX
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Global confirm handler for all delete forms
document.addEventListener('submit', function(e) {
    const form = e.target;
    const msg = form.getAttribute('data-confirm');
    if (msg) {
        if (!window.confirm(msg)) {
            e.preventDefault();
            return false;
        }
    }
});

</script>
@yield('scripts')
</body>
</html>
