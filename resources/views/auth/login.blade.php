<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NATURACOR — Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bio-lime:    #BFFF80;
            --bio-sage:    #D2E7B3;
            --bio-teal:    #4AEFBF;
            --bio-green:   #00CC44;
            --deep-black:  #041A0A;
            --glass-bg:    rgba(2, 28, 11, 0.50);
            --glass-border:rgba(191, 255, 128, 0.18);
        }

        html, body {
            width: 100%; height: 100%;
            overflow: hidden;
            font-family: 'Montserrat', sans-serif;
            background: var(--deep-black);
        }

        /* ══ CANVAS FONDO ══════════════════════════════════════════ */
        #bgCanvas {
            position: fixed;
            inset: 0; z-index: 0;
            width: 100%; height: 100%;
        }

        /* ══ TEXTURA VENAS (SVG inline como pseudo) ════════════════ */
        .vein-overlay {
            position: fixed;
            inset: 0; z-index: 1;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='600'%3E%3Cg fill='none' stroke='%2300CC44' stroke-width='0.4' opacity='0.18'%3E%3Cpath d='M400 0 Q420 150 380 300 Q360 450 400 600'/%3E%3Cpath d='M400 300 Q500 280 600 320 Q700 340 800 300'/%3E%3Cpath d='M400 300 Q300 270 200 310 Q100 340 0 300'/%3E%3Cpath d='M400 300 Q430 200 480 150 Q520 100 560 50'/%3E%3Cpath d='M400 300 Q370 200 320 160 Q280 110 240 60'/%3E%3Cpath d='M400 300 Q450 350 480 420 Q510 480 530 560'/%3E%3Cpath d='M400 300 Q350 360 320 430 Q295 490 270 570'/%3E%3Cpath d='M200 0 Q210 100 190 200 Q175 300 200 400'/%3E%3Cpath d='M600 100 Q610 200 590 300 Q575 380 600 460'/%3E%3Cpath d='M100 200 Q200 210 280 190 Q340 175 400 200'/%3E%3Cpath d='M500 400 Q580 390 650 420 Q720 445 780 420'/%3E%3C/g%3E%3C/svg%3E");
            background-size: cover;
            animation: veinBreathe 10s ease-in-out infinite;
        }
        @keyframes veinBreathe {
            0%,100% { opacity: 0.35; }
            50%      { opacity: 0.75; }
        }

        /* ══ LAYOUT CENTRAL ════════════════════════════════════════ */
        .scene {
            position: relative;
            z-index: 10;
            width: 100%; height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ══ PANEL CRISTAL ═════════════════════════════════════════ */
        .glass-panel {
            position: relative;
            width: 100%;
            max-width: 420px;
            margin: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(22px) saturate(1.4);
            -webkit-backdrop-filter: blur(22px) saturate(1.4);
            border-radius: 30px;
            border: 1px solid var(--glass-border);
            padding: 56px 44px 44px;
            box-shadow:
                0 0 0 1px rgba(191,255,128,0.06) inset,
                0 2px 0 rgba(191,255,128,0.12) inset,
                0 50px 100px rgba(0,0,0,0.65),
                0 0 80px rgba(0,204,68,0.08),
                0 0 160px rgba(0,204,68,0.04);
            animation: panelIn 1.2s cubic-bezier(0.16,1,0.3,1) both;
        }

        /* Reflejo superior cristal */
        .glass-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 15%; right: 15%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(191,255,128,0.45), transparent);
            border-radius: 50%;
        }

        /* Borde agua girando al cargar */
        .glass-panel.loading::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 32px;
            background: conic-gradient(from var(--angle, 0deg), transparent 70%, #BFFF80, transparent);
            animation: waterSpin 1.5s linear infinite;
            z-index: -1;
        }
        @property --angle {
            syntax: '<angle>';
            inherits: false;
            initial-value: 0deg;
        }
        @keyframes waterSpin {
            to { --angle: 360deg; }
        }

        @keyframes panelIn {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ══ LOGO FLOTANTE ═════════════════════════════════════════ */
        .logo-holo {
            position: absolute;
            top: -52px;
            left: 50%;
            transform: translateX(-50%);
            width: 104px; height: 104px;
            border-radius: 50%;
            background: radial-gradient(circle at 38% 35%, #d4a843, #8b6914 55%, #4a3508);
            border: 2px solid rgba(212,168,67,0.55);
            display: flex; align-items: center; justify-content: center;
            font-size: 46px;
            box-shadow:
                0 0 0 6px rgba(191,255,128,0.08),
                0 0 30px rgba(212,168,67,0.55),
                0 0 70px rgba(212,168,67,0.25),
                0 0 120px rgba(0,204,68,0.15),
                inset 0 -8px 20px rgba(0,0,0,0.40),
                inset 0 4px 12px rgba(255,220,100,0.30);
            animation: holoFloat 4s ease-in-out infinite, holoSpin 20s linear infinite;
            transform-style: preserve-3d;
            cursor: pointer;
        }
        @keyframes holoFloat {
            0%,100% { top: -52px; filter: drop-shadow(0 8px 24px rgba(212,168,67,0.40)); }
            50%      { top: -62px; filter: drop-shadow(0 16px 40px rgba(212,168,67,0.65)); }
        }
        @keyframes holoSpin {
            0%   { box-shadow: 0 0 0 6px rgba(191,255,128,0.08), 0 0 30px rgba(212,168,67,0.55), 0 0 70px rgba(212,168,67,0.25), inset 0 -8px 20px rgba(0,0,0,0.40); }
            25%  { box-shadow: 4px 0 0 6px rgba(191,255,128,0.08), 4px 0 30px rgba(212,168,67,0.45), 0 0 70px rgba(212,168,67,0.20), inset 4px -8px 20px rgba(0,0,0,0.40); }
            50%  { box-shadow: 0 0 0 6px rgba(191,255,128,0.08), 0 -4px 30px rgba(212,168,67,0.55), 0 0 70px rgba(212,168,67,0.25), inset 0 4px 20px rgba(0,0,0,0.40); }
            75%  { box-shadow: -4px 0 0 6px rgba(191,255,128,0.08), -4px 0 30px rgba(212,168,67,0.45), 0 0 70px rgba(212,168,67,0.20), inset -4px -8px 20px rgba(0,0,0,0.40); }
            100% { box-shadow: 0 0 0 6px rgba(191,255,128,0.08), 0 0 30px rgba(212,168,67,0.55), 0 0 70px rgba(212,168,67,0.25), inset 0 -8px 20px rgba(0,0,0,0.40); }
        }

        /* ══ TIPOGRAFÍA ════════════════════════════════════════════ */
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 900;
            color: #ffffff;
            text-align: center;
            letter-spacing: 0.15em;
            text-shadow: 0 0 40px rgba(191,255,128,0.35), 0 2px 4px rgba(0,0,0,0.5);
            margin-top: 8px;
        }
        .brand-sub {
            font-size: 9.5px;
            font-weight: 500;
            color: rgba(210,231,179,0.65);
            text-align: center;
            letter-spacing: 0.30em;
            text-transform: uppercase;
            margin-top: 5px;
            margin-bottom: 28px;
        }
        .welcome-text {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 700;
            color: rgba(255,255,255,0.92);
            text-align: center;
            margin-bottom: 26px;
        }

        /* ══ CAMPOS ════════════════════════════════════════════════ */
        .field-group { margin-bottom: 16px; }
        .field-label {
            font-size: 10px;
            font-weight: 600;
            color: rgba(191,255,128,0.75);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            display: block;
            margin-bottom: 7px;
        }
        .field-wrapper { position: relative; }
        .field-icon {
            position: absolute;
            left: 15px; top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: rgba(74,239,191,0.55);
            pointer-events: none;
            transition: color 0.3s;
            z-index: 2;
        }

        /* Input con efecto savia luminosa en focus */
        .nc-input {
            width: 100%;
            background: rgba(0,0,0,0.30);
            border: 1px solid rgba(191,255,128,0.18);
            border-radius: 14px;
            padding: 13px 16px 13px 44px;
            font-size: 13.5px;
            font-family: 'Montserrat', sans-serif;
            color: #ffffff;
            transition: all 0.3s;
            position: relative;
        }
        .nc-input::placeholder { color: rgba(210,231,179,0.35); }
        .nc-input:focus {
            outline: none;
            background: rgba(0,0,0,0.40);
            border-color: var(--bio-lime);
            box-shadow:
                0 0 0 3px rgba(191,255,128,0.10),
                0 0 20px rgba(191,255,128,0.18),
                inset 0 0 12px rgba(191,255,128,0.04);
            color: #ffffff;
        }
        .field-wrapper:focus-within .field-icon {
            color: var(--bio-lime);
        }

        /* Línea savia animada al focus */
        .field-wrapper::after {
            content: '';
            position: absolute;
            bottom: 0; left: 14px; right: 14px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--bio-lime), var(--bio-teal), var(--bio-lime), transparent);
            border-radius: 2px;
            transform: scaleX(0);
            transition: transform 0.4s cubic-bezier(0.16,1,0.3,1);
            transform-origin: left;
        }
        .field-wrapper:focus-within::after {
            transform: scaleX(1);
        }

        .error-msg {
            color: #f87171;
            font-size: 11px;
            margin-top: 5px;
            font-weight: 500;
        }

        /* ══ FILA RECORDAR ═════════════════════════════════════════ */
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
            margin-top: 4px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            cursor: pointer;
        }
        .remember-label input { accent-color: var(--bio-lime); }
        .forgot-link {
            font-size: 12px;
            color: rgba(191,255,128,0.70);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--bio-lime); }

        /* ══ BOTÓN PREMIUM ═════════════════════════════════════════ */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #006622 0%, #009933 40%, #00CC44 100%);
            color: rgba(255,255,255,0.95);
            border: 1.5px solid rgba(255,255,255,0.55);
            border-radius: 14px;
            padding: 15px 20px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.06em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.30s ease;
            box-shadow:
                0 4px 20px rgba(0,204,68,0.35),
                0 0 40px rgba(0,204,68,0.12),
                inset 0 1px 0 rgba(255,255,255,0.25),
                inset 0 -1px 0 rgba(0,0,0,0.20);
        }
        /* Reflejo cromado animado */
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -120%;
            width: 60%; height: 100%;
            background: linear-gradient(105deg, transparent, rgba(255,255,255,0.22), transparent);
            transition: left 0.55s ease;
            transform: skewX(-15deg);
        }
        .btn-login:hover::before { left: 160%; }
        .btn-login:hover {
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.80);
            box-shadow:
                0 8px 32px rgba(0,204,68,0.55),
                0 0 60px rgba(0,204,68,0.22),
                inset 0 1px 0 rgba(255,255,255,0.35);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login .fingerprint {
            display: inline-block;
            margin-left: 8px;
            font-size: 16px;
            opacity: 0.75;
            vertical-align: middle;
        }

        /* ══ ALERT STATUS ══════════════════════════════════════════ */
        .alert-bio {
            background: rgba(0,204,68,0.10);
            border: 1px solid rgba(191,255,128,0.25);
            color: #86efac;
            border-radius: 12px;
            font-size: 12.5px;
            padding: 11px 15px;
            margin-bottom: 18px;
        }

        /* ══ VERSIÓN ═══════════════════════════════════════════════ */
        .version {
            text-align: center;
            margin-top: 18px;
            font-size: 10px;
            color: rgba(255,255,255,0.18);
            letter-spacing: 0.12em;
        }

        /* ══ PLANTAS DECORATIVAS SVG ═══════════════════════════════ */
        .plant-left, .plant-right {
            position: fixed;
            bottom: 0;
            z-index: 2;
            pointer-events: none;
            opacity: 0.55;
            animation: plantSway 8s ease-in-out infinite;
        }
        .plant-left  { left: 2%;  transform-origin: bottom center; }
        .plant-right { right: 2%; transform-origin: bottom center; animation-direction: alternate-reverse; }
        @keyframes plantSway {
            0%,100% { transform: rotate(-2deg); }
            50%      { transform: rotate(2deg);  }
        }

        @media(max-width: 500px) {
            .glass-panel { padding: 52px 26px 36px; margin: 16px; }
            .brand-name  { font-size: 26px; }
            .plant-left, .plant-right { display: none; }
        }
    </style>
