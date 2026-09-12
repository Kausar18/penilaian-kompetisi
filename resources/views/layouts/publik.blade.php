<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') &middot; {{ $situs->judul ?: config('app.name') }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($situs->deskripsi ?? ''), 155) }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #0B2545; --biru: #12459B; --cyan: #22A6C9;
            --latar: #F4F6FA; --garis: #E2E7F0; --redup: #64748B;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            color: #101828; background: var(--latar);
            margin: 0; overflow-x: hidden;
        }

        /* ================= NAVBAR ================= */
        .nav-publik {
            position: sticky; top: 0; z-index: 50;
            background: rgba(11, 37, 69, .92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,.08);
            transition: background .25s ease, box-shadow .25s ease;
        }
        .nav-publik.menempel { box-shadow: 0 8px 24px -14px rgba(4,14,35,.8); }
        .nav-publik .isi {
            position: relative;
            max-width: 1140px; margin: 0 auto; padding: .8rem 1.25rem;
            display: flex; align-items: center; gap: 1rem;
        }
        .nav-merek {
            display: flex; align-items: center; gap: .65rem;
            color: #fff; text-decoration: none; font-weight: 800;
            letter-spacing: -.01em; margin-right: auto; min-width: 0;
        }
        .nav-merek .kotak {
            display: inline-grid; place-items: center;
            width: 38px; height: 38px; border-radius: .6rem; flex: none;
            background: linear-gradient(135deg, var(--cyan), var(--biru));
            font-size: .9rem; box-shadow: 0 8px 18px -8px rgba(34,166,201,.8);
        }
        .nav-merek .teks { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nav-tautan { display: flex; align-items: center; gap: .25rem; }
        .nav-tautan a {
            color: rgba(255,255,255,.82); text-decoration: none;
            font-size: .9rem; font-weight: 500;
            padding: .45rem .85rem; border-radius: .5rem;
            transition: background .15s ease, color .15s ease;
        }
        .nav-tautan a:hover { color: #fff; background: rgba(255,255,255,.1); }
        .nav-tautan a.aktif { color: #fff; background: rgba(34,166,201,.22); }
        .btn-panel {
            background: linear-gradient(135deg, var(--cyan), var(--biru));
            color: #fff !important; font-weight: 600;
            padding: .45rem 1rem !important; border-radius: .5rem;
            box-shadow: 0 8px 18px -10px rgba(34,166,201,.9);
        }
        .btn-panel:hover { filter: brightness(1.1); }
        .nav-toggle {
            display: none; background: none; border: 1px solid rgba(255,255,255,.25);
            color: #fff; border-radius: .5rem; padding: .3rem .6rem; font-size: 1.1rem;
        }

        /* ================= SEKSI UMUM ================= */
        .bungkus { max-width: 1140px; margin: 0 auto; padding-inline: 1.25rem; }
        .seksi { padding: 4.5rem 0; }
        .seksi-judul {
            font-weight: 800; font-size: clamp(1.5rem, 3.2vw, 2.1rem);
            letter-spacing: -.02em; margin: 0 0 .6rem;
        }
        .seksi-sub { color: var(--redup); max-width: 640px; margin: 0 auto 2.75rem; }
        .kapsul {
            display: inline-flex; align-items: center; gap: .45rem;
            background: rgba(34,166,201,.12); color: #0d6f8a;
            border: 1px solid rgba(34,166,201,.3);
            font-size: .76rem; font-weight: 700; letter-spacing: .07em;
            text-transform: uppercase; padding: .35rem .85rem; border-radius: 2rem;
        }

        /* muncul saat discroll */
        .muncul { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s cubic-bezier(.2,.7,.2,1); }
        .muncul.tampak { opacity: 1; transform: none; }

        /* ================= FOOTER ================= */
        .footer-publik {
            background: var(--navy); color: rgba(255,255,255,.72);
            padding: 3rem 0 1.5rem;
        }
        .footer-publik a { color: rgba(255,255,255,.8); text-decoration: none; }
        .footer-publik a:hover { color: #fff; text-decoration: underline; }
        .footer-judul { color: #fff; font-weight: 700; font-size: .95rem; margin-bottom: .8rem; }
        .footer-garis {
            border-top: 1px solid rgba(255,255,255,.12);
            margin-top: 2.5rem; padding-top: 1.25rem;
            font-size: .82rem; color: rgba(255,255,255,.55);
        }

        @media (max-width: 768px) {
            .seksi { padding: 3rem 0; }
            .nav-toggle { display: block; }
            .nav-tautan {
                display: none; position: absolute; top: 100%; left: 0; right: 0;
                flex-direction: column; align-items: stretch; gap: .2rem;
                background: var(--navy); padding: .75rem 1.25rem 1.1rem;
                border-bottom: 1px solid rgba(255,255,255,.1);
            }
            .nav-tautan.buka { display: flex; }
            .btn-panel { text-align: center; margin-top: .35rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .muncul { opacity: 1 !important; transform: none !important; transition: none !important; }
            *, *::before, *::after { animation-duration: .001ms !important; }
        }
    </style>
    @stack('gaya')
</head>
<body>

<nav class="nav-publik" id="navPublik">
    <div class="isi">
        <a href="{{ route('publik.beranda') }}" class="nav-merek">
            <span class="kotak">PK</span>
            <span class="teks">{{ $situs->judul ?: config('app.name') }}</span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Buka menu"
                onclick="document.getElementById('navTautan').classList.toggle('buka')">
            <i class="bi bi-list"></i>
        </button>

        <div class="nav-tautan" id="navTautan">
            <a href="{{ route('publik.beranda') }}" class="{{ request()->routeIs('publik.beranda') ? 'aktif' : '' }}">Beranda</a>
            <a href="{{ route('publik.pengumuman') }}" class="{{ request()->routeIs('publik.pengumuman') ? 'aktif' : '' }}">Pengumuman</a>
            @auth
                <a href="{{ route('admin.statistik') }}" class="btn-panel">Buka Panel</a>
            @else
                <a href="{{ route('login') }}" class="btn-panel">Masuk Panitia</a>
            @endauth
        </div>
    </div>
</nav>

@yield('konten')

<footer class="footer-publik">
    <div class="bungkus">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="footer-judul">{{ $situs->judul }}</div>
                <p class="mb-0" style="font-size: .88rem; max-width: 380px;">
                    {{ $situs->penyelenggara }}
                </p>
            </div>
            <div class="col-6 col-lg-3">
                <div class="footer-judul">Halaman</div>
                <ul class="list-unstyled mb-0" style="font-size: .88rem; line-height: 2;">
                    <li><a href="{{ route('publik.beranda') }}">Beranda</a></li>
                    <li><a href="{{ route('publik.pengumuman') }}">Pengumuman</a></li>
                    <li><a href="{{ route('login') }}">Masuk Panitia</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-4">
                <div class="footer-judul">Kontak</div>
                <ul class="list-unstyled mb-0" style="font-size: .88rem; line-height: 2;">
                    @if ($situs->kontak_email)
                        <li><i class="bi bi-envelope me-2"></i><a href="mailto:{{ $situs->kontak_email }}">{{ $situs->kontak_email }}</a></li>
                    @endif
                    @if ($situs->kontak_wa)
                        <li><i class="bi bi-whatsapp me-2"></i>{{ $situs->kontak_wa }}</li>
                    @endif
                    @if ($situs->instagramUrl())
                        <li>
                            <i class="bi bi-instagram me-2"></i>
                            <a href="{{ $situs->instagramUrl() }}" target="_blank" rel="noopener">
                                {{ $situs->instagramLabel() }}
                            </a>
                        </li>
                    @endif
                    @if ($situs->situs_lembaga)
                        {{-- tampilkan domainnya saja, senada dengan email & IG yang juga menampilkan nilainya --}}
                        <li>
                            <i class="bi bi-globe2 me-2"></i>
                            <a href="{{ $situs->situs_lembaga }}" target="_blank" rel="noopener">
                                {{ parse_url($situs->situs_lembaga, PHP_URL_HOST) ?: $situs->situs_lembaga }}
                            </a>
                        </li>
                    @endif
                    @unless ($situs->kontak_email || $situs->kontak_wa || $situs->instagram || $situs->situs_lembaga)
                        <li class="text-white-50">Belum diisi panitia.</li>
                    @endunless
                </ul>
            </div>
        </div>
        <div class="footer-garis d-flex flex-wrap gap-2 justify-content-between">
            <span>&copy; {{ date('Y') }} {{ $situs->penyelenggara ?: config('app.name') }}</span>
            <span>Penilaian dilakukan lewat sistem internal panitia.</span>
        </div>
    </div>
</footer>

<script>
    // navbar dapat bayangan setelah discroll sedikit
    const nav = document.getElementById('navPublik');
    addEventListener('scroll', () => nav.classList.toggle('menempel', scrollY > 8), { passive: true });

    // animasi elemen saat masuk layar
    const pengamat = new IntersectionObserver((baris) => {
        baris.forEach((b) => {
            if (b.isIntersecting) {
                b.target.classList.add('tampak');
                pengamat.unobserve(b.target);
            }
        });
    }, { threshold: .12, rootMargin: '0px 0px -40px' });

    document.querySelectorAll('.muncul').forEach((el, i) => {
        el.style.transitionDelay = Math.min(i * 70, 420) + 'ms';
        pengamat.observe(el);
    });
</script>
@stack('skrip')
</body>
</html>
