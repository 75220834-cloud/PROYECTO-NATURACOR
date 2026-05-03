<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Catálogo') — NATURACOR</title>
    <meta name="description" content="NATURACOR — Tienda de productos naturales y cordiales en Jauja, Junín. Consulta nuestro catálogo y haz tu pedido por WhatsApp.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --deep:        #0A2914;
            --deep-glow:   #00CC44;
            --bio-neon:    #BFFF00;
            --bio-cyan:    #00FFFF;
            --glass-body:  rgba(0, 102, 34, 0.55);
            --glass-edge:  rgba(191, 255, 0, 0.40);
            --btn-from:    #00CC44;
            --btn-to:      #BFFF00;
            --text-prim:   #FFFFFF;
            --text-sec:    #D2E7B3;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--deep);
            color: var(--text-prim);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ══════════════════════════════════════════════
           BG LAYERS — Breathing Bio-Vibrante
        ══════════════════════════════════════════════ */
        #bg-canvas {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            z-index: 0; pointer-events: none;
        }

        /* Deep bio-breathing glow */
        .bg-breath {
            position: fixed; inset: 0; z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse 70% 50% at 10% 80%, rgba(0,204,68,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 80% 50% at 90% 20%, rgba(0,204,68,0.20) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(191,255,0,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 30% 30%, rgba(0,255,255,0.04) 0%, transparent 60%);
            animation: breathPulse 8s ease-in-out infinite;
        }
        @keyframes breathPulse {
            0%,100% { opacity: 0.5; }
            50%     { opacity: 1; }
        }

        /* Vein texture overlay */
        .bg-veins {
            position: fixed; inset: 0; z-index: 0;
            pointer-events: none;
            background:
                repeating-linear-gradient(120deg, transparent, transparent 80px, rgba(191,255,0,0.015) 80px, rgba(191,255,0,0.015) 81px),
                repeating-linear-gradient(60deg, transparent, transparent 60px, rgba(0,255,255,0.01) 60px, rgba(0,255,255,0.01) 61px),
                repeating-linear-gradient(170deg, transparent, transparent 100px, rgba(0,204,68,0.02) 100px, rgba(0,204,68,0.02) 101px);
            animation: veinDrift 30s linear infinite;
        }
        @keyframes veinDrift {
            0%   { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-40px) translateY(-20px); }
        }

        /* ══════════════════════════════════════════════
           NAVBAR
        ══════════════════════════════════════════════ */
        .nc-navbar {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000; height: 66px;
            background: rgba(10, 41, 20, 0.85);
            backdrop-filter: blur(24px) saturate(1.8);
            -webkit-backdrop-filter: blur(24px) saturate(1.8);
            border-bottom: 1px solid rgba(191,255,0,0.18);
            display: flex; align-items: center;
            padding: 0 28px;
            box-shadow: 0 2px 30px rgba(0,204,68,0.15),
                        0 1px 0 rgba(191,255,0,0.08) inset;
        }
        .nc-navbar .logo-wrap {
            display: flex; align-items: center; gap: 12px;
        }
        .nc-navbar .logo {
            width: 42px; height: 42px; border-radius: 11px;
            background: rgba(191,255,0,0.10);
            border: 1px solid rgba(191,255,0,0.40);
            display: flex; align-items: center; justify-content: center;
            font-size: 21px; flex-shrink: 0;
            box-shadow: 0 0 24px rgba(191,255,0,0.30),
                        0 0 60px rgba(191,255,0,0.10),
                        inset 0 0 12px rgba(191,255,0,0.15);
            animation: logoHolo 4s ease-in-out infinite, logoSpin 20s linear infinite;
            transform-style: preserve-3d;
        }
        @keyframes logoHolo {
            0%,100% { box-shadow: 0 0 24px rgba(191,255,0,0.30), 0 0 60px rgba(191,255,0,0.10), inset 0 0 12px rgba(191,255,0,0.15); }
            50%     { box-shadow: 0 0 40px rgba(191,255,0,0.55), 0 0 90px rgba(191,255,0,0.20), inset 0 0 20px rgba(191,255,0,0.25); }
        }
        @keyframes logoSpin {
            0%   { transform: perspective(600px) rotateY(0deg); }
            100% { transform: perspective(600px) rotateY(360deg); }
        }
        .nc-navbar .brand {
            font-family: 'Montserrat', sans-serif;
            font-size: 17px; font-weight: 800;
            letter-spacing: 3px;
            color: var(--text-prim);
            text-shadow: 0 0 8px rgba(191,255,0,0.3);
        }
        .nc-navbar .brand span {
            color: var(--bio-neon);
            text-shadow: 0 0 14px rgba(191,255,0,0.6);
        }

        .btn-login {
            background: rgba(191,255,0,0.08);
            border: 1px solid rgba(191,255,0,0.30);
            color: var(--bio-neon); border-radius: 10px;
            padding: 9px 20px; font-size: 13px; font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease; text-decoration: none;
            letter-spacing: 0.5px;
            text-shadow: 0 0 6px rgba(191,255,0,0.3);
        }
        .btn-login:hover {
            background: rgba(191,255,0,0.18);
            border-color: rgba(191,255,0,0.65);
            color: var(--bio-neon);
            box-shadow: 0 0 30px rgba(191,255,0,0.25), 0 0 60px rgba(191,255,0,0.10);
        }
        .btn-login i { margin-right: 6px; }

        /* ══════════════════════════════════════════════
           CONTENT
        ══════════════════════════════════════════════ */
        .nc-page { margin-top: 66px; position: relative; z-index: 1; }

        /* ══════════════════════════════════════════════
           HERO
        ══════════════════════════════════════════════ */
        .hero {
            text-align: center;
            padding: 80px 20px 60px;
            position: relative;
        }
        .hero::after {
            content: '';
            position: absolute; bottom: 0; left: 50%;
            transform: translateX(-50%);
            width: 300px; height: 2px;
            background: linear-gradient(90deg, transparent, var(--bio-neon), var(--bio-cyan), var(--bio-neon), transparent);
            box-shadow: 0 0 20px rgba(191,255,0,0.4);
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 6vw, 56px);
            font-weight: 800; letter-spacing: 4px;
            margin-bottom: 18px;
            color: var(--text-prim);
            text-shadow: 0 0 30px rgba(191,255,0,0.35),
                         0 0 60px rgba(191,255,0,0.15);
            animation: heroGlow 4s ease-in-out infinite;
        }
        @keyframes heroGlow {
            0%,100% { text-shadow: 0 0 30px rgba(191,255,0,0.35), 0 0 60px rgba(191,255,0,0.15); }
            50%     { text-shadow: 0 0 50px rgba(191,255,0,0.55), 0 0 100px rgba(191,255,0,0.25), 0 0 140px rgba(0,255,255,0.08); }
        }
        .hero p {
            font-size: 16px; color: var(--text-sec);
            max-width: 520px; margin: 0 auto 28px;
            line-height: 1.8; font-weight: 400;
            text-shadow: 0 0 4px rgba(191,255,0,0.08);
        }
        .hero .badge-location {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(191,255,0,0.10);
            border: 1px solid rgba(191,255,0,0.35);
            color: var(--bio-neon); border-radius: 22px;
            padding: 7px 18px; font-size: 12px; font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 0 16px rgba(191,255,0,0.15);
            text-shadow: 0 0 6px rgba(191,255,0,0.35);
        }

        /* ══════════════════════════════════════════════
           SECTION TITLES
        ══════════════════════════════════════════════ */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-prim);
            letter-spacing: 1.5px;
            text-shadow: 0 0 12px rgba(191,255,0,0.25);
        }
        .section-subtitle {
            font-size: 13px; color: var(--text-sec);
            margin-bottom: 36px; font-weight: 400;
        }

        /* ══════════════════════════════════════════════
           PRODUCT CARDS — Bio-Phosphorescent Panels
        ══════════════════════════════════════════════ */
        .product-card {
            background: var(--glass-body);
            backdrop-filter: blur(22px) saturate(1.5);
            -webkit-backdrop-filter: blur(22px) saturate(1.5);
            border-radius: 18px;
            border: 1.5px solid var(--glass-edge);
            box-shadow: 0 0 30px rgba(191,255,0,0.08),
                        0 10px 40px rgba(0,0,0,0.40),
                        inset 0 1px 0 rgba(191,255,0,0.12),
                        inset 0 0 40px rgba(0,204,68,0.06);
            overflow: hidden;
            transition: transform 0.4s cubic-bezier(0.16,1,0.3,1),
                        box-shadow 0.4s ease,
                        border-color 0.4s ease;
            display: flex; flex-direction: column;
            height: 100%;
            position: relative;
        }
        /* Energy border loading animation */
        .product-card::before {
            content: '';
            position: absolute; inset: -2px; border-radius: 20px;
            background: conic-gradient(
                from var(--card-angle, 0deg),
                transparent 0%,
                rgba(191,255,0,0.5) 10%,
                rgba(0,255,255,0.3) 20%,
                transparent 30%
            );
            z-index: -1;
            animation: borderEnergy 5s linear infinite;
            opacity: 0.4;
        }
        @property --card-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }
        @keyframes borderEnergy {
            0%   { --card-angle: 0deg; }
            100% { --card-angle: 360deg; }
        }
        .product-card::after {
            content: '';
            position: absolute; inset: 0; border-radius: 18px;
            background: linear-gradient(135deg, rgba(191,255,0,0.06) 0%, transparent 40%, rgba(0,255,255,0.03) 100%);
            pointer-events: none; z-index: 1;
        }
        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 0 50px rgba(191,255,0,0.20),
                        0 0 100px rgba(191,255,0,0.08),
                        0 24px 60px rgba(0,0,0,0.50),
                        inset 0 1px 0 rgba(191,255,0,0.20),
                        inset 0 0 60px rgba(0,204,68,0.10);
            border-color: rgba(191,255,0,0.65);
        }
        .product-card:hover::before { opacity: 0.8; }

        .product-img {
            height: 200px;
            background: linear-gradient(180deg, rgba(0,102,34,0.20) 0%, rgba(10,41,20,0.40) 100%);
            display: flex; align-items: center; justify-content: center;
            position: relative;
            border-bottom: 1px solid rgba(191,255,0,0.15);
            overflow: hidden;
        }
        .product-img img {
            width: 100%; height: 100%;
            object-fit: contain;
            padding: 12px;
            transition: transform 0.5s cubic-bezier(0.16,1,0.3,1);
            filter: drop-shadow(0 4px 20px rgba(191,255,0,0.15));
        }
        .product-card:hover .product-img img {
            transform: scale(1.08);
            filter: drop-shadow(0 4px 30px rgba(191,255,0,0.30));
        }
        .product-img .emoji-placeholder {
            font-size: 64px;
            filter: drop-shadow(0 0 24px rgba(191,255,0,0.35));
            animation: emojiFloat 4s ease-in-out infinite;
        }
        @keyframes emojiFloat {
            0%,100% { transform: translateY(0) scale(1); }
            50%     { transform: translateY(-10px) scale(1.05); }
        }

        .product-body {
            padding: 20px; flex: 1;
            display: flex; flex-direction: column;
            position: relative; z-index: 2;
        }
        .product-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px; font-weight: 700;
            color: var(--text-prim); margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-shadow: 0 0 5px rgba(191,255,0,0.15);
        }
        .product-desc {
            font-size: 12.5px; color: var(--text-sec);
            line-height: 1.6; flex: 1; margin-bottom: 16px;
            display: -webkit-box; -webkit-line-clamp: 3;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .product-price {
            font-size: 24px; font-weight: 800;
            color: var(--bio-neon); margin-bottom: 14px;
            text-shadow: 0 0 20px rgba(191,255,0,0.50),
                         0 0 40px rgba(191,255,0,0.20);
        }
        .product-price small {
            font-size: 11px; color: var(--text-sec);
            font-weight: 500; text-shadow: none;
        }

        /* ══════════════════════════════════════════════
           PREMIUM BUTTON — Consultar (Energy Gradient)
        ══════════════════════════════════════════════ */
        .btn-whatsapp {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--btn-from), var(--btn-to));
            border: 1.5px solid rgba(191,255,0,0.45);
            color: #0A2914; border-radius: 12px;
            font-size: 13.5px; font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
            position: relative; overflow: hidden;
            letter-spacing: 0.5px;
            box-shadow: 0 0 20px rgba(191,255,0,0.15),
                        inset 0 1px 0 rgba(255,255,255,0.20);
        }
        .btn-whatsapp::before {
            content: '';
            position: absolute; inset: -2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.30), transparent);
            transform: translateX(-100%);
            transition: transform 0.7s ease;
        }
        .btn-whatsapp:hover::before { transform: translateX(100%); }
        .btn-whatsapp:hover {
            color: #0A2914;
            box-shadow: 0 0 40px rgba(191,255,0,0.40),
                        0 0 80px rgba(191,255,0,0.15),
                        0 8px 24px rgba(0,0,0,0.30);
            transform: translateY(-2px);
            border-color: rgba(191,255,0,0.70);
        }
        .btn-whatsapp .bi-whatsapp {
            animation: none; transition: transform 0.3s;
        }
        .btn-whatsapp:hover .bi-whatsapp {
            animation: waPulse 0.6s ease-in-out infinite;
        }
        @keyframes waPulse {
            0%,100% { transform: scale(1); }
            50%     { transform: scale(1.20); }
        }

        /* ══════════════════════════════════════════════
           TYPE BADGES
        ══════════════════════════════════════════════ */
        .badge-tipo {
            position: absolute; top: 12px; right: 12px;
            padding: 5px 13px; border-radius: 22px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            z-index: 3;
            backdrop-filter: blur(12px);
        }
        .badge-tipo.natural {
            background: rgba(191,255,0,0.18);
            color: var(--bio-neon);
            border: 1px solid rgba(191,255,0,0.45);
            box-shadow: 0 0 16px rgba(191,255,0,0.25);
            text-shadow: 0 0 6px rgba(191,255,0,0.4);
        }
        .badge-tipo.cordial {
            background: rgba(0,255,255,0.15);
            color: var(--bio-cyan);
            border: 1px solid rgba(0,255,255,0.35);
            box-shadow: 0 0 16px rgba(0,255,255,0.20);
        }

        /* ══════════════════════════════════════════════
           CORDIAL CARDS
        ══════════════════════════════════════════════ */
        .cordial-card {
            background: var(--glass-body);
            backdrop-filter: blur(18px);
            border-radius: 14px;
            border: 1px solid rgba(191,255,0,0.28);
            padding: 20px 22px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px;
            transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
            box-shadow: 0 0 16px rgba(191,255,0,0.05),
                        0 4px 20px rgba(0,0,0,0.3);
        }
        .cordial-card:hover {
            background: rgba(0,102,34,0.65);
            border-color: rgba(191,255,0,0.50);
            transform: translateX(6px);
            box-shadow: 0 0 30px rgba(191,255,0,0.12),
                        0 8px 30px rgba(0,0,0,0.40);
        }
        .cordial-info { flex: 1; }
        .cordial-name {
            font-size: 14px; font-weight: 600; color: var(--text-prim);
            text-shadow: 0 0 4px rgba(191,255,0,0.10);
        }
        .cordial-price {
            font-size: 20px; font-weight: 800; color: var(--bio-neon);
            white-space: nowrap;
            text-shadow: 0 0 16px rgba(191,255,0,0.40);
        }
        .cordial-btn {
            padding: 8px 16px; font-size: 12px;
            background: linear-gradient(135deg, var(--btn-from), var(--btn-to));
            border: 1px solid rgba(191,255,0,0.40);
            color: #0A2914; border-radius: 9px;
            text-decoration: none; font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            white-space: nowrap; transition: all 0.25s;
            letter-spacing: 0.3px;
            box-shadow: 0 0 12px rgba(191,255,0,0.12);
        }
        .cordial-btn:hover {
            box-shadow: 0 0 28px rgba(191,255,0,0.35);
            color: #0A2914; transform: translateY(-2px);
        }

        /* ══════════════════════════════════════════════
           INFO GLASS PANELS
        ══════════════════════════════════════════════ */
        .info-glass {
            background: var(--glass-body);
            backdrop-filter: blur(18px);
            border-radius: 18px;
            border: 1px solid rgba(191,255,0,0.25);
            padding: 30px;
            box-shadow: 0 0 20px rgba(191,255,0,0.06),
                        0 8px 32px rgba(0,0,0,0.35),
                        inset 0 0 30px rgba(0,204,68,0.04);
            transition: all 0.3s ease;
            height: 100%;
        }
        .info-glass:hover {
            border-color: rgba(191,255,0,0.50);
            box-shadow: 0 0 40px rgba(191,255,0,0.15),
                        0 12px 40px rgba(0,0,0,0.45),
                        inset 0 0 40px rgba(0,204,68,0.08);
            transform: translateY(-4px);
        }

        /* ══════════════════════════════════════════════
           LOYALTY BANNER
        ══════════════════════════════════════════════ */
        .loyalty-banner {
            background: linear-gradient(135deg, rgba(0,102,34,0.50), rgba(10,41,20,0.60));
            border: 1.5px solid rgba(191,255,0,0.30);
            border-radius: 20px; padding: 44px;
            text-align: center; position: relative;
            overflow: hidden;
            backdrop-filter: blur(16px);
            box-shadow: 0 0 40px rgba(191,255,0,0.08),
                        0 8px 40px rgba(0,0,0,0.4),
                        inset 0 0 80px rgba(191,255,0,0.03);
        }
        .loyalty-banner::before {
            content: '🏆';
            position: absolute; top: -30px; right: -20px;
            font-size: 140px; opacity: 0.06;
            animation: trophyFloat 6s ease-in-out infinite;
            filter: drop-shadow(0 0 30px rgba(191,255,0,0.3));
        }
        @keyframes trophyFloat {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50%     { transform: translateY(-10px) rotate(3deg); }
        }
        .loyalty-banner h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700; margin-bottom: 12px;
            color: var(--text-prim);
            text-shadow: 0 0 14px rgba(191,255,0,0.20);
        }
        .loyalty-banner p {
            font-size: 14px; color: var(--text-sec); line-height: 1.8;
            max-width: 520px; margin: 0 auto;
        }
        .loyalty-banner .highlight {
            color: var(--bio-neon); font-weight: 700;
            text-shadow: 0 0 12px rgba(191,255,0,0.40);
        }
        .btn-loyalty {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 20px; padding: 11px 24px;
            background: linear-gradient(135deg, var(--btn-from), var(--btn-to));
            border: 1.5px solid rgba(191,255,0,0.45);
            color: #0A2914; border-radius: 12px;
            font-size: 14px; font-weight: 700;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s;
            letter-spacing: 0.3px;
            box-shadow: 0 0 20px rgba(191,255,0,0.15);
        }
        .btn-loyalty:hover {
            box-shadow: 0 0 40px rgba(191,255,0,0.35), 0 0 80px rgba(191,255,0,0.12);
            color: #0A2914; transform: translateY(-2px);
        }

        /* ══════════════════════════════════════════════
           FLOATING WHATSAPP — Reactive Float + Ring
        ══════════════════════════════════════════════ */
        .fab-whatsapp {
            position: fixed; bottom: 26px; right: 26px;
            z-index: 9999;
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, #25d366, #128C7E);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 32px rgba(37, 211, 102, 0.55),
                        0 0 60px rgba(37, 211, 102, 0.20),
                        0 0 120px rgba(37, 211, 102, 0.08);
            color: white; font-size: 29px; text-decoration: none;
            transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
            animation: fabFloat 3s ease-in-out infinite;
        }
        @keyframes fabFloat {
            0%,100% { transform: translateY(0); }
            50%     { transform: translateY(-8px); }
        }
        .fab-whatsapp::after {
            content: '';
            position: absolute; inset: -5px; border-radius: 50%;
            border: 2px solid rgba(37,211,102,0.40);
            animation: fabRing 2.5s ease-in-out infinite;
        }
        @keyframes fabRing {
            0%,100% { transform: scale(1); opacity: 0.7; }
            50%     { transform: scale(1.22); opacity: 0; }
        }
        .fab-whatsapp::before {
            content: '';
            position: absolute; inset: -10px; border-radius: 50%;
            border: 1px solid rgba(191,255,0,0.15);
            animation: fabRing2 3.5s ease-in-out infinite 0.5s;
        }
        @keyframes fabRing2 {
            0%,100% { transform: scale(1); opacity: 0.5; }
            50%     { transform: scale(1.35); opacity: 0; }
        }
        .fab-whatsapp:hover {
            transform: scale(1.18) translateY(-6px);
            box-shadow: 0 8px 40px rgba(37, 211, 102, 0.70),
                        0 0 80px rgba(37, 211, 102, 0.30),
                        0 0 140px rgba(191,255,0,0.10);
            color: white;
        }

        /* ══════════════════════════════════════════════
           FOOTER
        ══════════════════════════════════════════════ */
        .nc-footer {
            text-align: center; padding: 50px 20px;
            border-top: 1px solid rgba(191,255,0,0.12);
            margin-top: 60px;
            background: rgba(10,41,20,0.40);
        }
        .nc-footer p { font-size: 12px; color: var(--text-sec); line-height: 1.9; }
        .nc-footer .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 18px; font-weight: 700;
            letter-spacing: 2.5px; color: var(--text-prim);
            margin-bottom: 10px;
            text-shadow: 0 0 12px rgba(191,255,0,0.20);
        }

        /* ══════════════════════════════════════════════
           ANIMATIONS
        ══════════════════════════════════════════════ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeInUp 0.7s cubic-bezier(0.16,1,0.3,1) both; }

        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.16,1,0.3,1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* ══════════════════════════════════════════════
           SCROLLBAR
        ══════════════════════════════════════════════ */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--deep); }
        ::-webkit-scrollbar-thumb { background: rgba(191,255,0,0.20); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(191,255,0,0.40); }

        /* ══════════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════════ */
        @media(max-width: 576px) {
            .hero { padding: 55px 16px 30px; }
            .hero h1 { letter-spacing: 2px; font-size: 28px; }
            .nc-navbar { padding: 0 16px; }
            .nc-navbar .brand { font-size: 14px; letter-spacing: 2px; }
            .fab-whatsapp { width: 54px; height: 54px; font-size: 24px; bottom: 20px; right: 20px; }
            .product-img { height: 160px; }
            .section-title { font-size: 20px; }
            .filter-sidebar { margin-bottom: 20px; }
        }

        /* ══════════════════════════════════════════════
           FILTER SIDEBAR
        ══════════════════════════════════════════════ */
        .filter-sidebar {
            background: var(--glass-body);
            backdrop-filter: blur(18px);
            border-radius: 18px;
            border: 1px solid rgba(191,255,0,0.25);
            padding: 22px;
            box-shadow: 0 0 20px rgba(191,255,0,0.06), 0 8px 32px rgba(0,0,0,0.35);
            position: sticky;
            top: 80px;
        }
        .filter-section {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(191,255,0,0.10);
        }
        .filter-section:last-of-type { border-bottom: none; }
        .filter-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--bio-neon);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-shadow: 0 0 6px rgba(191,255,0,0.25);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .filter-input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(191,255,0,0.25);
            background: rgba(0,102,34,0.30);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .filter-input:focus {
            border-color: rgba(191,255,0,0.60);
            box-shadow: 0 0 16px rgba(191,255,0,0.15);
        }
        .filter-input::placeholder { color: rgba(210,231,179,0.4); }
        .filter-check {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-sec);
            transition: color 0.2s;
        }
        .filter-check:hover { color: var(--text-prim); }
        .filter-check input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(191,255,0,0.35);
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .filter-check input[type="radio"]:checked {
            border-color: var(--bio-neon);
            background: var(--bio-neon);
            box-shadow: 0 0 10px rgba(191,255,0,0.40),
                        inset 0 0 0 3px rgba(10,41,20,0.80);
        }
        .filter-btn {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1.5px solid rgba(191,255,0,0.40);
            background: linear-gradient(135deg, var(--btn-from), var(--btn-to));
            color: #0A2914;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 0 14px rgba(191,255,0,0.12);
        }
        .filter-btn:hover {
            box-shadow: 0 0 28px rgba(191,255,0,0.30);
            transform: translateY(-2px);
        }
        .filter-clear {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: var(--bio-cyan);
            font-size: 12px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .filter-clear:hover { color: #fff; }

        /* ══════════════════════════════════════════════
           ACTIVE FILTER BAR
        ══════════════════════════════════════════════ */
        .filter-active-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 12px 18px;
            background: rgba(191,255,0,0.06);
            border: 1px solid rgba(191,255,0,0.15);
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13px;
            color: var(--text-sec);
        }
        .filter-tag {
            background: rgba(191,255,0,0.12);
            border: 1px solid rgba(191,255,0,0.30);
            color: var(--bio-neon);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ══════════════════════════════════════════════
           BENEFIT TAGS ON PRODUCT CARDS
        ══════════════════════════════════════════════ */
        .product-benefits {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .benefit-tag {
            background: rgba(0,255,255,0.10);
            border: 1px solid rgba(0,255,255,0.25);
            color: var(--bio-cyan);
            padding: 3px 9px;
            border-radius: 14px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* ══════════════════════════════════════════════
           CORDIAL PRICING TABLE
        ══════════════════════════════════════════════ */
        .cordial-table-wrap {
            background: var(--glass-body);
            backdrop-filter: blur(18px);
            border-radius: 18px;
            border: 1px solid rgba(191,255,0,0.25);
            padding: 28px;
            box-shadow: 0 0 20px rgba(191,255,0,0.06), 0 8px 32px rgba(0,0,0,0.35);
        }
        .cordial-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Montserrat', sans-serif;
        }
        .cordial-table thead th {
            font-size: 12px;
            font-weight: 700;
            color: var(--bio-neon);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 14px;
            border-bottom: 2px solid rgba(191,255,0,0.25);
            text-align: center;
            text-shadow: 0 0 6px rgba(191,255,0,0.25);
        }
        .cordial-table tbody td {
            padding: 14px;
            font-size: 14px;
            color: var(--text-prim);
            border-bottom: 1px solid rgba(191,255,0,0.08);
            text-align: center;
        }
        .cordial-table tbody td:first-child { text-align: left; }
        .cordial-table tbody tr:hover {
            background: rgba(191,255,0,0.04);
        }
        .cordial-table tbody td strong {
            text-shadow: 0 0 4px rgba(191,255,0,0.10);
        }
    </style>

</head>
<body>
    <!-- Bio-breath glow overlay -->
    <div class="bg-breath"></div>
    <!-- Vein texture overlay -->
    <div class="bg-veins"></div>
    <!-- Bioluminescent Canvas -->
    <canvas id="bg-canvas"></canvas>

    <!-- Navbar -->
    <nav class="nc-navbar">
        <div class="logo-wrap">
            <div class="logo">🌿</div>
            <div class="brand">NATURA<span>COR</span></div>
        </div>
        <div class="d-none d-md-flex align-items-center gap-3" style="flex:1; justify-content:center;">
            <a href="#productos" style="color:var(--text-sec);text-decoration:none;font-size:13px;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='var(--bio-neon)'" onmouseout="this.style.color='var(--text-sec)'">Productos</a>
            <a href="#cordiales" style="color:var(--text-sec);text-decoration:none;font-size:13px;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='var(--bio-neon)'" onmouseout="this.style.color='var(--text-sec)'">Cordiales</a>
            <a href="#fidelizacion" style="color:var(--text-sec);text-decoration:none;font-size:13px;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='var(--bio-neon)'" onmouseout="this.style.color='var(--text-sec)'">Fidelización</a>
            <a href="#contacto" style="color:var(--text-sec);text-decoration:none;font-size:13px;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='var(--bio-neon)'" onmouseout="this.style.color='var(--text-sec)'">Contacto</a>
        </div>
        <a href="{{ url('/login') }}" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i>Iniciar Sesión
        </a>
    </nav>

    <!-- Page Content -->
    <main class="nc-page">
        @yield('content')
    </main>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/51{{ config('naturacor.empresa.whatsapp', '932857118') }}?text={{ urlencode('¡Hola! 🌿 Me interesa conocer más sobre los productos de NATURACOR.') }}"
       target="_blank" class="fab-whatsapp" title="Escríbenos por WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- Footer -->
    <footer class="nc-footer">
        <div class="footer-brand">🌿 NATURACOR</div>
        <p>
            Productos Naturales — Jauja, Junín, Perú<br>
            <i class="bi bi-clock"></i> Lun – Dom: 9:00 AM – 9:00 PM<br>
            <i class="bi bi-whatsapp"></i> {{ config('naturacor.empresa.whatsapp', '932857118') }}
        </p>
        <p style="margin-top:14px;font-size:11px;color:rgba(191,255,0,0.15);">
            © {{ date('Y') }} NATURACOR — Todos los derechos reservados
        </p>
    </footer>

    <!-- ═══ QUANTUM PARTICLE ENGINE ═══════════════════ -->
    <script>
    (function(){
        const c = document.getElementById('bg-canvas');
        const ctx = c.getContext('2d');
        let W, H, pts = [], mouse = {x: -999, y: -999};
        function resize(){ W = c.width = innerWidth; H = c.height = innerHeight; }
        resize(); addEventListener('resize', resize);
        addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });

        // Quantum particles — dense, mixed colors, twinkle
        const colors = [
            {r:191,g:255,b:0},     // Bio-Neon #BFFF00
            {r:191,g:255,b:0},     // Bio-Neon (majority)
            {r:191,g:255,b:0},     // Bio-Neon (majority)
            {r:0,g:255,b:255},     // Bio-Cyan #00FFFF
            {r:0,g:204,b:68},      // Deep-Glow #00CC44
        ];
        for(let i = 0; i < 120; i++){
            const col = colors[Math.floor(Math.random()*colors.length)];
            pts.push({
                x: Math.random()*3000, y: Math.random()*3000,
                r: Math.random()*2.2 + 0.4,
                dx: (Math.random()-0.5)*0.25,
                dy: (Math.random()-0.5)*0.25,
                pulse: Math.random()*Math.PI*2,
                pulseSpeed: Math.random()*0.03 + 0.01,
                twinkle: Math.random()*Math.PI*2,
                twinkleSpeed: Math.random()*0.05 + 0.02,
                col: col
            });
        }

        function draw(){
            ctx.clearRect(0,0,W,H);

            for(const p of pts){
                p.x += p.dx; p.y += p.dy;
                p.pulse += p.pulseSpeed;
                p.twinkle += p.twinkleSpeed;

                // Mouse repulsion (subtle)
                const mdx = p.x - mouse.x, mdy = p.y - mouse.y;
                const md = Math.sqrt(mdx*mdx + mdy*mdy);
                if(md < 150 && md > 0){
                    p.x += (mdx/md)*0.8;
                    p.y += (mdy/md)*0.8;
                }

                if(p.x < -20) p.x = W+20; if(p.x > W+20) p.x = -20;
                if(p.y < -20) p.y = H+20; if(p.y > H+20) p.y = -20;

                const alpha = (0.25 + Math.sin(p.pulse)*0.15) * (0.7 + Math.sin(p.twinkle)*0.3);
                const c = p.col;

                // Outer glow
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r*5, 0, Math.PI*2);
                ctx.fillStyle = `rgba(${c.r},${c.g},${c.b},${alpha*0.08})`; ctx.fill();

                // Core glow
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r*2.5, 0, Math.PI*2);
                ctx.fillStyle = `rgba(${c.r},${c.g},${c.b},${alpha*0.18})`; ctx.fill();

                // Particle
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
                ctx.fillStyle = `rgba(${c.r},${c.g},${c.b},${alpha})`; ctx.fill();
            }

            // Connect nearby particles with luminous threads
            for(let i = 0; i < pts.length; i++){
                for(let j = i+1; j < pts.length; j++){
                    const dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y;
                    const d = Math.sqrt(dx*dx + dy*dy);
                    if(d < 120){
                        const a = 0.07*(1-d/120);
                        ctx.beginPath(); ctx.moveTo(pts[i].x, pts[i].y); ctx.lineTo(pts[j].x, pts[j].y);
                        ctx.strokeStyle = `rgba(191,255,0,${a})`; ctx.lineWidth = 0.6; ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(draw);
        }
        draw();
    })();
    </script>

    <!-- ═══ SCROLL REVEAL ═══════════════════════════════ -->
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const els = document.querySelectorAll('.reveal');
        const obs = new IntersectionObserver((ents) => {
            ents.forEach((e, i) => {
                if(e.isIntersecting){
                    setTimeout(() => e.target.classList.add('visible'), i * 80);
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        els.forEach(el => obs.observe(el));
    });
    </script>

    <!-- ═══ 3D TILT + GLOW ON PRODUCT CARDS ════════════ -->
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const r = card.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform = `translateY(-8px) scale(1.02) perspective(800px) rotateY(${x*8}deg) rotateX(${-y*8}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