</head>
<body>

<!-- Fondo canvas -->
<canvas id="bgCanvas"></canvas>

<!-- Textura venas -->
<div class="vein-overlay"></div>

<!-- Planta izquierda (SVG lavanda/hierba) -->
<svg class="plant-left" width="180" height="320" viewBox="0 0 180 320">
    <g fill="none" stroke="#4AEFBF" stroke-width="1.2">
        <path d="M90 320 Q88 260 85 200 Q82 140 90 80"/>
        <path d="M88 240 Q60 220 40 190 Q25 165 30 140"/>
        <path d="M87 200 Q110 180 125 155 Q138 132 130 108"/>
        <path d="M86 160 Q58 148 42 122 Q30 100 38 78"/>
        <path d="M88 120 Q108 105 118 82 Q126 62 118 42"/>
        <path d="M40 140 Q28 120 22 95 Q18 74 26 55"/>
        <path d="M130 108 Q138 88 132 68 Q126 50 134 32"/>
        <!-- puntos de flor -->
        <circle cx="26" cy="55"  r="3" fill="#4AEFBF" opacity="0.7"/>
        <circle cx="118" cy="42" r="3" fill="#BFFF80" opacity="0.7"/>
        <circle cx="90"  cy="80" r="4" fill="#4AEFBF" opacity="0.8"/>
        <circle cx="134" cy="32" r="2.5" fill="#BFFF80" opacity="0.6"/>
    </g>
