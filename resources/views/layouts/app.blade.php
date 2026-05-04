<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NATURACOR') - Sistema de Gestión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --neon:       #28c76f;
            --neon-dim:   rgba(40, 199, 111, 0.18);
            --neon-glow:  rgba(40, 199, 111, 0.40);
            --neon-soft:  rgba(40, 199, 111, 0.08);
            --emerald:    #0e4b2a;
            --base-bg:    #071a10;
            --white:      #ffffff;
            --text-sec:   #9caea4;
            --text-muted: rgba(255,255,255,0.28);

            --glass-bg:     rgba(255,255,255,0.03);
            --glass-border: rgba(255,255,255,0.10);
            --glass-shadow: 0 8px 32px rgba(0,0,0,0.35);
            --glass-blur:   blur(18px);

            --sidebar-w:         215px;
            --sidebar-collapsed: 60px;
            --topbar-h:          54px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--base-bg);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ══ CANVAS FONDO ══════════════════════════════════════════ */
        #bg-canvas {
            position: fixed;
            inset: 0;
            width: 100%; height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        /* ══ FADE-IN-UP ════════════════════════════════════════════ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .nc-content > * { animation: fadeInUp 0.55s cubic-bezier(0.16,1,0.3,1) both; }
        .nc-content > *:nth-child(1) { animation-delay: 0.05s; }
        .nc-content > *:nth-child(2) { animation-delay: 0.12s; }
        .nc-content > *:nth-child(3) { animation-delay: 0.19s; }
        .nc-content > *:nth-child(4) { animation-delay: 0.26s; }
        .nc-content > *:nth-child(5) { animation-delay: 0.33s; }
        .nc-content > *:nth-child(6) { animation-delay: 0.40s; }

        /* ══ SIDEBAR ════════════════════════════════════════════════ */
        .nc-sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: rgba(7, 26, 16, 0.55);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: width 0.3s cubic-bezier(0.4,0,0.2,1);
            border-right: 1px solid rgba(255,255,255,0.07);
            box-shadow: 4px 0 40px rgba(0,0,0,0.45),
                        inset -1px 0 0 rgba(40,199,111,0.08);
            overflow: hidden;
        }
        .nc-sidebar.collapsed { width: var(--sidebar-collapsed); }

        .nc-sidebar-brand {
            padding: 16px 14px;
            display: flex; align-items: center; gap: 11px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .nc-sidebar-brand .logo {
            width: 34px; height: 34px;
            border-radius: 9px;
            background: rgba(40,199,111,0.15);
            border: 1px solid rgba(40,199,111,0.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; flex-shrink: 0;
            box-shadow: 0 0 16px rgba(40,199,111,0.25),
                        inset 0 0 8px rgba(40,199,111,0.12);
            animation: logoGlow 3s ease-in-out infinite;
        }
        @keyframes logoGlow {
            0%,100% { box-shadow: 0 0 16px rgba(40,199,111,0.25), inset 0 0 8px rgba(40,199,111,0.12); }
            50%      { box-shadow: 0 0 30px rgba(40,199,111,0.48), inset 0 0 14px rgba(40,199,111,0.22); }
        }
        .nc-sidebar-brand .brand-name {
            font-size: 15px; font-weight: 800;
            color: var(--white); letter-spacing: 2.5px;
            white-space: nowrap; overflow: hidden;
            transition: opacity 0.2s;
        }
        .nc-sidebar.collapsed .brand-name,
        .nc-sidebar.collapsed .nav-label,
        .nc-sidebar.collapsed .nav-section { opacity: 0; pointer-events: none; }

        .nc-sidebar nav {
            padding: 8px 0; flex: 1;
            overflow-y: auto; overflow-x: hidden;
        }
        .nc-sidebar nav::-webkit-scrollbar { width: 3px; }
        .nc-sidebar nav::-webkit-scrollbar-thumb { background: rgba(40,199,111,0.15); border-radius: 2px; }

        .nav-section {
            font-size: 9.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.8px;
            color: rgba(40,199,111,0.38);
            padding: 11px 14px 3px;
            white-space: nowrap; transition: opacity 0.2s;
        }
        .nav-item a {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 14px;
            color: var(--text-sec);
            text-decoration: none;
            border-radius: 0 10px 10px 0;
            margin: 1px 8px 1px 0;
            font-size: 13px; font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap; overflow: hidden;
        }
        .nav-item a:hover {
            background: rgba(40,199,111,0.09);
            color: var(--white);
        }
        .nav-item a.active {
            background: rgba(40,199,111,0.15);
            color: var(--neon);
            font-weight: 600;
            border-right: 2px solid var(--neon);
            box-shadow: inset 0 0 12px rgba(40,199,111,0.10),
                        4px 0 12px rgba(40,199,111,0.12);
        }
        .nav-item i { font-size: 16px; flex-shrink: 0; }
        .nav-label  { transition: opacity 0.2s; }

        .nc-sidebar-footer {
            padding: 12px 14px;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 10px; color: rgba(255,255,255,0.18);
            white-space: nowrap; overflow: hidden; letter-spacing: 0.5px;
        }

        /* ══ TOPBAR ═════════════════════════════════════════════════ */
        .nc-topbar {
            position: fixed;
            top: 0; right: 0;
            left: var(--sidebar-w);
            height: var(--topbar-h);
            background: rgba(7,26,16,0.55);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            z-index: 999;
            display: flex; align-items: center;
            padding: 0 18px; gap: 12px;
            box-shadow: 0 2px 24px rgba(0,0,0,0.40),
                        inset 0 -1px 0 rgba(40,199,111,0.07);
            transition: left 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .nc-topbar .page-title {
            font-size: 14.5px; font-weight: 600;
            color: rgba(255,255,255,0.88);
        }

        /* ══ CONTENT ════════════════════════════════════════════════ */
        .nc-content {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-h));
            transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1);
            position: relative; z-index: 1;
        }

        /* ══ GLASS CARDS (propias) ══════════════════════════════════ */
        .nc-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border-radius: 14px;
            border-top:   1px solid rgba(255,255,255,0.10);
            border-left:  1px solid rgba(255,255,255,0.10);
            border-bottom:1px solid rgba(255,255,255,0.04);
            border-right: 1px solid rgba(255,255,255,0.04);
            padding: 20px;
            box-shadow: var(--glass-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .nc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(0,0,0,0.48),
                        0 0 0 1px rgba(40,199,111,0.10) inset;
        }
        .nc-card-header {
            font-size: 14px; font-weight: 600;
            color: rgba(255,255,255,0.88);
            margin-bottom: 14px;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* ══ KPI CARDS ══════════════════════════════════════════════ */
        .kpi-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border-radius: 14px; padding: 18px;
            border-top:   1px solid rgba(255,255,255,0.10);
            border-left:  1px solid rgba(255,255,255,0.10);
            border-bottom:1px solid rgba(255,255,255,0.04);
            border-right: 1px solid rgba(255,255,255,0.04);
            box-shadow: var(--glass-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 44px rgba(0,0,0,0.52),
                        0 0 22px rgba(40,199,111,0.08);
            border-top-color:  rgba(40,199,111,0.25);
            border-left-color: rgba(40,199,111,0.25);
        }
        .kpi-icon { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .kpi-icon-green { background: rgba(40,199,111,0.13); color: var(--neon); }
        .kpi-icon-blue  { background: rgba(52,152,219,0.13); color: #3498db; }
        .kpi-icon-amber { background: rgba(243,156,18,0.13); color: #f39c12; }
        .kpi-icon-rose  { background: rgba(231,76,60,0.13);  color: #e74c3c; }
        .kpi-value { font-size: 26px; font-weight: 700; color: var(--white); }
        .kpi-label { font-size: 12.5px; color: var(--text-sec); font-weight: 500; }

        /* ══ BADGES DE ESTADO ═══════════════════════════════════════ */
        .badge-hoy      { background: rgba(52,152,219,0.15); color: #3498db; padding: 2px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }
        .badge-mes      { background: rgba(243,156,18,0.15); color: #f39c12; padding: 2px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }
        .badge-atencion { background: rgba(231,76,60,0.15);  color: #e74c3c; padding: 2px 9px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }

        .badge-natural  { background: rgba(40,199,111,0.14); color: var(--neon); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; border: 1px solid rgba(40,199,111,0.2); }
        .badge-cordial  { background: rgba(244,114,182,0.14); color: #f472b6; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; border: 1px solid rgba(244,114,182,0.2); }
        .badge-stock-ok   { background: rgba(40,199,111,0.14); color: var(--neon); border: 1px solid rgba(40,199,111,0.2); padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-stock-low  { background: rgba(243,156,18,0.14); color: #f39c12;    border: 1px solid rgba(243,156,18,0.2); padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-stock-zero { background: rgba(231,76,60,0.14);  color: #e74c3c;   border: 1px solid rgba(231,76,60,0.2);  padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }

        /* ══ BOTONES PROPIOS ════════════════════════════════════════ */
        .btn-naturacor {
            background: rgba(40,199,111,0.15);
            color: var(--white);
            border: 1px solid var(--neon);
            border-radius: 9px;
            font-weight: 600; padding: 8px 16px; font-size: 13px;
            font-family: 'Sora', sans-serif;
            transition: all 0.22s ease;
            box-shadow: 0 0 12px rgba(40,199,111,0.25),
                        inset 0 0 8px rgba(40,199,111,0.10);
        }
        .btn-naturacor:hover {
            color: var(--white);
            background: rgba(40,199,111,0.28);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 0 22px rgba(40,199,111,0.55),
                        0 0 44px rgba(40,199,111,0.22),
                        inset 0 0 14px rgba(40,199,111,0.20);
        }
        .btn-naturacor:active { transform: translateY(0) scale(1); }

        .btn-naturacor-outline {
            border: 1px solid rgba(40,199,111,0.40);
            color: var(--neon);
            background: rgba(40,199,111,0.05);
            border-radius: 9px; font-weight: 500; padding: 8px 16px;
            font-size: 13px; font-family: 'Sora', sans-serif;
            transition: all 0.22s ease;
        }
        .btn-naturacor-outline:hover {
            background: rgba(40,199,111,0.12);
            color: #86efac;
            border-color: var(--neon);
            box-shadow: 0 0 16px rgba(40,199,111,0.32);
            transform: translateY(-1px);
        }

        /* ══ TABLES PROPIAS ═════════════════════════════════════════ */
        .nc-table { border-radius: 11px; overflow: hidden; border: 1px solid rgba(255,255,255,0.07); }
        .nc-table th {
            background: rgba(40,199,111,0.06);
            color: rgba(156,174,164,0.85);
            font-weight: 600; font-size: 10.5px;
            text-transform: uppercase; letter-spacing: 1px;
            border: none; padding: 11px 14px;
        }
        .nc-table td {
            border-color: rgba(255,255,255,0.05);
            padding: 11px 14px; vertical-align: middle;
            color: rgba(255,255,255,0.78); font-size: 13.5px;
        }
        .nc-table tbody tr { background: transparent; transition: background 0.14s; }
        .nc-table tbody tr:hover { background: rgba(40,199,111,0.05); }

        /* ══ FORMS PROPIOS ══════════════════════════════════════════ */
        .nc-input {
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,0.10);
            padding: 10px 13px; font-size: 13.5px;
            background: rgba(255,255,255,0.04);
            color: var(--white);
            transition: all 0.2s;
            font-family: 'Sora', sans-serif;
        }
        .nc-input::placeholder { color: rgba(255,255,255,0.22); }
        .nc-input:focus {
            border-color: rgba(40,199,111,0.55);
            box-shadow: 0 0 0 3px rgba(40,199,111,0.09),
                        0 0 14px rgba(40,199,111,0.12);
            outline: none;
            background: rgba(255,255,255,0.06); color: var(--white);
        }

        /* Bootstrap form overrides */
        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255,255,255,0.10) !important;
            color: var(--white) !important;
            border-radius: 9px !important;
            font-family: 'Sora', sans-serif !important;
            font-size: 13.5px !important;
        }
        .form-control::placeholder { color: #9caea4 !important; }
        .form-control:focus, .form-select:focus {
            background: rgba(0, 0, 0, 0.35) !important;
            border-color: #28c76f !important;
            box-shadow: 0 0 0 3px rgba(40,199,111,0.12),
                        0 0 8px rgba(40,199,111,0.30) !important;
            color: var(--white) !important;
        }
        .form-select option { background: #071a10; color: var(--white); }
        .form-label {
            color: rgba(156,174,164,0.85);
            font-size: 11px; font-weight: 600;
            letter-spacing: 1px; text-transform: uppercase;
        }

        /* ══ ALERTS PROPIAS ═════════════════════════════════════════ */
        .alert-naturacor {
            background: rgba(40,199,111,0.08);
            border: 1px solid rgba(40,199,111,0.25);
            color: #86efac; border-radius: 11px; font-size: 13.5px;
            backdrop-filter: blur(10px);
        }
        .alert-danger-nc {
            background: rgba(231,76,60,0.08);
            border: 1px solid rgba(231,76,60,0.25);
            color: #fb7185; border-radius: 11px; font-size: 13.5px;
            backdrop-filter: blur(10px);
        }

        /* ══ CONNECTION BADGE ═══════════════════════════════════════ */
        .connection-badge {
            display: flex; align-items: center; gap: 6px;
            font-size: 11.5px; font-weight: 500;
            padding: 5px 12px; border-radius: 20px;
        }
        .connection-badge.online  { background: rgba(40,199,111,0.10); color: var(--neon); border: 1px solid rgba(40,199,111,0.20); }
        .connection-badge.offline { background: rgba(243,156,18,0.10); color: #f39c12;     border: 1px solid rgba(243,156,18,0.20); }
        .connection-dot { width: 7px; height: 7px; border-radius: 50%; animation: dotPulse 2s infinite; }
        .connection-dot.online  { background: var(--neon); }
        .connection-dot.offline { background: #f39c12; }
        @keyframes dotPulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

        /* ══ DROPDOWN ═══════════════════════════════════════════════ */
        .dropdown-menu {
            background: rgba(7,26,16,0.96) !important;
            backdrop-filter: blur(22px) !important;
            border: 1px solid rgba(255,255,255,0.09) !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 48px rgba(0,0,0,0.6) !important;
        }
        .dropdown-item { color: var(--text-sec) !important; font-size: 13px; font-family: 'Sora', sans-serif; }
        .dropdown-item:hover { background: rgba(40,199,111,0.09) !important; color: var(--white) !important; }
        .dropdown-divider { border-color: rgba(255,255,255,0.07) !important; }
        .dropdown-item-text { color: rgba(255,255,255,0.30) !important; }

        /* ══ MODAL ══════════════════════════════════════════════════ */
        .modal-content {
            background: rgba(7,26,16,0.96) !important;
            backdrop-filter: blur(24px) !important;
            border: 1px solid rgba(255,255,255,0.09) !important;
            border-radius: 16px !important; color: var(--white) !important;
        }
        .modal-header { border-bottom: 1px solid rgba(255,255,255,0.07) !important; }
        .modal-footer { border-top:  1px solid rgba(255,255,255,0.07) !important; }
        .modal-title { color: var(--white) !important; font-weight: 600; }
        .btn-close { filter: invert(1) brightness(0.6) !important; }

        /* ══ SCROLLBAR ══════════════════════════════════════════════ */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(40,199,111,0.18); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(40,199,111,0.35); }

        /* ══ RESPONSIVE ═════════════════════════════════════════════ */
        @media(max-width: 768px) {
            .nc-sidebar { width: 0 !important; overflow: hidden; }
            .nc-topbar  { left: 0 !important; }
            .nc-content { margin-left: 0 !important; }
        }

        .text-muted { color: var(--text-sec) !important; }
        hr { border-color: rgba(255,255,255,0.07) !important; }

        /* ════════════════════════════════════════════════════════════
           DARK GLASSMORPHISM — OVERRIDE GLOBAL BOOTSTRAP
           Elimina todos los fondos blancos en todas las vistas
        ════════════════════════════════════════════════════════════ */

        /* ── TARJETAS BOOTSTRAP ─────────────────────────────────── */
        .card {
            background: rgba(7, 26, 16, 0.50) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35) !important;
            color: var(--white) !important;
        }
        .card-header {
            background: rgba(40, 199, 111, 0.06) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07) !important;
            color: rgba(255, 255, 255, 0.88) !important;
            font-weight: 600 !important;
            border-radius: 14px 14px 0 0 !important;
        }
        .card-body  { color: rgba(255, 255, 255, 0.82) !important; }
        .card-footer {
            background: rgba(255, 255, 255, 0.02) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.07) !important;
            color: var(--text-sec) !important;
        }
        .card-title { color: var(--white) !important; }
        .card-text  { color: var(--text-sec) !important; }

        /* ── FONDOS BLANCOS GENÉRICOS ──────────────────────────── */
        .bg-white, .bg-light {
            background: rgba(7, 26, 16, 0.50) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
        }
        .bg-body, .bg-body-tertiary {
            background: transparent !important;
        }

        /* ── TABLAS BOOTSTRAP COMPLETAS ────────────────────────── */
        .table {
            color: rgba(255, 255, 255, 0.82) !important;
            border-color: rgba(255, 255, 255, 0.06) !important;
        }
        .table > :not(caption) > * > * {
            background-color: transparent !important;
            color: rgba(255, 255, 255, 0.82) !important;
            border-bottom-color: rgba(255, 255, 255, 0.05) !important;
            padding: 11px 14px !important;
        }
        .table thead > tr > th,
        .table > thead th {
            background: rgba(40, 199, 111, 0.07) !important;
            color: rgba(156, 174, 164, 0.90) !important;
            font-size: 10.5px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            border-bottom: 1px solid rgba(40, 199, 111, 0.14) !important;
        }
        .table tbody tr { background: transparent !important; }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255, 255, 255, 0.015) !important;
        }
        .table-hover > tbody > tr:hover > * {
            background-color: rgba(40, 199, 111, 0.07) !important;
            color: var(--white) !important;
        }
        .table-bordered,
        .table-bordered > :not(caption) > * > * {
            border-color: rgba(255, 255, 255, 0.06) !important;
        }
        .table-responsive {
            border-radius: 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.07) !important;
        }

        /* ── DATATABLES (jQuery DataTables) ────────────────────── */
        .dataTables_wrapper,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-sec) !important;
            background: transparent !important;
            font-family: 'Sora', sans-serif !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-sec) !important;
            border-radius: 7px !important;
            font-family: 'Sora', sans-serif !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(40, 199, 111, 0.15) !important;
            color: var(--neon) !important;
            border: 1px solid rgba(40, 199, 111, 0.30) !important;
        }
        table.dataTable thead th {
            border-bottom: 1px solid rgba(40, 199, 111, 0.15) !important;
        }
        table.dataTable tbody tr { background: transparent !important; }
        table.dataTable.stripe tbody tr.odd,
        table.dataTable.display tbody tr.odd {
            background: rgba(255, 255, 255, 0.015) !important;
        }
        table.dataTable.hover tbody tr:hover,
        table.dataTable.display tbody tr:hover {
            background: rgba(40, 199, 111, 0.06) !important;
        }

        /* ── INPUTS / SELECTS EN CUALQUIER VISTA ────────────────── */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        input[type="search"],
        input[type="tel"],
        input[type="url"],
        textarea,
        select {
            background: rgba(0, 0, 0, 0.22) !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            color: var(--white) !important;
            border-radius: 9px !important;
            font-family: 'Sora', sans-serif !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
        }
        input::placeholder,
        textarea::placeholder { color: #9caea4 !important; }
        input:focus,
        textarea:focus,
        select:focus {
            border-color: #28c76f !important;
            box-shadow: 0 0 0 3px rgba(40, 199, 111, 0.12),
                        0 0 8px rgba(40, 199, 111, 0.30) !important;
            background: rgba(0, 0, 0, 0.32) !important;
            outline: none !important;
            color: var(--white) !important;
        }
        select option {
            background: #071a10 !important;
            color: var(--white) !important;
        }

        /* ── CALENDARIOS NATIVOS ────────────────────────────────── */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(0.7);
            cursor: pointer;
        }

        /* ── CHECKBOX Y RADIO ───────────────────────────────────── */
        input[type="checkbox"],
        input[type="radio"] {
            accent-color: #28c76f !important;
        }

        /* ── LABELS Y TIPOGRAFÍA GENERAL ────────────────────────── */
        label { color: rgba(156, 174, 164, 0.85) !important; }
        h1, h2, h3, h4, h5, h6 { color: var(--white) !important; }

        /* ── BADGES BOOTSTRAP ───────────────────────────────────── */
        .badge {
            border-radius: 20px !important;
            font-weight: 600 !important;
            padding: 3px 10px !important;
        }
        .badge.bg-success  { background: rgba(40, 199, 111, 0.18) !important; color: #28c76f !important; border: 1px solid rgba(40,199,111,0.25) !important; }
        .badge.bg-danger   { background: rgba(231, 76, 60, 0.18)  !important; color: #e74c3c !important; border: 1px solid rgba(231,76,60,0.25) !important; }
        .badge.bg-warning  { background: rgba(243, 156, 18, 0.18) !important; color: #f39c12 !important; border: 1px solid rgba(243,156,18,0.25) !important; }
        .badge.bg-info     { background: rgba(52, 152, 219, 0.18) !important; color: #3498db !important; border: 1px solid rgba(52,152,219,0.25) !important; }
        .badge.bg-primary  { background: rgba(52, 152, 219, 0.18) !important; color: #3498db !important; border: 1px solid rgba(52,152,219,0.25) !important; }
        .badge.bg-secondary{ background: rgba(156,174,164, 0.15)  !important; color: #9caea4 !important; border: 1px solid rgba(156,174,164,0.20) !important; }

        /* ── BOTONES BOOTSTRAP ──────────────────────────────────── */
        .btn { font-family: 'Sora', sans-serif !important; border-radius: 9px !important; font-weight: 600 !important; transition: all 0.22s ease !important; }

        .btn-primary {
            background: rgba(40, 199, 111, 0.18) !important;
            border: 1px solid #28c76f !important;
            color: var(--white) !important;
            box-shadow: 0 0 12px rgba(40, 199, 111, 0.28) !important;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: rgba(40, 199, 111, 0.30) !important;
            border-color: #28c76f !important;
            color: var(--white) !important;
            box-shadow: 0 0 22px rgba(40, 199, 111, 0.50) !important;
            transform: translateY(-1px) !important;
        }
        .btn-success {
            background: rgba(40, 199, 111, 0.18) !important;
            border: 1px solid #28c76f !important;
            color: var(--white) !important;
            box-shadow: 0 0 10px rgba(40, 199, 111, 0.25) !important;
        }
        .btn-success:hover, .btn-success:focus {
            background: rgba(40, 199, 111, 0.30) !important;
            border-color: #28c76f !important;
            color: var(--white) !important;
            box-shadow: 0 0 20px rgba(40, 199, 111, 0.50) !important;
            transform: translateY(-1px) !important;
        }
        .btn-danger {
            background: rgba(231, 76, 60, 0.15) !important;
            border: 1px solid rgba(231, 76, 60, 0.55) !important;
            color: #fb7185 !important;
        }
        .btn-danger:hover, .btn-danger:focus {
            background: rgba(231, 76, 60, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 0 14px rgba(231, 76, 60, 0.40) !important;
            transform: translateY(-1px) !important;
        }
        .btn-warning {
            background: rgba(243, 156, 18, 0.15) !important;
            border: 1px solid rgba(243, 156, 18, 0.55) !important;
            color: #f39c12 !important;
        }
        .btn-warning:hover, .btn-warning:focus {
            background: rgba(243, 156, 18, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 0 14px rgba(243, 156, 18, 0.40) !important;
            transform: translateY(-1px) !important;
        }
        .btn-info {
            background: rgba(52, 152, 219, 0.15) !important;
            border: 1px solid rgba(52, 152, 219, 0.55) !important;
            color: #3498db !important;
        }
        .btn-info:hover, .btn-info:focus {
            background: rgba(52, 152, 219, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 0 14px rgba(52, 152, 219, 0.40) !important;
            transform: translateY(-1px) !important;
        }
        .btn-secondary {
            background: rgba(156, 174, 164, 0.10) !important;
            border: 1px solid rgba(156, 174, 164, 0.28) !important;
            color: #9caea4 !important;
        }
        .btn-secondary:hover, .btn-secondary:focus {
            background: rgba(156, 174, 164, 0.20) !important;
            color: var(--white) !important;
        }
        .btn-outline-primary {
            border-color: rgba(40, 199, 111, 0.50) !important;
            color: var(--neon) !important;
            background: transparent !important;
        }
        .btn-outline-primary:hover {
            background: rgba(40, 199, 111, 0.14) !important;
            color: #86efac !important;
            box-shadow: 0 0 14px rgba(40, 199, 111, 0.30) !important;
        }
        .btn-outline-secondary {
            border-color: rgba(255, 255, 255, 0.18) !important;
            color: var(--text-sec) !important;
            background: transparent !important;
        }
        .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.06) !important;
            color: var(--white) !important;
        }
        .btn-outline-danger {
            border-color: rgba(231, 76, 60, 0.50) !important;
            color: #e74c3c !important;
            background: transparent !important;
        }
        .btn-outline-danger:hover {
            background: rgba(231, 76, 60, 0.14) !important;
            color: #fb7185 !important;
        }
        .btn-light {
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: var(--white) !important;
        }
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.12) !important;
            color: var(--white) !important;
        }

        /* ── PAGINACIÓN BOOTSTRAP ───────────────────────────────── */
        .pagination { gap: 3px; }
        .page-link {
            background: rgba(7, 26, 16, 0.55) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: var(--text-sec) !important;
            border-radius: 8px !important;
            font-family: 'Sora', sans-serif !important;
            transition: all 0.18s !important;
        }
        .page-link:hover {
            background: rgba(40, 199, 111, 0.12) !important;
            color: var(--neon) !important;
            border-color: rgba(40, 199, 111, 0.30) !important;
        }
        .page-item.active .page-link {
            background: rgba(40, 199, 111, 0.22) !important;
            border-color: #28c76f !important;
            color: var(--neon) !important;
            box-shadow: 0 0 10px rgba(40, 199, 111, 0.30) !important;
        }
        .page-item.disabled .page-link {
            background: rgba(7, 26, 16, 0.30) !important;
            color: rgba(156, 174, 164, 0.35) !important;
        }

        /* ── ALERTAS BOOTSTRAP ──────────────────────────────────── */
        .alert {
            backdrop-filter: blur(10px) !important;
            border-radius: 11px !important;
            font-family: 'Sora', sans-serif !important;
        }
        .alert-success {
            background: rgba(40, 199, 111, 0.10) !important;
            border-color: rgba(40, 199, 111, 0.28) !important;
            color: #86efac !important;
        }
        .alert-danger {
            background: rgba(231, 76, 60, 0.10) !important;
            border-color: rgba(231, 76, 60, 0.28) !important;
            color: #fca5a5 !important;
        }
        .alert-warning {
            background: rgba(243, 156, 18, 0.10) !important;
            border-color: rgba(243, 156, 18, 0.28) !important;
            color: #fcd34d !important;
        }
        .alert-info {
            background: rgba(52, 152, 219, 0.10) !important;
            border-color: rgba(52, 152, 219, 0.28) !important;
            color: #93c5fd !important;
        }

        /* ── LINKS ──────────────────────────────────────────────── */
        a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):not(.nav-item a) {
            color: #28c76f;
            text-decoration: none;
            transition: color 0.18s;
        }
        a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):not(.nav-item a):hover {
            color: #86efac;
        }

        /* ── TEXTO MUTED / SECONDARY ────────────────────────────── */
        .text-muted, .text-secondary { color: #9caea4 !important; }
        .text-success { color: #28c76f !important; }
        .text-danger  { color: #e74c3c !important; }
        .text-warning { color: #f39c12 !important; }
        .text-info    { color: #3498db !important; }
        .text-white   { color: #ffffff !important; }

        /* ── LIST GROUP ─────────────────────────────────────────── */
        .list-group-item {
            background: rgba(7, 26, 16, 0.45) !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
            color: rgba(255, 255, 255, 0.82) !important;
        }
        .list-group-item:hover {
            background: rgba(40, 199, 111, 0.07) !important;
        }
        .list-group-item.active {
            background: rgba(40, 199, 111, 0.18) !important;
            border-color: rgba(40, 199, 111, 0.30) !important;
            color: var(--neon) !important;
        }

        /* ── ACCORDION ──────────────────────────────────────────── */
        .accordion-item {
            background: rgba(7, 26, 16, 0.45) !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
            overflow: hidden;
        }
        .accordion-button {
            background: rgba(7, 26, 16, 0.55) !important;
            color: var(--white) !important;
            font-family: 'Sora', sans-serif !important;
            font-weight: 600 !important;
        }
        .accordion-button:not(.collapsed) {
            background: rgba(40, 199, 111, 0.10) !important;
            color: var(--neon) !important;
            box-shadow: none !important;
        }
        .accordion-button::after { filter: invert(1) brightness(0.7) !important; }
        .accordion-body {
            background: rgba(7, 26, 16, 0.35) !important;
            color: rgba(255, 255, 255, 0.80) !important;
        }

        /* ── TABS ───────────────────────────────────────────────── */
        .nav-tabs { border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important; }
        .nav-tabs .nav-link {
            color: var(--text-sec) !important;
            border: 1px solid transparent !important;
            border-radius: 9px 9px 0 0 !important;
            font-family: 'Sora', sans-serif !important;
            transition: all 0.18s !important;
        }
        .nav-tabs .nav-link:hover {
            color: var(--white) !important;
            background: rgba(40, 199, 111, 0.07) !important;
            border-color: rgba(255, 255, 255, 0.07) !important;
        }
        .nav-tabs .nav-link.active {
            background: rgba(40, 199, 111, 0.14) !important;
            border-color: rgba(40, 199, 111, 0.25) rgba(40, 199, 111, 0.25) transparent !important;
            color: var(--neon) !important;
        }
        .nav-pills .nav-link {
            color: var(--text-sec) !important;
            border-radius: 9px !important;
            font-family: 'Sora', sans-serif !important;
        }
        .nav-pills .nav-link.active,
        .nav-pills .show > .nav-link {
            background: rgba(40, 199, 111, 0.18) !important;
            color: var(--neon) !important;
        }

        /* ── PROGRESS BAR ───────────────────────────────────────── */
        .progress {
            background: rgba(255, 255, 255, 0.06) !important;
            border-radius: 20px !important;
        }
        .progress-bar {
            background: linear-gradient(90deg, #28c76f, #0e4b2a) !important;
            box-shadow: 0 0 8px rgba(40, 199, 111, 0.40) !important;
        }

        /* ── SPINNER ────────────────────────────────────────────── */
        .spinner-border, .spinner-grow { color: #28c76f !important; }

        /* ── TOOLTIPS & POPOVERS ────────────────────────────────── */
        .tooltip-inner {
            background: rgba(7, 26, 16, 0.95) !important;
            border: 1px solid rgba(40, 199, 111, 0.25) !important;
            color: var(--white) !important;
            border-radius: 8px !important;
            font-family: 'Sora', sans-serif !important;
            font-size: 12px !important;
        }
        .popover {
            background: rgba(7, 26, 16, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.09) !important;
            border-radius: 14px !important;
        }
        .popover-header {
            background: rgba(40, 199, 111, 0.08) !important;
            color: var(--white) !important;
            border-bottom-color: rgba(255, 255, 255, 0.07) !important;
        }
        .popover-body { color: rgba(255, 255, 255, 0.80) !important; }

        /* ── INPUT GROUP ────────────────────────────────────────── */
        .input-group-text {
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            color: #9caea4 !important;
            font-family: 'Sora', sans-serif !important;
        }

        /* ── OFFCANVAS ──────────────────────────────────────────── */
        .offcanvas {
            background: rgba(7, 26, 16, 0.96) !important;
            backdrop-filter: blur(24px) !important;
            border-color: rgba(255, 255, 255, 0.09) !important;
        }
        .offcanvas-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.07) !important;
            color: var(--white) !important;
        }
        .offcanvas-body { color: rgba(255, 255, 255, 0.82) !important; }

        /* ── TOAST ──────────────────────────────────────────────── */
        .toast {
            background: rgba(7, 26, 16, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.09) !important;
            backdrop-filter: blur(16px) !important;
            color: var(--white) !important;
            border-radius: 12px !important;
        }
        .toast-header {
            background: rgba(40, 199, 111, 0.07) !important;
            border-bottom-color: rgba(255, 255, 255, 0.07) !important;
            color: var(--white) !important;
        }

        /* ── BREADCRUMB ─────────────────────────────────────────── */
        .breadcrumb-item { color: var(--text-sec) !important; }
        .breadcrumb-item.active { color: var(--neon) !important; }
        .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.25) !important; }

        /* ── SEPARADORES ────────────────────────────────────────── */
        hr { border-color: rgba(255, 255, 255, 0.07) !important; }

    </style>
    @yield('styles')
</head>
<body>

<!-- CANVAS FONDO ANIMADO -->
<canvas id="bg-canvas"></canvas>

<!-- ══ SIDEBAR ══ -->
<aside class="nc-sidebar" id="sidebar">
    <div class="nc-sidebar-brand">
        <div class="logo">🌿</div>
        <span class="brand-name">NATURACOR</span>
    </div>
    <nav>
        <div class="nav-section">Principal</div>
        <div class="nav-item">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap"></i><span class="nav-label">Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ventas.pos') }}" class="{{ request()->routeIs('ventas.pos') ? 'active' : '' }}">
                <i class="bi bi-cart3"></i><span class="nav-label">Punto de Venta</span>
            </a>
        </div>
        <div class="nav-section">Gestión</div>
        <div class="nav-item">
            @php $stockBajoCount = \App\Models\Producto::where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')->when(auth()->user()->sucursal_id, fn($q) => $q->where('sucursal_id', auth()->user()->sucursal_id))->count(); @endphp
            <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos*') ? 'active' : '' }}" style="position:relative;">
                <i class="bi bi-box-seam"></i><span class="nav-label">Productos</span>
                @if($stockBajoCount > 0)
                <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;min-width:20px;text-align:center;box-shadow:0 0 8px rgba(239,68,68,0.5);">{{ $stockBajoCount }}</span>
                @endif
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes*') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span class="nav-label">Clientes</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.index') || request()->routeIs('ventas.show') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i><span class="nav-label">Ventas</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('caja.index') }}" class="{{ request()->routeIs('caja*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i><span class="nav-label">Caja</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('fidelizacion.index') }}" class="{{ request()->routeIs('fidelizacion*') ? 'active' : '' }}">
                <i class="bi bi-star-fill"></i><span class="nav-label">Fidelización</span>
            </a>
        </div>
        <div class="nav-section">Información</div>
        @if(auth()->user()->isAdmin())
        <div class="nav-item">
            <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i><span class="nav-label">Reportes</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('metricas.recomendaciones') }}" class="{{ request()->routeIs('metricas.recomendaciones') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i><span class="nav-label">Métricas reco.</span>
            </a>
        </div>
        @endif
        <div class="nav-item">
            <a href="{{ route('recetario.index') }}" class="{{ request()->routeIs('recetario*') ? 'active' : '' }}">
                <i class="bi bi-journal-medical"></i><span class="nav-label">Recetario</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('reclamos.index') }}" class="{{ request()->routeIs('reclamos*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-circle"></i><span class="nav-label">Reclamos</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('ia.index') }}" class="{{ request()->routeIs('ia*') ? 'active' : '' }}">
                <i class="bi bi-robot"></i><span class="nav-label">Asistente IA</span>
            </a>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="nav-section">Administración</div>
        <div class="nav-item">
            <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i><span class="nav-label">Usuarios</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('sucursales.index') }}" class="{{ request()->routeIs('sucursales*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i><span class="nav-label">Sucursales</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i><span class="nav-label">Auditoría</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="{{ route('valoraciones.index') }}" class="{{ request()->routeIs('valoraciones*') ? 'active' : '' }}" style="position:relative;">
                <i class="bi bi-star"></i><span class="nav-label">Valoraciones</span>
                @php $pendientes = \App\Models\Valoracion::where('aprobada', false)->count(); @endphp
                @if($pendientes > 0)
                <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:#f59e0b;color:#000;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;">{{ $pendientes }}</span>
                @endif
            </a>
        </div>
        @endif
    </nav>
    <div class="nc-sidebar-footer">v1.0 — NATURACOR © 2026</div>
</aside>

<!-- ══ TOPBAR ══ -->
<header class="nc-topbar" id="topbar">
    <button onclick="toggleSidebar()"
        style="border:none; background:transparent; font-size:20px;
               color:rgba(255,255,255,0.45); padding:0; line-height:1;
               cursor:pointer; transition:color 0.18s; flex-shrink:0;"
        onmouseover="this.style.color='#28c76f'"
        onmouseout="this.style.color='rgba(255,255,255,0.45)'">
        <i class="bi bi-list"></i>
    </button>
    <div class="flex-grow-1">
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
    </div>
    <div class="connection-badge online" id="connectionBadge">
        <span class="connection-dot online" id="connectionDot"></span>
        <span id="connectionText">En línea</span>
    </div>
    @if(auth()->user()->sucursal)
    <span style="font-size:11.5px; color:rgba(156,174,164,0.7);
                 background:rgba(255,255,255,0.03); padding:5px 12px;
                 border-radius:20px; border:1px solid rgba(255,255,255,0.08);">
        <i class="bi bi-shop"></i> {{ auth()->user()->sucursal->nombre }}
    </span>
    @endif
    <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2"
            data-bs-toggle="dropdown"
            style="background:rgba(255,255,255,0.04);
                   border:1px solid rgba(255,255,255,0.10) !important;
                   border-radius:30px; padding:5px 13px; color:var(--white);
                   font-family:'Sora',sans-serif; transition:all 0.2s ease;
                   box-shadow:none !important;"
            onmouseover="this.style.borderColor='rgba(40,199,111,0.40)'; this.style.boxShadow='0 0 14px rgba(40,199,111,0.2)'"
            onmouseout="this.style.borderColor='rgba(255,255,255,0.10)'; this.style.boxShadow='none'"
            onfocus="this.style.borderColor='rgba(40,199,111,0.40)'; this.style.boxShadow='0 0 14px rgba(40,199,111,0.2)'"
            onblur="this.style.borderColor='rgba(255,255,255,0.10)'; this.style.boxShadow='none'">
            <div style="width:27px; height:27px; border-radius:50%;
                        background:linear-gradient(135deg,#28c76f,#0e4b2a);
                        display:flex; align-items:center; justify-content:center;
                        color:white; font-size:12px; font-weight:700;
                        box-shadow:0 0 10px rgba(40,199,111,0.35);">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <span style="font-size:12.5px; font-weight:500;
                         max-width:110px; overflow:hidden;
                         text-overflow:ellipsis; white-space:nowrap;">
                {{ auth()->user()->name }}
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text" style="font-size:11.5px; padding:8px 16px;">
                {{ auth()->user()->hasRole('admin') ? '👑 Administrador' : '👤 Empleado' }}
            </span></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item" style="color:#e74c3c !important;">
                        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>

<!-- ══ CONTENT ══ -->
<main class="nc-content" id="mainContent">
    @if(session('success'))
        <div class="alert alert-naturacor alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                style="filter:invert(1) brightness(0.5);"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger-nc alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                style="filter:invert(1) brightness(0.5);"></button>
        </div>
    @endif
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
/* ══ CANVAS BACKGROUND ══════════════════════════════════════════════════ */
(function () {
    const cv  = document.getElementById('bg-canvas');
    const ctx = cv.getContext('2d');
    let W, H, T = 0;

    const PARTS = Array.from({ length: 100 }, () => ({
        x:  Math.random(), y: Math.random(),
        vx: (Math.random() - 0.5) * 0.00022,
        vy: -(Math.random() * 0.00030 + 0.00007),
        r:  Math.random() * 1.6 + 0.35,
        op: Math.random() * 0.40 + 0.08,
        ph: Math.random() * Math.PI * 2,
    }));

    const WAVES = [
        { y:.15, fr:.0028, sp:.28, ph:0.0, al:.040, c:'40,199,111'  },
        { y:.30, fr:.0038, sp:.22, ph:1.7, al:.048, c:'74,222,128'  },
        { y:.46, fr:.0022, sp:.35, ph:3.2, al:.030, c:'14,75,42'    },
        { y:.62, fr:.0048, sp:.40, ph:1.0, al:.055, c:'134,239,172' },
        { y:.77, fr:.0018, sp:.18, ph:4.5, al:.025, c:'22,163,74'   },
    ];

    function resize() { W = cv.width = window.innerWidth; H = cv.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize);

    function frame() {
        ctx.clearRect(0, 0, W, H);

        const bg = ctx.createLinearGradient(0,0,0,H);
        bg.addColorStop(0,   '#071a10');
        bg.addColorStop(0.5, '#061410');
        bg.addColorStop(1,   '#071a10');
        ctx.fillStyle = bg; ctx.fillRect(0, 0, W, H);

        const gc = ctx.createRadialGradient(W/2,H/2,0, W/2,H/2, Math.max(W,H)*0.6);
        gc.addColorStop(0, 'rgba(40,199,111,0.04)');
        gc.addColorStop(1, 'rgba(40,199,111,0)');
        ctx.fillStyle = gc; ctx.fillRect(0, 0, W, H);

        WAVES.forEach(w => {
            const by = H * w.y, t = T * w.sp, amp = H * 0.060;
            ctx.beginPath();
            for (let x = 0; x <= W; x += 3) {
                const y = by + Math.sin(w.fr*x + t + w.ph) * amp;
                x === 0 ? ctx.moveTo(x,y) : ctx.lineTo(x,y);
            }
            ctx.lineTo(W,H); ctx.lineTo(0,H); ctx.closePath();
            const gw = ctx.createLinearGradient(0, by-amp, 0, by+amp*2);
            gw.addColorStop(0,   `rgba(${w.c},${w.al*1.8})`);
            gw.addColorStop(0.5, `rgba(${w.c},${w.al})`);
            gw.addColorStop(1,   `rgba(${w.c},0)`);
            ctx.fillStyle = gw; ctx.fill();

            ctx.beginPath();
            for (let x = 0; x <= W; x += 3) {
                const y = by + Math.sin(w.fr*x + t + w.ph) * amp;
                x === 0 ? ctx.moveTo(x,y) : ctx.lineTo(x,y);
            }
            ctx.strokeStyle = `rgba(${w.c},${w.al*3.2})`;
            ctx.lineWidth = 1.1; ctx.stroke();
        });

        PARTS.forEach(p => {
            const sx = p.x * W;
            const sy = p.y * H + Math.sin(T*0.45 + p.ph) * 13;
            const op = p.op * (0.68 + 0.32*Math.sin(T*0.7 + p.ph));
            const gl = ctx.createRadialGradient(sx,sy,0, sx,sy, p.r*6);
            gl.addColorStop(0, `rgba(40,199,111,${op*0.9})`);
            gl.addColorStop(1, 'rgba(40,199,111,0)');
            ctx.beginPath(); ctx.arc(sx,sy, p.r*6, 0, Math.PI*2);
            ctx.fillStyle = gl; ctx.fill();
            ctx.beginPath(); ctx.arc(sx,sy, p.r, 0, Math.PI*2);
            ctx.fillStyle = `rgba(156,234,172,${op*1.3})`; ctx.fill();
            p.x += p.vx; p.y += p.vy;
            if (p.y < -0.04) p.y = 1.05;
            if (p.x < 0) p.x = 1; if (p.x > 1) p.x = 0;
        });

        T += 0.006;
        requestAnimationFrame(frame);
    }
    frame();
})();

/* ══ SIDEBAR TOGGLE ═════════════════════════════════════════════════════ */
let collapsed = false;
function toggleSidebar() {
    collapsed = !collapsed;
    const sb = document.getElementById('sidebar');
    const tb = document.getElementById('topbar');
    const mc = document.getElementById('mainContent');
    sb.classList.toggle('collapsed', collapsed);
    const w = collapsed ? '60px' : '215px';
    tb.style.left = w;
    mc.style.marginLeft = w;
}

/* ══ CONNECTION STATUS ══════════════════════════════════════════════════ */
function updateConn() {
    const b = document.getElementById('connectionBadge');
    const d = document.getElementById('connectionDot');
    const t = document.getElementById('connectionText');
    const on = navigator.onLine;
    b.className = 'connection-badge ' + (on?'online':'offline');
    d.className = 'connection-dot '   + (on?'online':'offline');
    t.textContent = on ? 'En línea' : 'Sin conexión';
}
window.addEventListener('online', updateConn);
window.addEventListener('offline', updateConn);
updateConn();

/* CSRF */
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/* Confirm delete */
document.addEventListener('submit', function(e) {
    const msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return false; }
});
</script>
@yield('scripts')
</body>
</html>
