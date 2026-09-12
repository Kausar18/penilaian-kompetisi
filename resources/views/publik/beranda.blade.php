@extends('layouts.publik')

@section('judul', 'Beranda')

@push('gaya')
<style>
    /* ================= HERO ================= */
    .hero {
        position: relative; overflow: hidden; color: #fff;
        padding: 5.5rem 0 6rem;
        background:
            radial-gradient(1100px 560px at 10% -10%, #1B4FA8 0%, transparent 55%),
            radial-gradient(900px 620px at 108% 15%, #22A6C9 0%, transparent 52%),
            linear-gradient(135deg, #0B2545 0%, #123a72 42%, #0E3B86 62%, #0B2545 100%);
        background-size: 100% 100%, 100% 100%, 200% 200%;
        animation: geser-gradien 18s ease-in-out infinite;
    }
    @keyframes geser-gradien {
        0%, 100% { background-position: 0% 0%, 100% 20%, 0% 50%; }
        50%      { background-position: 0% 0%, 100% 20%, 100% 50%; }
    }
    .hero-lapis { position: absolute; inset: 0; pointer-events: none; }
    .hero-dots {
        opacity: .45;
        background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1.4px);
        background-size: 26px 26px;
        mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 72%);
        animation: geser-dots 30s linear infinite;
    }
    @keyframes geser-dots { to { background-position: 260px 260px; } }
    .hero-grid {
        opacity: .28;
        background-image:
            linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(circle at 50% 40%, #000 0%, transparent 78%);
    }
    .hero-noise {
        opacity: .4; mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }
    .hero .bungkus { position: relative; z-index: 1; }
    .hero-kapsul {
        display: inline-flex; align-items: center; gap: .5rem;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
        color: #fff; font-size: .78rem; font-weight: 600; letter-spacing: .05em;
        padding: .4rem .95rem; border-radius: 2rem; backdrop-filter: blur(6px);
    }
    .hero-kapsul i { color: #7ee0f5; animation: denyut 2.4s ease-in-out infinite; }
    @keyframes denyut { 50% { opacity: .5; transform: scale(.85); } }
    .hero h1 {
        font-weight: 900; letter-spacing: -.035em; line-height: 1.08;
        font-size: clamp(2.1rem, 5.6vw, 3.6rem); margin: 1.25rem 0 0; max-width: 15ch;
    }
    .hero .sorot {
        background: linear-gradient(120deg, #7ee0f5, #22A6C9);
        -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    .hero p.utama {
        color: rgba(255,255,255,.78); font-size: 1.05rem;
        max-width: 54ch; margin: 1.15rem 0 0; line-height: 1.65;
    }
    .hero-aksi { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
    .btn-hero {
        display: inline-flex; align-items: center; gap: .55rem;
        padding: .8rem 1.5rem; border-radius: .65rem;
        font-weight: 600; text-decoration: none; border: 1px solid transparent;
        transition: transform .12s ease, filter .15s ease, background .15s ease;
    }
    .btn-hero:active { transform: translateY(1px); }
    .btn-hero.isi {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #22A6C9, #12459B); color: #fff;
        box-shadow: 0 14px 30px -14px rgba(34,166,201,.95);
    }
    .btn-hero.isi:hover { filter: brightness(1.1); }
    .btn-hero.isi::after {
        content: ""; position: absolute; top: 0; left: -120%;
        width: 55%; height: 100%; transform: skewX(-22deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
        animation: kilau 3.6s ease-in-out infinite;
    }
    @keyframes kilau { 0%, 60% { left: -120%; } 100% { left: 160%; } }
    .btn-hero.garis {
        background: rgba(255,255,255,.08); color: #fff;
        border-color: rgba(255,255,255,.3); backdrop-filter: blur(6px);
    }
    .btn-hero.garis:hover { background: rgba(255,255,255,.16); }

    /* ================= KARTU ANGKA ================= */
    .pita-angka { margin-top: -3.25rem; position: relative; z-index: 2; }
    .kartu-angka {
        background: #fff; border: 1px solid var(--garis); border-radius: .9rem;
        padding: 1.4rem 1.25rem; height: 100%; text-align: center;
        box-shadow: 0 18px 40px -28px rgba(4,14,35,.45);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .kartu-angka:hover { transform: translateY(-4px); box-shadow: 0 24px 48px -26px rgba(4,14,35,.5); }
    .kartu-angka i {
        font-size: 1.35rem; color: var(--cyan);
        display: inline-grid; place-items: center;
        width: 44px; height: 44px; border-radius: .7rem;
        background: rgba(34,166,201,.12); margin-bottom: .7rem;
    }
    .kartu-angka .statistik-angka {
        font-size: 1.95rem; font-weight: 800; letter-spacing: -.02em;
        color: var(--navy); line-height: 1.1;
    }
    .kartu-angka .label { color: var(--redup); font-size: .85rem; margin-top: .2rem; }

    /* ================= ALUR ================= */
    .alur { position: relative; }
    .kartu-alur {
        background: #fff; border: 1px solid var(--garis); border-radius: .9rem;
        padding: 1.75rem 1.5rem; height: 100%; position: relative; overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .kartu-alur:hover {
        transform: translateY(-4px); border-color: rgba(34,166,201,.5);
        box-shadow: 0 22px 44px -28px rgba(4,14,35,.5);
    }
    .kartu-alur .nomor {
        position: absolute; right: 1rem; top: .5rem;
        font-size: 3.4rem; font-weight: 900; color: rgba(11,37,69,.05); line-height: 1;
    }
    .kartu-alur .tahap {
        font-size: .72rem; font-weight: 700; letter-spacing: .09em;
        text-transform: uppercase; color: var(--cyan);
    }
    .kartu-alur h3 { font-size: 1.08rem; font-weight: 700; margin: .5rem 0 .55rem; }
    .kartu-alur p { color: var(--redup); font-size: .9rem; margin: 0; line-height: 1.6; }

    /* ================= BIDANG ================= */
    .kartu-bidang {
        display: flex; align-items: center; gap: .85rem;
        background: #fff; border: 1px solid var(--garis); border-radius: .8rem;
        padding: 1.05rem 1.15rem; height: 100%;
        transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    .kartu-bidang:hover {
        transform: translateY(-3px); border-color: rgba(34,166,201,.55);
        box-shadow: 0 18px 36px -26px rgba(4,14,35,.45);
    }
    .kartu-bidang .titik {
        width: 10px; height: 10px; border-radius: 50%; flex: none;
        background: linear-gradient(135deg, var(--cyan), var(--biru));
    }
    .kartu-bidang span { font-weight: 600; font-size: .93rem; }

    /* ================= TIMELINE ================= */
    /* Kolom bulatan dibuat eksplisit (20px) lalu garis ditaruh tepat di tengahnya
       (left 9px + lebar 2px = pusat 10px). Dulu posisi bulatan & garis dihitung
       terpisah sehingga meleset ~5px dan garisnya menembus tepi lingkaran. */
    .kartu-jadwal {
        background: #fff; border: 1px solid var(--garis); border-radius: .9rem;
        padding: 1.75rem 1.65rem;
        box-shadow: 0 18px 40px -30px rgba(4,14,35,.4);
    }
    .titik-waktu {
        display: grid; grid-template-columns: 20px 1fr;
        /* column-gap & row-gap dipisah: `gap` tunggal ikut merenggangkan jarak
           label ke tanggal di layar sempit sehingga tanggal terbaca seolah
           milik tahap berikutnya */
        column-gap: .95rem; row-gap: 0;
        align-items: start; position: relative; padding-bottom: 1.5rem;
    }
    .titik-waktu:last-child { padding-bottom: 0; }

    /* ruas garis penghubung ke tahap berikutnya */
    .titik-waktu::after {
        content: ""; position: absolute; left: 9px; top: 24px; bottom: 0;
        width: 2px; background: var(--garis);
    }
    .titik-waktu:last-child::after { display: none; }
    .titik-waktu.selesai::after { background: rgba(34,166,201,.55); }

    .bulatan {
        grid-column: 1; width: 20px; height: 20px; border-radius: 50%;
        background: #fff; border: 2px solid var(--garis);
        display: grid; place-items: center; margin-top: 1px;
        color: #fff; font-size: .68rem; line-height: 1;
        transition: transform .16s ease;
    }
    .titik-waktu.selesai .bulatan { background: var(--cyan); border-color: var(--cyan); }
    .titik-waktu.berjalan .bulatan {
        border-color: var(--cyan); border-width: 3px;
        box-shadow: 0 0 0 4px rgba(34,166,201,.18);
    }

    .titik-waktu .tahap {
        grid-column: 2; min-width: 0;
        font-weight: 650; font-size: .95rem; line-height: 1.35;
    }
    .titik-waktu.akan .tahap { color: var(--redup); font-weight: 550; }
    .titik-waktu.berjalan .tahap { color: #0d6f8a; }
    .titik-waktu .tanggal { grid-column: 2; color: var(--redup); font-size: .84rem; margin-top: .15rem; }

    /* layar lebar: tanggal pindah ke kolom kanan supaya terbaca seperti jadwal
       dan lebar kartu tidak menyisakan ruang kosong */
    @media (min-width: 992px) {
        .titik-waktu { grid-template-columns: 20px 1fr auto; column-gap: 1rem; }
        .titik-waktu .tanggal {
            grid-column: 3; grid-row: 1; margin-top: 0;
            text-align: right; white-space: nowrap;
        }
    }
    .titik-waktu .kini {
        display: inline-block; margin-left: .5rem; vertical-align: 1px;
        font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: #0d6f8a; background: rgba(34,166,201,.13);
        border-radius: 1rem; padding: .1rem .5rem;
    }

    /* ================= CTA ================= */
    .cta {
        position: relative; overflow: hidden; color: #fff; border-radius: 1.1rem;
        padding: 3rem 2rem; text-align: center;
        background: linear-gradient(135deg, #12459B 0%, #0B2545 75%);
        box-shadow: 0 30px 60px -32px rgba(4,14,35,.65);
    }
    .cta::after {
        content: ""; position: absolute; right: -80px; top: -80px;
        width: 260px; height: 260px; border-radius: 50%;
        background: radial-gradient(circle, rgba(34,166,201,.5), transparent 70%);
    }
    .cta::before {
        content: ""; position: absolute; left: -70px; bottom: -90px;
        width: 230px; height: 230px; border-radius: 50%;
        background: radial-gradient(circle, rgba(62,123,217,.42), transparent 70%);
    }
    .cta > * { position: relative; z-index: 1; }

    .seksi-terang { background: #fff; }
</style>
@endpush

@section('konten')

{{-- ==================== HERO ==================== --}}
<header class="hero">
    <div class="hero-lapis hero-grid"></div>
    <div class="hero-lapis hero-dots"></div>
    <div class="hero-lapis hero-noise"></div>

    <div class="bungkus">
        @if ($situs->penyelenggara)
            <span class="hero-kapsul"><i class="bi bi-patch-check-fill"></i>{{ $situs->penyelenggara }}</span>
        @endif

        @php
            // sorot kata terakhir judul supaya ada aksen warna, tanpa menghardcode teks apa pun
            $kata = preg_split('/\s+/', trim($situs->judul), -1, PREG_SPLIT_NO_EMPTY) ?: [''];
            $akhir = array_pop($kata);
        @endphp
        <h1>{{ implode(' ', $kata) }} <span class="sorot">{{ $akhir }}</span></h1>

        @if ($situs->subjudul)
            <p class="utama">{{ $situs->subjudul }}</p>
        @endif

        <div class="hero-aksi">
            <a href="{{ route('publik.pengumuman') }}" class="btn-hero isi">
                <i class="bi bi-megaphone"></i> Lihat Pengumuman
            </a>
            <a href="#alur" class="btn-hero garis">
                <i class="bi bi-signpost-split"></i> Alur Seleksi
            </a>
        </div>
    </div>
</header>

{{-- ==================== ANGKA RINGKAS ==================== --}}
@if (count($angka))
    <div class="bungkus pita-angka">
        <div class="row g-3 row-cols-2 row-cols-lg-{{ min(count($angka), 4) }}">
            @foreach ($angka as $a)
                <div class="col muncul">
                    <div class="kartu-angka">
                        <i class="bi {{ $a['ikon'] }}"></i>
                        <div class="statistik-angka">{{ number_format($a['nilai'], 0, ',', '.') }}</div>
                        <div class="label">{{ $a['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ==================== TENTANG + ALUR ==================== --}}
<section class="seksi alur" id="alur">
    <div class="bungkus text-center">
        <span class="kapsul"><i class="bi bi-signpost-split"></i> Alur Seleksi</span>
        <h2 class="seksi-judul mt-3">Dua tahap penilaian</h2>
        <p class="seksi-sub">
            {{ $situs->deskripsi }}
        </p>

        <div class="row g-3 text-start">
            @php
                $langkah = [
                    ['1', 'Tahap 1', 'Verifikasi Administrasi',
                     'Berkas proposal diperiksa terhadap seluruh butir persyaratan Juklak — kelengkapan dokumen, kriteria startup, serta kesesuaian isi dan template proposal.'],
                    ['2', 'Tahap 2', 'Penilaian Substansi (Pitching Battle)',
                     'Peserta yang lolos administrasi mempresentasikan usahanya. Penilaian memakai rubrik berbobot: problem & solution, prototipe, inovasi, model bisnis & dampak, dan public speaking.'],
                    ['3', 'Hasil', 'Penetapan Peserta Terpilih',
                     'Nilai seluruh juri direkap menjadi peringkat akhir. Peserta terpilih diumumkan di halaman ini dan berhak mengikuti program pembinaan.'],
                ];
            @endphp

            @foreach ($langkah as [$no, $tahap, $judul, $isi])
                <div class="col-md-4 muncul">
                    <article class="kartu-alur">
                        <span class="nomor">{{ $no }}</span>
                        <div class="tahap">{{ $tahap }}</div>
                        <h3>{{ $judul }}</h3>
                        <p>{{ $isi }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== BIDANG FOKUS ==================== --}}
@if ($bidang->isNotEmpty())
    <section class="seksi seksi-terang">
        <div class="bungkus text-center">
            <span class="kapsul"><i class="bi bi-grid"></i> Bidang Fokus</span>
            <h2 class="seksi-judul mt-3">Bidang usaha yang dikompetisikan</h2>
            <p class="seksi-sub">Startup yang mendaftar dikelompokkan ke dalam bidang berikut.</p>

            <div class="row g-3 text-start">
                @foreach ($bidang as $b)
                    <div class="col-sm-6 col-lg-4 muncul">
                        <div class="kartu-bidang">
                            <span class="titik"></span>
                            <span>{{ $b->nama }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ==================== TIMELINE ==================== --}}
@if (count($timeline))
    <section class="seksi">
        <div class="bungkus">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <span class="kapsul"><i class="bi bi-calendar3"></i> Jadwal</span>
                    <h2 class="seksi-judul mt-3">Tahapan kegiatan</h2>
                    <p class="text-secondary mb-0" style="font-size: .95rem;">
                        Jadwal dapat berubah sewaktu-waktu. Perubahan diumumkan lewat halaman
                        <a href="{{ route('publik.pengumuman') }}">Pengumuman</a>.
                    </p>
                </div>
                <div class="col-lg-7">
                    @php
                        // tahap berjalan = tahap pertama yang belum ditandai selesai
                        $iBerjalan = collect($timeline)->search(fn ($t) => ! $t['selesai']);
                    @endphp

                    <div class="kartu-jadwal">
                        @foreach ($timeline as $i => $t)
                            @php
                                $status = $t['selesai'] ? 'selesai' : ($i === $iBerjalan ? 'berjalan' : 'akan');
                            @endphp

                            <div class="titik-waktu {{ $status }} muncul">
                                <span class="bulatan">
                                    @if ($status === 'selesai')
                                        <i class="bi bi-check-lg"></i>
                                    @endif
                                </span>
                                <div class="tahap">
                                    {{ $t['tahap'] }}
                                    @if ($status === 'berjalan')
                                        <span class="kini">Sedang berjalan</span>
                                    @endif
                                </div>

                                {{-- tanggal hanya tayang kalau panitia menyalakannya di menu Halaman Publik --}}
                                @if ($situs->tampilkan_tanggal_jadwal && $t['tanggal'])
                                    <div class="tanggal">{{ $t['tanggal'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ==================== CTA ==================== --}}
<section class="seksi pt-0">
    <div class="bungkus">
        <div class="cta muncul">
            <h2 class="fw-bold mb-2" style="letter-spacing: -.02em;">
                @if ($situs->adaPengumuman())
                    Hasil seleksi sudah tersedia
                @else
                    Pengumuman hasil seleksi
                @endif
            </h2>
            <p class="mb-4" style="color: rgba(255,255,255,.75); max-width: 52ch; margin-inline: auto;">
                @if ($situs->adaPengumuman())
                    Daftar peserta yang lolos dapat dilihat pada halaman pengumuman.
                @else
                    Hasil seleksi akan ditayangkan di halaman pengumuman setelah proses penilaian selesai.
                @endif
            </p>
            <a href="{{ route('publik.pengumuman') }}" class="btn-hero isi">
                <i class="bi bi-megaphone"></i> Buka Halaman Pengumuman
            </a>
        </div>
    </div>
</section>

@endsection

@push('skrip')
    @include('partials.animasi-angka')
@endpush