</svg>

<!-- Planta derecha (SVG hongos/helechos) -->
<svg class="plant-right" width="180" height="320" viewBox="0 0 180 320">
    <g fill="none" stroke="#4AEFBF" stroke-width="1.2">
        <path d="M90 320 Q92 255 95 190 Q98 135 90 70"/>
        <path d="M92 230 Q122 210 142 182 Q155 160 148 135"/>
        <path d="M93 188 Q68 170 54 144 Q44 122 52 98"/>
        <path d="M94 148 Q124 135 138 110 Q148 90 140 68"/>
        <path d="M93 108 Q70 96 58 72 Q50 52 60 34"/>
        <!-- hongo izq -->
        <path d="M52 98 Q38 78 44 60 Q50 44 42 30"/>
        <ellipse cx="42" cy="30" rx="14" ry="8" fill="rgba(74,239,191,0.12)" stroke="#4AEFBF"/>
        <!-- hongo der -->
        <path d="M140 68 Q152 50 146 34 Q140 20 150 8"/>
        <ellipse cx="150" cy="8" rx="14" ry="8" fill="rgba(191,255,128,0.12)" stroke="#BFFF80"/>
        <circle cx="60"  cy="34" r="3" fill="#4AEFBF" opacity="0.7"/>
        <circle cx="90"  cy="70" r="4" fill="#BFFF80" opacity="0.8"/>
    </g>
