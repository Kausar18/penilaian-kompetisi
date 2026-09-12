<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Masuk') &middot; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #0B2545; --biru: #12459B; --cyan: #22A6C9;
            --latar: #F4F6FA; --garis: #E2E7F0; --redup: #64748B;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            color: #101828;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
            background:
                radial-gradient(1200px 600px at 12% -10%, #1B4FA8 0%, transparent 55%),
                radial-gradient(1000px 700px at 110% 20%, #22A6C9 0%, transparent 50%),
                linear-gradient(135deg, #0B2545 0%, #123a72 40%, #0E3B86 60%, #0B2545 100%);
            background-size: 100% 100%, 100% 100%, 200% 200%;
            animation: geser-gradien 18s ease-in-out infinite;
        }
        @keyframes geser-gradien {
            0%, 100% { background-position: 0% 0%, 100% 20%, 0% 50%; }
            50%      { background-position: 0% 0%, 100% 20%, 100% 50%; }
        }

        /* ==== lapisan latar ==== */
        .bg-layer { position: fixed; inset: 0; z-index: 0; pointer-events: none; }

        /* tekstur grain (SVG noise, di-inline sebagai data URI) */
        .bg-noise {
            opacity: .5; mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }
        /* tekstur titik-titik (dot grid) */
        .bg-dots {
            opacity: .5;
            background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1.4px);
            background-size: 26px 26px;
            mask-image: radial-gradient(circle at 50% 42%, #000 0%, transparent 72%);
            animation: geser-dots 30s linear infinite;
        }
        @keyframes geser-dots { to { background-position: 260px 260px; } }
        /* garis grid halus */
        .bg-grid {
            opacity: .3;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 78%);
        }

        /* orb / blob mengambang */
        .bg-orb { position: fixed; border-radius: 50%; filter: blur(70px); z-index: 0;
            pointer-events: none; opacity: .5; }
        .bg-orb.a { width: 460px; height: 460px; left: -140px; bottom: -160px;
            background: radial-gradient(circle, #22A6C9, transparent 70%);
            animation: apung-a 16s ease-in-out infinite; }
        .bg-orb.b { width: 520px; height: 520px; right: -180px; top: -200px;
            background: radial-gradient(circle, #3E7BD9, transparent 70%);
            animation: apung-b 20s ease-in-out infinite; }
        .bg-orb.c { width: 300px; height: 300px; left: 55%; top: 60%;
            background: radial-gradient(circle, #1E63C8, transparent 70%);
            animation: apung-c 24s ease-in-out infinite; }
        @keyframes apung-a { 50% { transform: translate(60px, -50px) scale(1.12); } }
        @keyframes apung-b { 50% { transform: translate(-70px, 60px) scale(1.08); } }
        @keyframes apung-c { 50% { transform: translate(-50px, -40px) scale(1.15); } }

        .tamu-wrap {
            position: relative; z-index: 1; width: 100%;
            animation: naik 620ms cubic-bezier(.2,.7,.2,1) both;
        }
        @keyframes naik { from { opacity: 0; transform: translateY(18px) scale(.98); } }

        .panel {
            background: rgba(255,255,255,.98);
            border: 1px solid rgba(255,255,255,.5);
            border-radius: 1.1rem;
            box-shadow: 0 30px 70px -20px rgba(4,14,35,.55), 0 8px 24px -12px rgba(4,14,35,.4);
        }

        /* ==== split layout khusus login ==== */
        .auth-card {
            display: grid; grid-template-columns: 1.05fr 1fr;
            overflow: hidden; border-radius: 1.1rem;
            background: #fff;
            border: 1px solid rgba(255,255,255,.5);
            box-shadow: 0 30px 70px -20px rgba(4,14,35,.55), 0 8px 24px -12px rgba(4,14,35,.4);
        }
        .auth-brand {
            position: relative; color: #fff; padding: 2.75rem 2.5rem;
            background: linear-gradient(160deg, #12459B 0%, #0B2545 70%);
            display: flex; flex-direction: column; justify-content: space-between;
            overflow: hidden;
        }
        /* tekstur pada panel brand */
        .auth-brand .brand-tex {
            position: absolute; inset: 0; z-index: 0; opacity: .5;
            background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1.4px);
            background-size: 22px 22px;
            mask-image: linear-gradient(160deg, #000 0%, transparent 85%);
        }
        .auth-brand::after {
            content: ""; position: absolute; right: -90px; top: -90px;
            width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(34,166,201,.55), transparent 70%);
            animation: apung-c 14s ease-in-out infinite;
        }
        .auth-brand::before {
            content: ""; position: absolute; left: -70px; bottom: -80px;
            width: 220px; height: 220px; border-radius: 50%;
            background: radial-gradient(circle, rgba(62,123,217,.4), transparent 70%);
            animation: apung-a 18s ease-in-out infinite;
        }
        .auth-brand > * { position: relative; z-index: 1; }
        .auth-brand h2 { font-weight: 800; font-size: 1.5rem; line-height: 1.25; margin: 0; }
        .auth-brand p { color: rgba(255,255,255,.78); font-size: .9rem; margin: .75rem 0 0; }
        .auth-brand .poin { list-style: none; padding: 0; margin: 1.75rem 0 0; }
        .auth-brand .poin li { display: flex; align-items: center; gap: .6rem;
            font-size: .875rem; color: rgba(255,255,255,.9); margin-bottom: .7rem;
            animation: naik 600ms ease both; }
        .auth-brand .poin li:nth-child(1) { animation-delay: .15s; }
        .auth-brand .poin li:nth-child(2) { animation-delay: .28s; }
        .auth-brand .poin li:nth-child(3) { animation-delay: .41s; }
        .auth-brand .poin i { color: var(--cyan); font-size: 1.05rem;
            animation: denyut 2.4s ease-in-out infinite; }
        @keyframes denyut { 50% { opacity: .55; transform: scale(.88); } }
        .auth-form { padding: 2.75rem 2.5rem; }

        .label-filter {
            font-size: .72rem; font-weight: 600; letter-spacing: .05em;
            text-transform: uppercase; color: var(--redup); margin-bottom: .3rem;
        }
        .input-ikon { position: relative; }
        .input-ikon > i {
            position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
            color: var(--redup); font-size: 1rem; transition: color .15s ease;
        }
        .input-ikon:focus-within > i { color: var(--cyan); }
        .input-ikon .form-control { padding-left: 2.5rem; }
        .form-control { border-color: var(--garis); padding-top: .6rem; padding-bottom: .6rem;
            transition: border-color .15s ease, box-shadow .15s ease; }
        .form-control:focus { border-color: var(--cyan); box-shadow: 0 0 0 .2rem rgba(34,166,201,.15); }
        .btn-utama {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, var(--biru), var(--navy));
            border: none; color: #fff; font-weight: 600; padding: .65rem 1rem;
            box-shadow: 0 10px 22px -10px rgba(18,69,155,.7);
            transition: transform .12s ease, filter .15s ease, box-shadow .15s ease;
        }
        .btn-utama:hover { filter: brightness(1.09); color: #fff;
            transform: translateY(-1px); box-shadow: 0 14px 28px -12px rgba(18,69,155,.8); }
        .btn-utama:active { transform: translateY(0); }
        /* kilau bergerak di tombol */
        .btn-utama::after {
            content: ""; position: absolute; top: 0; left: -120%;
            width: 55%; height: 100%; transform: skewX(-22deg);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
            animation: kilau 3.6s ease-in-out infinite;
        }
        @keyframes kilau { 0%, 60% { left: -120%; } 100% { left: 160%; } }
        .merek {
            display: inline-grid; place-items: center;
            width: 46px; height: 46px; border-radius: .7rem;
            background: linear-gradient(135deg, var(--cyan), var(--biru));
            color: #fff; font-weight: 800; font-size: 1.05rem;
            box-shadow: 0 8px 18px -8px rgba(34,166,201,.7);
        }

        @media (max-width: 768px) {
            .auth-card { grid-template-columns: 1fr; }
            .auth-brand { padding: 2rem 1.75rem; }
            .auth-brand .poin { display: none; }
            .auth-form { padding: 2rem 1.75rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            body, .bg-dots, .bg-orb, .tamu-wrap,
            .auth-brand::before, .auth-brand::after,
            .auth-brand .poin li, .auth-brand .poin i, .btn-utama::after { animation: none !important; }
        }
    </style>
</head>
<body>
    <div class="bg-layer bg-grid"></div>
    <div class="bg-layer bg-dots"></div>
    <div class="bg-layer bg-noise"></div>
    <div class="bg-orb a"></div>
    <div class="bg-orb b"></div>
    <div class="bg-orb c"></div>

    <div class="tamu-wrap" style="max-width: @yield('lebar', '410px'); margin-inline: auto;">
        @yield('konten')
    </div>
</body>
</html>
