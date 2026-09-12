<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Panel') &middot; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:      #0B2545;
            --biru:      #12459B;
            --biru-muda: #E8EFFA;
            --cyan:      #22A6C9;
            --teal:      #0E9B8A;
            --amber:     #D98B2B;
            --emas:      #F5B335;
            --emas-tua:  #C98D18;
            --hijau:     #0E9B6A;
            --merah:     #C0392B;
            --merah-muda:#FBE6E4;
            --latar:     #F4F6FA;
            --garis:     #E2E7F0;
            --redup:     #64748B;
            --sidebar-w: 244px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--latar);
            color: #101828;
            font-weight: 500;
        }

        h1, .h1, h2, .h2, h3, .h3, h4, .h4 {
            font-family: 'Inter', Arial, sans-serif;
            letter-spacing: -.02em;
            font-weight: 700;
        }
        h5, .h5, h6, .h6 { letter-spacing: -.01em; font-weight: 600; }

        a { color: var(--biru); }

        /* ============================================ SHELL */
        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-w);
            flex: 0 0 var(--sidebar-w);
            position: fixed;
            inset: 0 auto 0 0;
            display: flex;
            flex-direction: column;
            z-index: 1040;
            color: #B7C6DE;
            border-right: 1px solid rgba(255,255,255,.06);
            background:
                radial-gradient(130% 55% at 0% 0%, rgba(34,166,201,.18), transparent 60%),
                linear-gradient(180deg, #0E2C55 0%, #0B2545 45%, #071A34 100%);
        }
        .sidebar::before {
            content: "";
            position: absolute; inset: 0;
            pointer-events: none; opacity: .5; mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }
        .sidebar > * { position: relative; z-index: 1; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1.15rem 1.3rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
        }
        .sidebar-brand .merek {
            display: inline-grid;
            place-items: center;
            width: 36px; height: 36px;
            flex: 0 0 36px;
            border-radius: .6rem;
            background: linear-gradient(135deg, var(--cyan), var(--biru));
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            box-shadow: 0 6px 16px -6px rgba(34,166,201,.75);
            transition: transform .2s ease;
        }
        .sidebar-brand:hover .merek { transform: rotate(-6deg) scale(1.05); }

        .sidebar-seksi {
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255,255,255,.4);
            padding: 1.15rem 1.4rem .45rem;
        }

        .sidebar-nav { flex: 1; overflow-y: auto; padding-bottom: 1rem; }

        .sidebar-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: .12rem .6rem;
            padding: .6rem .8rem;
            border-radius: .6rem;
            font-size: .9rem;
            font-weight: 600;
            color: #B7C6DE;
            text-decoration: none;
            transition: background .14s ease, color .14s ease, transform .1s ease;
        }
        .sidebar-link i { font-size: 1.02rem; opacity: .7; width: 1.15rem; text-align: center; }
        .sidebar-link:hover { background: rgba(255,255,255,.07); color: #fff; }
        .sidebar-link:hover i { opacity: 1; }
        .sidebar-link:active { transform: translateX(1px); }

        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(34,166,201,.28), rgba(34,166,201,.04));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(34,166,201,.28);
        }
        .sidebar-link.active i { opacity: 1; color: var(--cyan); }
        .sidebar-link.active::before {
            content: "";
            position: absolute; left: -.6rem; top: .5rem; bottom: .5rem;
            width: 3px; border-radius: 0 3px 3px 0;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan);
        }

        .sidebar-user {
            border-top: 1px solid rgba(255,255,255,.08);
            padding: .9rem .85rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            background: rgba(0,0,0,.18);
        }
        .sidebar-user .info-user {
            flex: 1 1 auto;
            min-width: 0;           /* wajib supaya anak flex boleh menyusut & ter-ellipsis */
        }
        .sidebar-user .info-user > div {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sidebar-user .nama-user { font-size: .82rem; font-weight: 700; color: #fff; }
        .sidebar-user .peran-user {
            font-size: .7rem;
            color: rgba(255,255,255,.55);
            text-transform: capitalize;
        }
        .sidebar-user form { flex: 0 0 auto; }

        .avatar-bulat {
            width: 38px; height: 38px;
            flex: 0 0 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--biru));
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: .85rem;
            box-shadow: 0 4px 12px -4px rgba(34,166,201,.6);
        }

        .btn-keluar {
            border: 0;
            background: transparent;
            color: rgba(255,255,255,.5);
            padding: .35rem .4rem;
            line-height: 1;
            border-radius: .4rem;
            transition: background .14s ease, color .14s ease;
        }
        .btn-keluar:hover { color: #fff; background: rgba(192,57,43,.9); }

        .sidebar-backdrop {
            position: fixed; inset: 0;
            background: rgba(4,14,35,.5);
            z-index: 1035;
            opacity: 0; visibility: hidden;
            transition: opacity .2s ease, visibility .2s ease;
        }
        .app-shell.buka-sidebar .sidebar-backdrop { opacity: 1; visibility: visible; }
        @media (min-width: 992px) { .sidebar-backdrop { display: none; } }

        /* ============================================ MAIN */
        .app-main {
            flex: 1;
            margin-left: var(--sidebar-w);
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: linear-gradient(180deg, #fff, #FBFCFE);
            border-bottom: 1px solid var(--garis);
            padding: 1.1rem 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: box-shadow .22s ease;
        }
        .topbar.scrolled { box-shadow: 0 8px 26px -16px rgba(11,37,69,.3); }

        .judul-halaman {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -.02em;
            margin: 0;
        }
        .subjudul-halaman { font-size: .82rem; color: var(--redup); margin: .1rem 0 0; }

        .app-konten { padding: 1.75rem; flex: 1; }

        .app-konten > * {
            animation: rise .5s cubic-bezier(.2,.7,.2,1) both;
        }
        .app-konten > *:nth-child(1) { animation-delay: .04s; }
        .app-konten > *:nth-child(2) { animation-delay: .10s; }
        .app-konten > *:nth-child(3) { animation-delay: .16s; }
        .app-konten > *:nth-child(4) { animation-delay: .22s; }
        .app-konten > *:nth-child(5) { animation-delay: .28s; }
        .app-konten > *:nth-child(6) { animation-delay: .34s; }
        .app-konten > .offcanvas, .app-konten > .modal { animation: none !important; }
        @keyframes rise { from { opacity: 0; transform: translateY(16px); } }

        /* ============================================ KOMPONEN */
        .panel {
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: .875rem;
            box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.03);
            transition: box-shadow .2s ease;
        }
        .panel:hover { box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 14px 32px -20px rgba(11,37,69,.28); }

        .statistik {
            background: #fff;
            border: 1px solid var(--garis);
            border-radius: .875rem;
            padding: 1.1rem 1.25rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.03);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .statistik:hover {
            transform: translateY(-3px);
            box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 16px 30px -18px rgba(11,37,69,.28);
        }
        .statistik::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--aksen, var(--biru));
        }
        .statistik-label {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--redup);
        }
        .statistik-angka {
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--navy);
            font-variant-numeric: tabular-nums;
            margin-top: .3rem;
        }
        .statistik-catatan { font-size: .78rem; color: var(--redup); margin-top: .15rem; }

        .label-filter {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--redup);
            margin-bottom: .3rem;
        }

        .form-control, .form-select { border-color: var(--garis); font-size: .9rem; }
        .form-control:focus, .form-select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 .2rem rgba(34,166,201,.15);
        }

        .btn-utama {
            background: var(--biru);
            border-color: var(--biru);
            color: #fff;
            font-weight: 600;
            transition: background .15s ease, transform .1s ease, box-shadow .15s ease;
        }
        .btn-utama:hover {
            background: #0E3878;
            border-color: #0E3878;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -12px rgba(18,69,155,.7);
        }
        .btn-utama:active { transform: translateY(0); }

        /* tombol yang tampil sebagai teks-link di kolom Aksi */
        .aksi-link {
            border: 0; background: none; padding: 0;
            font: inherit; font-weight: 600; font-size: .85rem;
            color: var(--biru); text-decoration: none; cursor: pointer;
        }
        .aksi-link:hover { text-decoration: underline; }
        .aksi-link.danger { color: var(--merah); }
        .aksi-pisah { color: var(--garis); margin: 0 .1rem; }

        .tabel-rapi { --bs-table-bg: transparent; }
        .tabel-rapi thead th {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--redup);
            border-bottom: 1px solid var(--garis);
            white-space: nowrap;
            padding: .85rem 1rem;
            background: #FBFCFE;
        }
        .tabel-rapi td { font-size: .86rem; border-color: var(--garis); padding: .85rem 1rem; vertical-align: middle; }
        .tabel-rapi tbody tr { transition: background .12s ease; }
        .tabel-rapi tbody tr:hover { background: #F5F8FD; }

        .tag {
            display: inline-block;
            font-size: .72rem;
            font-weight: 600;
            border-radius: 999px;
            padding: .18rem .6rem;
            background: var(--biru-muda);
            color: var(--biru);
        }
        .tag-netral { background: #EEF1F6; color: var(--redup); }
        .tag-kuning { background: #FBF0E0; color: var(--amber); }
        .tag-hijau  { background: #E3F5EC; color: var(--hijau); }
        .tag-merah  { background: #FBE6E4; color: var(--merah); }

        /* status verifikasi — BACA-SAJA. Diubah lewat menu Verifikasi Administrasi
           supaya setiap keputusan punya jejak butir Juklak mana yang gagal. */
        .status-baca {
            display: inline-block;
            font-weight: 700;
            font-size: .82rem;
            border: 1.5px solid transparent;
            border-radius: 999px;
            padding: .34rem 1rem;
        }
        .status-baca.st-submitted { color: var(--amber); border-color: #E6C58C; background-color: #FBF0E0; }
        .status-baca.st-verified  { color: var(--hijau); border-color: #9BDBB6; background-color: #E3F5EC; }
        .status-baca.st-rejected  { color: var(--merah); border-color: #EBB0A8; background-color: #FBE6E4; }
        .status-hint { font-size: .72rem; color: var(--redup); }
        a.status-hint:hover { color: var(--biru); text-decoration: underline !important; }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--biru-muda);
            color: var(--biru);
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            font-weight: 600;
        }

        .page-link { color: var(--biru); border-color: var(--garis); font-size: .85rem; }
        .page-item.active .page-link { background: var(--biru); border-color: var(--biru); }

        .kotak-kosong {
            background: #F8FAFD;
            border: 1px dashed var(--garis);
            border-radius: .75rem;
            padding: 2rem 1.25rem;
            text-align: center;
            color: var(--redup);
            font-size: .85rem;
        }

        .banner-info {
            background: #FDF8EF;
            border: 1px solid #F0E0C4;
            border-radius: .7rem;
            padding: .75rem 1rem;
            font-size: .82rem;
            color: #6B5330;
            display: flex;
            gap: .6rem;
            align-items: flex-start;
        }

        /* ---- kartu seksi (dipakai di detail pendaftar) ---- */
        .kartu-seksi {
            border: 1px solid var(--garis);
            border-radius: .75rem;
            padding: 1rem 1.1rem;
            margin-bottom: .85rem;
        }
        .kartu-judul {
            font-size: .78rem;
            font-weight: 700;
            color: var(--navy);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .7rem;
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .kartu-judul i { color: var(--biru); }

        /* ---- modal detail (di tengah) ---- */
        .modal-detail .modal-header { background: #FBFCFE; }

        /* ---- modal konfirmasi hapus ---- */
        .hapus-ikon {
            width: 44px; height: 44px; flex: 0 0 44px;
            border-radius: 50%;
            display: grid; place-items: center;
            background: var(--merah-muda);
            color: var(--merah);
            font-size: 1.15rem;
        }
        .hapus-ringkas {
            background: #F8FAFD;
            border: 1px solid var(--garis);
            border-radius: .6rem;
            padding: .7rem .9rem;
        }
        .hapus-rincian { margin: 0; padding-left: 1.1rem; color: var(--redup); }
        .hapus-rincian li { margin-bottom: .25rem; }

        /* ---- toast flash ---- */
        .flash-stack {
            position: fixed;
            top: 5rem;
            right: 1.25rem;
            z-index: 1080;
            width: 340px;
            max-width: calc(100vw - 2rem);
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }
        .flash-stack .alert {
            box-shadow: 0 14px 34px -14px rgba(16,24,40,.45);
            animation: flash-in .28s ease;
        }
        @keyframes flash-in { from { opacity: 0; transform: translateX(24px); } }

        /* ============================================ RESPONSIF */
        .sidebar-toggle { display: none; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
            .app-shell.buka-sidebar .sidebar { transform: none; }
            .app-main { margin-left: 0; }
            .sidebar-toggle { display: inline-flex; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-delay: 0ms !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
    @stack('gaya')
</head>
<body>
<div class="app-shell" id="shell">
    <div class="sidebar-backdrop" onclick="document.getElementById('shell').classList.remove('buka-sidebar')"></div>

    @php
        $adalahReviewer = auth()->user()->isReviewer();
        // [route, pola-aktif, label, ikon, hanya-admin?] — untuk reviewer hanya Rubrik yang disembunyikan
        $menuUtama = [
            ['admin.statistik',       'admin.statistik',   'Statistik',   'bi-bar-chart-line',   false],
            ['admin.pendaftar.index', 'admin.pendaftar.*',  'Pendaftar',   'bi-people',           false],
            ['admin.pengunjung',      'admin.pengunjung',   'Pengunjung',  'bi-eye',              false],
            ['admin.verifikasi.index', 'admin.verifikasi.*', 'Verifikasi Adm.', 'bi-ui-checks',       false],
            ['admin.penilaian.index', 'admin.penilaian.*',  'Penilaian',   'bi-clipboard-check',  false],
            ['admin.rekap.index',     'admin.rekap.*',      'Rekap Nilai', 'bi-list-ol',          false],
        ];
        $menuKonfig = [
            ['admin.penugasan.index', 'admin.penugasan.*',  'Penugasan',        'bi-diagram-3', false],
            ['admin.formadm.index',   'admin.formadm.*',    'Form Administrasi', 'bi-ui-checks-grid', true],
            ['admin.situs.index',     'admin.situs.*',      'Halaman Publik',   'bi-globe2',    true],
            ['admin.rubrik.index',    'admin.rubrik.*',     'Rubrik Penilaian', 'bi-sliders',   true],
        ];
        $bolehLihat = fn ($item) => ! $item[4] || ! $adalahReviewer;
        $menuUtama = array_filter($menuUtama, $bolehLihat);
        $menuKonfig = array_filter($menuKonfig, $bolehLihat);
        $rutaBeranda = $adalahReviewer ? 'admin.penilaian.index' : 'admin.statistik';
    @endphp

    <aside class="sidebar">
        <a href="{{ route($rutaBeranda) }}" class="sidebar-brand text-decoration-none">
            <span class="merek">PK</span>
            <span>{{ config('app.name') }}</span>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-seksi">Startup Competition</div>
            @foreach ($menuUtama as [$route, $pola, $label, $ikon])
                <a href="{{ route($route) }}"
                   class="sidebar-link {{ request()->routeIs($pola) ? 'active' : '' }}">
                    <i class="bi {{ $ikon }}"></i>{{ $label }}
                </a>
            @endforeach

            @if (count($menuKonfig))
                <div class="sidebar-seksi">Konfigurasi</div>
                @foreach ($menuKonfig as [$route, $pola, $label, $ikon])
                    <a href="{{ route($route) }}"
                       class="sidebar-link {{ request()->routeIs($pola) ? 'active' : '' }}">
                        <i class="bi {{ $ikon }}"></i>{{ $label }}
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="sidebar-user">
            <span class="avatar-bulat">{{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</span>
            <div class="info-user">
                <div class="nama-user" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                <div class="peran-user">{{ auth()->user()->peran }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-keluar" title="Keluar"><i class="bi bi-box-arrow-right fs-6"></i></button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar" id="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary sidebar-toggle" onclick="document.getElementById('shell').classList.toggle('buka-sidebar')">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="judul-halaman">@yield('judul', 'Panel')</h1>
                    @hasSection('subjudul')
                        <p class="subjudul-halaman">@yield('subjudul')</p>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @yield('aksi')
            </div>
        </header>

        <main class="app-konten">
            @include('partials.flash')
            @yield('konten')
        </main>
    </div>
</div>

<div class="flash-stack" id="flashStack"></div>

@include('partials.konfirmasi-hapus')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---- bayangan topbar saat scroll ----
(function () {
    const bar = document.getElementById('topbar');
    if (!bar) return;
    const main = document.querySelector('.app-main');
    const sync = () => bar.classList.toggle('scrolled', (main ? main.scrollTop : window.scrollY) > 4 || window.scrollY > 4);
    sync();
    window.addEventListener('scroll', sync, { passive: true });
})();

// ---- flash: pindahkan alert sukses/gagal ke pojok kanan atas & auto-tutup ----
(function () {
    const stack = document.getElementById('flashStack');
    if (!stack) return;
    document.querySelectorAll('.js-flash-auto').forEach((el) => {
        stack.appendChild(el);
        setTimeout(() => {
            el.style.transition = 'opacity .4s ease, transform .4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateX(24px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });
})();

// ---- modal konfirmasi hapus (ketik ulang nama) ----
(function () {
    const el = document.getElementById('modalHapus');
    if (!el) return;
    const modal = new bootstrap.Modal(el);
    const $nama = document.getElementById('hapusNama');
    const $sub = document.getElementById('hapusSub');
    const $frasa = document.getElementById('hapusFrasa');
    const $rincian = document.getElementById('hapusRincian');
    const $ketik = document.getElementById('hapusKetik');
    const $ketikWrap = document.getElementById('hapusKetikWrap');
    const $ok = document.getElementById('hapusKonfirmasi');
    const $form = document.getElementById('hapusForm');
    let target = '';
    let langsung = false;

    function sync() {
        if (langsung) { $ok.disabled = false; return; }
        $ok.disabled = $ketik.value.trim() !== ($nama.textContent || '').trim();
    }

    document.addEventListener('click', function (e) {
        const t = e.target.closest('[data-hapus-url]');
        if (!t) return;
        e.preventDefault();

        target = t.getAttribute('data-hapus-url');
        langsung = t.hasAttribute('data-hapus-langsung');
        const nama = t.getAttribute('data-hapus-nama') || 'data ini';
        $nama.textContent = nama;
        $sub.textContent = t.getAttribute('data-hapus-sub') || '';
        $sub.hidden = !$sub.textContent;
        $frasa.textContent = '"' + nama + '"';

        let items = [];
        try { items = JSON.parse(t.getAttribute('data-hapus-rincian') || '[]'); } catch (_) {}
        $rincian.innerHTML = '';
        items.forEach((txt) => {
            const li = document.createElement('li');
            li.textContent = txt;
            $rincian.appendChild(li);
        });
        $rincian.hidden = items.length === 0;

        $ketikWrap.hidden = langsung;
        $ketik.value = '';
        sync();

        // kalau dipicu dari dalam modal/panel lain, tutup dulu baru buka konfirmasi
        const induk = t.closest('.modal.show, .offcanvas.show');
        const bukaKonfirmasi = () => {
            modal.show();
            setTimeout(() => (langsung ? $ok : $ketik).focus(), 350);
        };

        if (induk) {
            const inst = bootstrap.Modal.getInstance(induk) || bootstrap.Offcanvas.getInstance(induk);
            induk.addEventListener('hidden.bs.modal', bukaKonfirmasi, { once: true });
            induk.addEventListener('hidden.bs.offcanvas', bukaKonfirmasi, { once: true });
            inst ? inst.hide() : bukaKonfirmasi();
        } else {
            bukaKonfirmasi();
        }
    });

    $ketik.addEventListener('input', sync);
    $ketik.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !$ok.disabled) { e.preventDefault(); $ok.click(); }
    });

    $ok.addEventListener('click', function () {
        if ($ok.disabled) return;
        $ok.disabled = true;
        $ok.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus…';
        $form.setAttribute('action', target);
        $form.submit();
    });
})();
</script>

@stack('skrip')
</body>
</html>