</svg>

<!-- Panel login -->
<div class="scene">
    <div class="glass-panel" id="glassPanel">

        <!-- Logo holográfico flotante -->
        <div class="logo-holo" id="logoHolo">🌿</div>

        <div class="brand-name">NATURACOR</div>
        <div class="brand-sub">Sistema de Gestión Natural</div>
        <div class="welcome-text">Bienvenido a Naturacor</div>

        @if(session('status'))
            <div class="alert-bio">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="field-group">
                <label class="field-label">Correo electrónico</label>
                <div class="field-wrapper">
                    <input type="email" name="email" id="email"
                        value="{{ old('email') }}"
                        class="nc-input"
                        placeholder="correo@naturacor.com"
                        required autocomplete="email" autofocus>
                    <i class="bi bi-envelope field-icon"></i>
                </div>
                @error('email')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="field-group">
                <label class="field-label">Contraseña</label>
                <div class="field-wrapper">
                    <input type="password" name="password" id="password"
                        class="nc-input"
                        placeholder="••••••••"
                        required autocomplete="current-password">
                    <i class="bi bi-lock field-icon"></i>
                </div>
                @error('password')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember">
                    Recordarme
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">¿Olvidaste tu contraseña?</a>
                @endif
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                Ingresar al sistema
                <span class="fingerprint">🔐</span>
            </button>
        </form>
    </div>
    <div class="version">v1.0 — NATURACOR © 2026</div>
</div>

