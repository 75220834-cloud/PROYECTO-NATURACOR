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
        :root {
            --green-deep: #0a1f0a;
            --green-dark: #122112;
            --green-mid: #1a3a1a;
            --green-accent: #4ade80;
            --green-light: #86efac;
            --white: #ffffff;
            --gray: #6b7280;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--green-deep);
            overflow: hidden;
        }

        /* === ONDAS ANIMADAS === */
        .waves-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            border-radius: 50% 50% 0 0;
            animation: waveMove linear infinite;
        }

        .wave-1 {
            height: 55vh;
            background: radial-gradient(ellipse at center, rgba(34,197,94,0.12) 0%, rgba(34,197,94,0.04) 60%, transparent 100%);
            animation-duration: 8s;
            bottom: -10vh;
        }

        .wave-2 {
            height: 45vh;
            background: radial-gradient(ellipse at center, rgba(74,222,128,0.10) 0%, rgba(74,222,128,0.03) 60%, transparent 100%);
            animation-duration: 12s;
            animation-direction: reverse;
            bottom: -15vh;
        }

        .wave-3 {
            height: 35vh;
            background: radial-gradient(ellipse at center, rgba(134,239,172,0.08) 0%, rgba(134,239,172,0.02) 60%, transparent 100%);
            animation-duration: 16s;
            bottom: -20vh;
        }

        .wave-4 {
            height: 25vh;
            background: radial-gradient(ellipse at center, rgba(22,163,74,0.15) 0%, rgba(22,163,74,0.04) 60%, transparent 100%);
            animation-duration: 20s;
            animation-direction: reverse;
            bottom: -5vh;
        }

        @keyframes waveMove {
            0% { transform: translateX(0) scaleY(1); }
            50% { transform: translateX(-25%) scaleY(1.05); }
            100% { transform: translateX(-50%) scaleY(1); }
        }

        /* Partículas flotantes */
        .particles {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(74,222,128,0.15);
            animation: floatUp linear infinite;
        }

        @keyframes floatUp {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* === LAYOUT === */
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 460px;
            min-height: 100vh;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        /* === LADO IZQUIERDO === */
        .login-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            text-align: center;
            position: relative;
        }

        .brand-logo {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(74,222,128,0.25), rgba(74,222,128,0.08));
            border: 1.5px solid rgba(74,222,128,0.35);
            font-size: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 0 60px rgba(74,222,128,0.15), inset 0 1px 0 rgba(255,255,255,0.1);
            animation: logoPulse 4s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 60px rgba(74,222,128,0.15), inset 0 1px 0 rgba(255,255,255,0.1); }
            50% { box-shadow: 0 0 80px rgba(74,222,128,0.3), inset 0 1px 0 rgba(255,255,255,0.1); }
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 900;
            color: white;
            letter-spacing: 4px;
            line-height: 1;
            animation: fadeInUp 1s ease both;
        }

        .brand-divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--green-accent), transparent);
            margin: 20px auto;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .brand-tagline {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            font-weight: 300;
            letter-spacing: 3px;
            text-transform: uppercase;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .version-tag {
            position: absolute;
            bottom: 32px;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* === LADO DERECHO === */
        .login-right {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: -30px 0 80px rgba(0,0,0,0.4);
            position: relative;
            animation: slideInRight 0.8s ease both;
        }

        @keyframes slideInRight {
            from { transform: translateX(40px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--green-accent), #16a34a, var(--green-accent));
            background-size: 100% 200%;
            animation: gradientSlide 3s ease infinite;
        }

        @keyframes gradientSlide {
            0% { background-position: 0% 0%; }
            50% { background-position: 0% 100%; }
            100% { background-position: 0% 0%; }
        }

        .login-header {
            margin-bottom: 36px;
        }

        .login-header h3 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--green-deep);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 300;
        }

        .form-label-custom {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
            transition: color 0.2s;
        }

        .nc-input {
            width: 100%;
            border-radius: 12px;
            border: 1.5px solid #e5e7eb;
            padding: 13px 16px 13px 44px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
            background: #fafafa;
            color: #111827;
        }

        .nc-input:focus {
            border-color: var(--green-accent);
            box-shadow: 0 0 0 3px rgba(74,222,128,0.12);
            outline: none;
            background: white;
        }

        .nc-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #22c55e;
        }

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
            color: var(--gray);
            cursor: pointer;
        }

        .remember-label input[type="checkbox"] {
            accent-color: #22c55e;
            width: 15px;
            height: 15px;
        }

        .forgot-link {
            font-size: 13px;
            color: #22c55e;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover { color: #16a34a; }

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
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .btn-login:hover::after {
            width: 300px;
            height: 300px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(22,163,74,0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #15803d;
            border-radius: 12px;
            font-size: 13px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .error-msg {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px;
        }

        @media(max-width: 768px) {
            .login-wrapper { grid-template-columns: 1fr; }
            .login-left { display: none; }
            .login-right { min-height: 100vh; padding: 40px 28px; }
        }
    </style>
</head>
<body>

<!-- Ondas animadas -->
<div class="waves-container">
    <div class="wave wave-1"></div>
    <div class="wave wave-2"></div>
    <div class="wave wave-3"></div>
    <div class="wave wave-4"></div>
</div>

<!-- Partículas -->
<div class="particles" id="particles"></div>

<div class="login-wrapper">
    <!-- Lado izquierdo decorativo -->
    <div class="login-left">
        <div class="brand-logo">🌿</div>
        <div class="brand-title">NATURACOR</div>
        <div class="brand-divider"></div>
        <div class="brand-tagline">Sistema de gestión natural</div>
        <div class="version-tag">v1.0</div>
    </div>

    <!-- Formulario -->
    <div class="login-right">
        <div class="login-header">
            <h3>Bienvenido a Naturacor</h3>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        @if(session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div>
                <label class="form-label-custom">Correo electrónico</label>
                <div class="input-wrapper">
                    <input type="email" name="email" id="email"
                        value="{{ old('email') }}"
                        class="nc-input"
                        placeholder="correo@naturacor.com"
                        required autocomplete="email" autofocus>
                    <i class="bi bi-envelope input-icon"></i>
                </div>
                @error('email')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label class="form-label-custom">Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password"
                        class="nc-input"
                        placeholder="••••••••"
                        required autocomplete="current-password">
                    <i class="bi bi-lock input-icon"></i>
                </div>
                @error('password')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Remember + Forgot -->
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Generar partículas flotantes
    const container = document.getElementById('particles');
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.classList.add('particle');
        const size = Math.random() * 6 + 2;
        p.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${Math.random() * 100}%;
            animation-duration: ${Math.random() * 15 + 10}s;
            animation-delay: ${Math.random() * 10}s;
        `;
        container.appendChild(p);
    }
</script>
</body>
</html>
