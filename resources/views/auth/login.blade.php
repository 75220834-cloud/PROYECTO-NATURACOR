<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NATURACOR — Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #041a04;
            overflow: hidden;
        }

        canvas {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .glass-card {
            background: rgba(10, 30, 10, 0.55);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 24px;
            padding: 48px 44px;
            box-shadow:
                0 0 0 1px rgba(74,222,128,0.05) inset,
                0 40px 80px rgba(0,0,0,0.5),
                0 0 60px rgba(34,197,94,0.08);
            animation: cardIn 1s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .brand-logo {
            width: 72px; height: 72px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(74,222,128,0.3), rgba(34,197,94,0.15));
            border: 1.5px solid rgba(74,222,128,0.4);
            font-size: 36px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 30px rgba(74,222,128,0.25), 0 0 60px rgba(74,222,128,0.1);
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 30px rgba(74,222,128,0.25), 0 0 60px rgba(74,222,128,0.1); }
            50% { box-shadow: 0 0 50px rgba(74,222,128,0.45), 0 0 100px rgba(74,222,128,0.2); }
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 900;
            color: #ffffff;
            text-align: center;
            letter-spacing: 5px;
            text-shadow: 0 0 30px rgba(74,222,128,0.3);
        }

        .brand-sub {
            font-size: 11px;
            font-weight: 400;
            color: rgba(134,239,172,0.6);
            text-align: center;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 32px;
        }

        .welcome-text {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 28px;
        }

        .field-group { margin-bottom: 18px; }

        .field-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(134,239,172,0.8);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
        }

        .field-wrapper { position: relative; }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(74,222,128,0.6);
            font-size: 15px;
            transition: color 0.2s;
            pointer-events: none;
        }

        .nc-input {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(74,222,128,0.25);
            border-radius: 12px;
            padding: 13px 16px 13px 44px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #ffffff;
            transition: all 0.25s;
        }

        .nc-input::placeholder { color: rgba(255,255,255,0.3); }

        .nc-input:focus {
            outline: none;
            border-color: rgba(74,222,128,0.6);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(74,222,128,0.1), 0 0 20px rgba(74,222,128,0.1);
        }

        .error-msg { color: #f87171; font-size: 12px; margin-top: 6px; }

        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            cursor: pointer;
        }

        .remember-label input { accent-color: #4ade80; }

        .forgot-link {
            font-size: 13px;
            color: rgba(134,239,172,0.8);
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover { color: #4ade80; }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.25s;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(34,197,94,0.35), 0 0 40px rgba(34,197,94,0.15);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before { left: 100%; }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(34,197,94,0.5), 0 0 60px rgba(34,197,94,0.2);
        }

        .btn-login:active { transform: translateY(0); }

        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
            border-radius: 12px;
            font-size: 13px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .version {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1px;
        }

        @media(max-width: 480px) {
            .glass-card { padding: 36px 28px; }
            .brand-name { font-size: 28px; }
        }
    </style>
</head>
<body>

<canvas id="canvas"></canvas>

<div class="login-container">
    <div class="glass-card">
        <div class="brand-logo">🌿</div>
        <div class="brand-name">NATURACOR</div>
        <div class="brand-sub">Sistema de Gestión Natural</div>

        <div class="welcome-text">Bienvenido a Naturacor</div>

        @if(session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
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

            <button type="submit" class="btn-login">
                Ingresar al sistema
            </button>
        </form>
    </div>
    <div class="version">v1.0</div>
</div>

<script>
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
let W, H, particles = [], time = 0;

function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
}
resize();
window.addEventListener('resize', resize);

for (let i = 0; i < 120; i++) {
    particles.push({
        x: Math.random() * 2000,
        y: Math.random() * 1000,
        size: Math.random() * 2.5 + 0.5,
        speed: Math.random() * 0.4 + 0.1,
        opacity: Math.random() * 0.6 + 0.1,
        waveOffset: Math.random() * Math.PI * 2,
    });
}

function getWaveY(x, t, amplitude, frequency, phase) {
    return amplitude * Math.sin(frequency * x + t + phase);
}

function draw() {
    ctx.clearRect(0, 0, W, H);

    const bg = ctx.createRadialGradient(W/2, H/2, 0, W/2, H/2, Math.max(W, H));
    bg.addColorStop(0, '#0a2010');
    bg.addColorStop(0.5, '#061508');
    bg.addColorStop(1, '#020a02');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    const waves = [
        { amplitude: H*0.12, frequency: 0.003,  phase: 0,   speed: 0.4,  alpha: 0.06, color: '34,197,94' },
        { amplitude: H*0.10, frequency: 0.004,  phase: 1.5, speed: 0.3,  alpha: 0.08, color: '74,222,128' },
        { amplitude: H*0.14, frequency: 0.0025, phase: 3.0, speed: 0.5,  alpha: 0.05, color: '22,163,74' },
        { amplitude: H*0.08, frequency: 0.005,  phase: 0.8, speed: 0.6,  alpha: 0.10, color: '134,239,172' },
        { amplitude: H*0.16, frequency: 0.002,  phase: 4.5, speed: 0.25, alpha: 0.04, color: '16,185,129' },
    ];

    waves.forEach((w, wi) => {
        const baseY = H * (0.25 + wi * 0.12);
        const t = time * w.speed;

        ctx.beginPath();
        ctx.moveTo(0, H);
        for (let x = 0; x <= W; x += 3) {
            const y = baseY + getWaveY(x, t, w.amplitude, w.frequency, w.phase);
            x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
        ctx.lineTo(W, H);
        ctx.lineTo(0, H);
        ctx.closePath();

        const grad = ctx.createLinearGradient(0, baseY - w.amplitude, 0, baseY + w.amplitude);
        grad.addColorStop(0, `rgba(${w.color}, ${w.alpha * 1.5})`);
        grad.addColorStop(0.5, `rgba(${w.color}, ${w.alpha})`);
        grad.addColorStop(1, `rgba(${w.color}, 0)`);
        ctx.fillStyle = grad;
        ctx.fill();

        ctx.beginPath();
        for (let x = 0; x <= W; x += 3) {
            const y = baseY + getWaveY(x, t, w.amplitude, w.frequency, w.phase);
            x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
        ctx.strokeStyle = `rgba(${w.color}, ${w.alpha * 3})`;
        ctx.lineWidth = 1.5;
        ctx.stroke();
    });

    particles.forEach(p => {
        const waveIdx = Math.min(Math.floor(p.y / (H / waves.length)), waves.length - 1);
        const w = waves[waveIdx];
        const baseY = H * (0.25 + waveIdx * 0.12);
        const waveY = baseY + getWaveY(p.x, time * w.speed, w.amplitude * 0.8, w.frequency, w.phase);
        const screenX = (p.x / 2000) * W;
        const screenY = waveY + Math.sin(time * 0.5 + p.waveOffset) * 15;

        const glow = ctx.createRadialGradient(screenX, screenY, 0, screenX, screenY, p.size * 4);
        glow.addColorStop(0, `rgba(74,222,128,${p.opacity})`);
        glow.addColorStop(1, 'rgba(74,222,128,0)');
        ctx.beginPath();
        ctx.arc(screenX, screenY, p.size * 4, 0, Math.PI * 2);
        ctx.fillStyle = glow;
        ctx.fill();

        ctx.beginPath();
        ctx.arc(screenX, screenY, p.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(134,239,172,${p.opacity * 1.5})`;
        ctx.fill();

        p.x += p.speed * 0.8;
        if (p.x > 2000) p.x = 0;
    });

    time += 0.008;
    requestAnimationFrame(draw);
}

draw();
</script>
</body>
</html>