<script>
/* ══ CANVAS BIO-INMERSIVO ══════════════════════════════════════════ */
(function() {
    const cv  = document.getElementById('bgCanvas');
    const ctx = cv.getContext('2d');
    let W, H, T = 0;

    /* Partículas bioluminiscentes */
    const PARTS = Array.from({ length: 160 }, () => ({
        x:   Math.random(),
        y:   Math.random(),
        vx:  (Math.random() - 0.5) * 0.00015,
        vy: -(Math.random() * 0.00020 + 0.00005),
        r:   Math.random() * 1.8 + 0.3,
        op:  Math.random() * 0.50 + 0.08,
        ph:  Math.random() * Math.PI * 2,
        col: Math.random() > 0.6 ? '191,255,128' : Math.random() > 0.5 ? '74,239,191' : '0,204,68',
    }));

    /* Ondas bio */
    const WAVES = [
        { y:.18, fr:.0024, sp:.20, ph:0.0,  al:.055, c:'0,204,68'     },
        { y:.32, fr:.0032, sp:.17, ph:1.8,  al:.065, c:'74,239,191'   },
        { y:.48, fr:.0020, sp:.28, ph:3.5,  al:.040, c:'34,197,94'    },
        { y:.63, fr:.0042, sp:.34, ph:1.2,  al:.070, c:'191,255,128'  },
        { y:.78, fr:.0016, sp:.15, ph:5.0,  al:.030, c:'22,163,74'    },
        { y:.90, fr:.0050, sp:.42, ph:2.5,  al:.080, c:'74,239,191'   },
    ];

    function resize() { W = cv.width = window.innerWidth; H = cv.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize);

    function frame() {
        ctx.clearRect(0, 0, W, H);

        /* Fondo profundo radial */
        const bg = ctx.createRadialGradient(W*.45, H*.45, 0, W*.5, H*.5, Math.max(W,H)*.85);
        bg.addColorStop(0,   '#0A2914');
        bg.addColorStop(0.4, '#061A0C');
        bg.addColorStop(0.8, '#041208');
        bg.addColorStop(1,   '#020A04');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, W, H);

        /* Resplandor central teal */
        const gc = ctx.createRadialGradient(W*.5, H*.55, 0, W*.5, H*.55, Math.max(W,H)*.5);
        gc.addColorStop(0, 'rgba(74,239,191,0.05)');
        gc.addColorStop(1, 'rgba(74,239,191,0)');
        ctx.fillStyle = gc; ctx.fillRect(0, 0, W, H);

        /* Ondas */
        WAVES.forEach(w => {
            const by  = H * w.y;
            const t   = T  * w.sp;
            const amp = H  * 0.065;

            ctx.beginPath();
            for (let x = 0; x <= W; x += 3) {
                const y = by + Math.sin(w.fr * x + t + w.ph) * amp;
                x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            }
            ctx.lineTo(W, H); ctx.lineTo(0, H); ctx.closePath();

            const gw = ctx.createLinearGradient(0, by - amp, 0, by + amp * 2.5);
            gw.addColorStop(0,   `rgba(${w.c},${w.al * 1.6})`);
            gw.addColorStop(0.5, `rgba(${w.c},${w.al})`);
            gw.addColorStop(1,   `rgba(${w.c},0)`);
            ctx.fillStyle = gw; ctx.fill();

            /* línea de ola */
            ctx.beginPath();
            for (let x = 0; x <= W; x += 3) {
                const y = by + Math.sin(w.fr * x + t + w.ph) * amp;
                x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            }
            ctx.strokeStyle = `rgba(${w.c},${w.al * 3.5})`;
            ctx.lineWidth = 1.2; ctx.stroke();
        });

        /* Partículas bio con glow */
        PARTS.forEach(p => {
            const sx = p.x * W;
            const sy = p.y * H + Math.sin(T * 0.40 + p.ph) * 18;
            const op = p.op * (0.65 + 0.35 * Math.sin(T * 0.65 + p.ph));

            const gl = ctx.createRadialGradient(sx, sy, 0, sx, sy, p.r * 7);
            gl.addColorStop(0, `rgba(${p.col},${op * 0.85})`);
            gl.addColorStop(1, `rgba(${p.col},0)`);
            ctx.beginPath(); ctx.arc(sx, sy, p.r * 7, 0, Math.PI * 2);
            ctx.fillStyle = gl; ctx.fill();

            ctx.beginPath(); ctx.arc(sx, sy, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.col},${op * 1.4})`; ctx.fill();

            p.x += p.vx; p.y += p.vy;
            if (p.y < -0.04) p.y = 1.05;
            if (p.x < 0) p.x = 1;
            if (p.x > 1) p.x = 0;
        });

        T += 0.006;
        requestAnimationFrame(frame);
    }
    frame();
})();

/* ══ LOGO PARALLAX CON MOUSE ═══════════════════════════════════════ */
const logo = document.getElementById('logoHolo');
document.addEventListener('mousemove', function(e) {
    const cx = window.innerWidth  / 2;
    const cy = window.innerHeight / 2;
    const dx = (e.clientX - cx) / cx;
    const dy = (e.clientY - cy) / cy;
    logo.style.transform = `translateX(calc(-50% + ${dx * 10}px)) translateY(${dy * 6}px) rotateX(${-dy * 8}deg) rotateY(${dx * 8}deg)`;
});

/* ══ LOADING — BORDE AGUA ══════════════════════════════════════════ */
document.getElementById('loginForm').addEventListener('submit', function() {
    const panel = document.getElementById('glassPanel');
    const btn   = document.getElementById('btnLogin');
    panel.classList.add('loading');
    btn.innerHTML = '<span class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;vertical-align:middle;margin-right:8px;"></span>Validando...';
    btn.disabled = true;
});
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

</body>
</html>
