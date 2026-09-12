<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Segera Hadir &middot; {{ $situs->judul ?: config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif; color: #fff; margin: 0;
            min-height: 100vh; display: grid; place-items: center; padding: 1.5rem;
            text-align: center; overflow: hidden;
            background:
                radial-gradient(1000px 520px at 12% -10%, #1B4FA8 0%, transparent 55%),
                radial-gradient(900px 620px at 108% 18%, #22A6C9 0%, transparent 52%),
                linear-gradient(135deg, #0B2545 0%, #123a72 45%, #0B2545 100%);
        }
        .titik {
            position: fixed; inset: 0; pointer-events: none; opacity: .4;
            background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1.4px);
            background-size: 26px 26px;
            mask-image: radial-gradient(circle at 50% 45%, #000 0%, transparent 70%);
        }
        .isi { position: relative; z-index: 1; max-width: 520px; }
        .merek {
            display: inline-grid; place-items: center;
            width: 62px; height: 62px; border-radius: 1rem; margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #22A6C9, #12459B);
            font-weight: 800; font-size: 1.3rem;
            box-shadow: 0 14px 30px -12px rgba(34,166,201,.9);
        }
        h1 { font-weight: 800; letter-spacing: -.03em; font-size: clamp(1.6rem, 4vw, 2.2rem); margin: 0 0 .75rem; }
        p { color: rgba(255,255,255,.75); line-height: 1.65; margin: 0 0 1.75rem; }
        .btn-masuk {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.28);
            color: #fff; text-decoration: none; font-weight: 600; font-size: .9rem;
            padding: .6rem 1.3rem; border-radius: .6rem; transition: background .15s ease;
        }
        .btn-masuk:hover { background: rgba(255,255,255,.2); color: #fff; }
    </style>
</head>
<body>
    <div class="titik"></div>

    <div class="isi">
        <div class="merek">PK</div>
        <h1>{{ $situs->judul ?: config('app.name') }}</h1>
        <p>
            Halaman publik belum dibuka. Informasi seleksi dan pengumuman hasil
            akan ditayangkan di sini setelah panitia mengaktifkannya.
        </p>
        <a href="{{ route('login') }}" class="btn-masuk">
            <i class="bi bi-box-arrow-in-right"></i> Masuk Panitia
        </a>
    </div>
</body>
</html>
