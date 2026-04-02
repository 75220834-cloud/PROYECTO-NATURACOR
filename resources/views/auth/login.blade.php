<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NATURACOR – Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        display: flex;
        background: linear-gradient(135deg, #1a2e1a 0%, #1e3a2f 40%, #0f2010 100%);
        margin: 0;
        align-items: center;
        justify-content: center;
    }
    .login-wrapper {
        display: grid;
        grid-template-columns: 1fr 420px;
        min-height: 100vh;
        width: 100%;
    }
    .login-left {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px;
        text-align: center;
    }
    .brand-logo {
        width: 80px; height: 80px; border-radius: 20px;
        background: linear-gradient(135deg, rgba(74,222,128,0.3), rgba(74,222,128,0.1));
        border: 2px solid rgba(74,222,128,0.4);
        font-size: 44px; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 0 40px rgba(74,222,128,0.2);
    }
    .brand-title { font-size: 42px; font-weight: 800; color: white; letter-spacing: 2px; }
    .brand-subtitle { font-size: 14px; color: rgba(255,255,255,0.5); margin-top: 8px; font-weight: 300; line-height: 1.7; }
    .feature-tag { display: inline-flex; align-items: center; gap: 8px; background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.2); border-radius: 30px; padding: 6px 14px; font-size: 12px; color: rgba(255,255,255,0.7); margin: 4px; font-weight: 500; }
    .login-right {
        background: white;
        padding: 48px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: -20px 0 60px rgba(0,0,0,0.3);
    }
    .nc-input {
        border-radius: 12px; border: 1.5px solid #d1fae5; padding: 12px 16px;
        font-size: 14px; transition: all 0.2s; width: 100%;
    }
    .nc-input:focus { border-color: #4ade80; box-shadow: 0 0 0 3px rgba(74,222,128,0.15); outline: none; }
    .btn-login {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white; border: none; border-radius: 12px;
        padding: 14px; font-size: 15px; font-weight: 700;
        width: 100%; cursor: pointer; transition: all 0.2s;
        letter-spacing: 0.5px;
    }
    .btn-login:hover { background: linear-gradient(135deg, #16a34a, #15803d); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(22,163,74,0.4); }
    .input-icon-wrapper { position: relative; }
    .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
    .input-icon-wrapper input { padding-left: 42px; }
    @media(max-width:768px) {
        .login-wrapper { grid-template-columns: 1fr; }
        .login-left { display: none; }
        .login-right { min-height: 100vh; }
    }
    </style>
</head>
<body>
<div class="login-wrapper">
    <!-- Izquierda decorativa -->
    <div class="login-left">
        <div class="brand-logo">🌿</div>
        <div class="brand-title">NATURACOR</div>
        <div class="brand-subtitle">
            Sistema de gestión<br>para productos naturales
        </div>
        <div class="mt-4 d-flex flex-wrap justify-content-center">
            <span class="feature-tag"><i class="bi bi-wifi-off"></i> Offline / Online</span>
            <span class="feature-tag"><i class="bi bi-shop"></i> Multi-sucursal</span>
            <span class="feature-tag"><i class="bi bi-cart3"></i> POS integrado</span>
            <span class="feature-tag"><i class="bi bi-cash-coin"></i> Control de caja</span>
            <span class="feature-tag"><i class="bi bi-robot"></i> Asistente IA</span>
            <span class="feature-tag"><i class="bi bi-journal-medical"></i> Recetario</span>
        </div>
        <div class="mt-6" style="margin-top: 48px; font-size:11px; color:rgba(255,255,255,0.25);">
            v1.0 — Universidad — Pruebas y Calidad de Software
        </div>
    </div>

    <!-- Formulario de login -->
    <div class="login-right">
        <div class="mb-6" style="margin-bottom:32px;">
            <h3 style="font-size:24px; font-weight:700; color:#1a2e1a; margin-bottom:6px;">Bienvenido de vuelta</h3>
            <p style="color:#6b7280; font-size:14px; margin:0;">Ingresa tus credenciales para continuar</p>
        </div>

        @if(session('status'))
            <div class="alert" style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; border-radius:12px; font-size:13px; padding:12px 16px; margin-bottom:20px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <!-- Email -->
            <div class="mb-4">
                <label class="form-label" style="font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Correo electrónico</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="nc-input"
                        placeholder="admin@naturacor.com" required autocomplete="email" autofocus>
                </div>
                @error('email')
                    <div style="color:#dc2626; font-size:12px; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="form-label" style="font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Contraseña</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="nc-input" placeholder="••••••••" required autocomplete="current-password">
                </div>
                @error('password')
                    <div style="color:#dc2626; font-size:12px; margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Remember -->
            <div class="d-flex justify-content-between align-items-center mb-5" style="margin-bottom:24px !important;">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#6b7280; cursor:pointer;">
                    <input type="checkbox" name="remember" style="accent-color: #22c55e;">
                    Recordarme
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:13px; color:#22c55e; text-decoration:none; font-weight:500;">¿Olvidaste tu contraseña?</a>
                @endif
            </div>

            <button type="submit" class="btn-login">
                Ingresar al sistema
            </button>

            <!-- Demo credentials -->
            <div class="mt-4 p-3" style="background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
                <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">Credenciales de demostración</div>
                <div style="font-size:12px; color:#374151;">
                    <strong>Admin:</strong> admin@naturacor.com / Admin123!<br>
                    <strong>Empleado:</strong> empleado@naturacor.com / Empleado123!
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
