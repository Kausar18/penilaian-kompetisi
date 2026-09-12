@extends('layouts.publik')

@section('judul', 'Pengumuman')

@push('gaya')
<style>
    .kepala-halaman {
        position: relative; overflow: hidden; color: #fff; padding: 3.5rem 0 3rem;
        background:
            radial-gradient(900px 420px at 12% -20%, #1B4FA8 0%, transparent 58%),
            linear-gradient(135deg, #0B2545 0%, #123a72 55%, #0E3B86 100%);
    }
    .kepala-halaman::after {
        content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .4;
        background-image: radial-gradient(rgba(255,255,255,.16) 1px, transparent 1.4px);
        background-size: 26px 26px;
        mask-image: radial-gradient(circle at 50% 30%, #000 0%, transparent 76%);
    }
    .kepala-halaman .bungkus { position: relative; z-index: 1; }
    .kepala-halaman h1 {
        font-weight: 800; letter-spacing: -.03em; margin: .9rem 0 .5rem;
        font-size: clamp(1.8rem, 4.4vw, 2.6rem);
    }
    .kepala-halaman p { color: rgba(255,255,255,.75); margin: 0; max-width: 60ch; }

    /* ---- tab tahap ---- */
    .tab-tahap { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.75rem; }
    .tab-tahap a, .tab-tahap span {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .55rem 1.1rem; border-radius: 2rem; text-decoration: none;
        font-size: .9rem; font-weight: 600;
        border: 1px solid rgba(255,255,255,.25); color: rgba(255,255,255,.85);
        background: rgba(255,255,255,.08); transition: background .15s ease;
    }
    .tab-tahap a:hover { background: rgba(255,255,255,.18); color: #fff; }
    .tab-tahap .aktif {
        background: linear-gradient(135deg, #22A6C9, #12459B); color: #fff;
        border-color: transparent; box-shadow: 0 10px 22px -12px rgba(34,166,201,.9);
    }
    .tab-tahap .lencana {
        background: rgba(255,255,255,.2); border-radius: 1rem;
        padding: 0 .5rem; font-size: .78rem;
    }

    /* ---- panel isi ---- */
    .panel-isi { margin-top: -1.75rem; position: relative; z-index: 2; }
    .kotak-putih {
        background: #fff; border: 1px solid var(--garis); border-radius: 1rem;
        box-shadow: 0 22px 50px -34px rgba(4,14,35,.5);
    }
    .bar-saring {
        padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--garis);
        display: flex; flex-wrap: wrap; gap: .6rem; align-items: center;
    }
    .bar-saring .form-control, .bar-saring .form-select {
        border-color: var(--garis); font-size: .9rem;
    }
    .bar-saring .form-control:focus, .bar-saring .form-select:focus {
        border-color: var(--cyan); box-shadow: 0 0 0 .2rem rgba(34,166,201,.15);
    }

    .kartu-tim {
        border: 1px solid var(--garis); border-radius: .8rem; padding: 1.1rem 1.15rem;
        height: 100%; background: #fff; position: relative;
        transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    .kartu-tim:hover {
        transform: translateY(-3px); border-color: rgba(34,166,201,.55);
        box-shadow: 0 18px 38px -26px rgba(4,14,35,.45);
    }
    .kartu-tim .centang {
        position: absolute; top: 1rem; right: 1rem;
        color: #16a34a; font-size: 1.05rem;
    }
    .kartu-tim .nama { font-weight: 700; font-size: 1rem; letter-spacing: -.01em; padding-right: 1.75rem; }
    .kartu-tim .judul {
        color: var(--redup); font-size: .87rem; margin-top: .3rem; line-height: 1.5;
    }
    .kartu-tim .meta {
        display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .8rem;
    }
    .cip {
        font-size: .74rem; font-weight: 600; padding: .2rem .6rem;
        border-radius: 1rem; background: var(--latar); color: #475569;
        border: 1px solid var(--garis);
    }
    .cip.bidang { background: rgba(34,166,201,.1); color: #0d6f8a; border-color: rgba(34,166,201,.28); }

    .kosong { text-align: center; padding: 3.5rem 1.5rem; }
    .kosong i {
        font-size: 1.8rem; color: var(--cyan);
        display: inline-grid; place-items: center;
        width: 64px; height: 64px; border-radius: 50%;
        background: rgba(34,166,201,.12); margin-bottom: 1rem;
    }
    .kosong h3 { font-weight: 700; font-size: 1.15rem; margin-bottom: .5rem; }
    .kosong p { color: var(--redup); max-width: 46ch; margin: 0 auto; font-size: .93rem; }

    /* ---- halaman "belum diumumkan" ---- */
    /* Sengaja TIDAK memakai merah seperti halaman "ditutup": pada halaman hasil
       seleksi, merah gampang disalahartikan peserta sebagai "saya tidak lolos". */
    .belum-tayang { text-align: center; padding: 4rem 1.5rem 4.25rem; }
    .belum-tayang .ikon-bulat {
        display: inline-grid; place-items: center;
        width: 78px; height: 78px; border-radius: 50%; margin-bottom: 1.5rem;
        background: linear-gradient(135deg, var(--cyan), var(--biru));
        color: #fff; font-size: 2rem;
        box-shadow: 0 16px 34px -14px rgba(34,166,201,.85);
    }
    .belum-tayang h2 {
        font-weight: 800; letter-spacing: -.02em; color: var(--navy);
        font-size: clamp(1.35rem, 3vw, 1.75rem); margin: 0 0 .85rem;
    }
    .belum-tayang p {
        color: var(--redup); font-size: .95rem; line-height: 1.7;
        max-width: 48ch; margin: 0 auto 2rem;
    }
    .btn-kembali {
        display: inline-flex; align-items: center; gap: .5rem;
        background: linear-gradient(135deg, var(--biru), var(--navy));
        color: #fff; text-decoration: none; font-weight: 600; font-size: .93rem;
        padding: .75rem 1.6rem; border-radius: .6rem;
        box-shadow: 0 14px 28px -14px rgba(18,69,155,.9);
        transition: transform .12s ease, filter .15s ease;
    }
    .btn-kembali:hover { color: #fff; filter: brightness(1.1); transform: translateY(-1px); }
    .btn-kembali:active { transform: translateY(0); }

    .catatan-panitia {
        background: rgba(34,166,201,.07); border: 1px solid rgba(34,166,201,.25);
        border-radius: .7rem; padding: .95rem 1.1rem; font-size: .9rem;
        color: #0f5f76; margin: 1.25rem 1.25rem 0;
    }
</style>
@endpush

@section('konten')

<header class="kepala-halaman">
    <div class="bungkus">
        <span class="kapsul" style="background: rgba(255,255,255,.12); color:#fff; border-color: rgba(255,255,255,.25);">
            <i class="bi bi-megaphone"></i> Pengumuman
        </span>
        <h1>Hasil Seleksi</h1>
        <p>
            Halaman ini memuat daftar peserta yang <strong>lolos</strong> pada setiap tahap.
            Nilai dan catatan juri bersifat internal dan tidak ditampilkan.
        </p>

        {{-- Kedua tahap selalu bisa dibuka. Yang belum diumumkan tidak jadi tombol
             mati — halamannya berisi keterangan, dan jumlahnya disembunyikan. --}}
        <div class="tab-tahap">
            <a href="{{ route('publik.pengumuman', ['tahap' => 'administrasi']) }}"
               class="{{ $tab === 'administrasi' ? 'aktif' : '' }}">
                <i class="bi bi-clipboard-check"></i> Lolos Administrasi
                @if ($situs->umumkan_administrasi)
                    <span class="lencana">{{ $jumlah['administrasi'] }}</span>
                @endif
            </a>

            <a href="{{ route('publik.pengumuman', ['tahap' => 'finalis']) }}"
               class="{{ $tab === 'finalis' ? 'aktif' : '' }}">
                <i class="bi bi-trophy"></i> Finalis
                @if ($situs->umumkan_finalis)
                    <span class="lencana">{{ $jumlah['finalis'] }}</span>
                @endif
            </a>
        </div>
    </div>
</header>

<div class="bungkus panel-isi">
    <div class="kotak-putih">

        @if ($situs->catatan_pengumuman)
            <div class="catatan-panitia">
                <i class="bi bi-info-circle me-1"></i> {{ $situs->catatan_pengumuman }}
            </div>
        @endif

        @if (! $diumumkan)
            {{-- Tahap ini belum diumumkan panitia. Sengaja TIDAK memuat tanggal
                 apa pun supaya panitia tidak terikat janji waktu. --}}
            <div class="belum-tayang">
                <span class="ikon-bulat"><i class="bi bi-hourglass-split"></i></span>

                <h2>Pengumuman Belum Tersedia</h2>

                <p>
                    @if ($tab === 'finalis')
                        Hasil penilaian <strong>Pitching Battle</strong> belum diumumkan.
                        Daftar finalis akan ditayangkan di halaman ini setelah seluruh
                        rangkaian penilaian selesai.
                    @else
                        Hasil <strong>verifikasi administrasi</strong> belum diumumkan.
                        Daftar peserta yang lolos akan ditayangkan di halaman ini setelah
                        proses verifikasi selesai.
                    @endif
                </p>

                <a href="{{ route('publik.beranda') }}" class="btn-kembali">Kembali ke Beranda</a>
            </div>
        @else
            <form method="GET" class="bar-saring">
                <input type="hidden" name="tahap" value="{{ $tab }}">

                <div class="flex-grow-1" style="min-width: 220px;">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control"
                           placeholder="Cari nama tim atau judul inovasi...">
                </div>

                <div style="min-width: 190px;">
                    <select name="bidang" class="form-select">
                        <option value="">Semua bidang</option>
                        @foreach ($bidang as $b)
                            <option value="{{ $b->id }}" @selected(request('bidang') == $b->id)>{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-dark btn-sm px-3" style="background: var(--navy); border: none; padding-block: .5rem;">
                    <i class="bi bi-search me-1"></i> Cari
                </button>

                @if (request()->hasAny(['q', 'bidang']))
                    <a href="{{ route('publik.pengumuman', ['tahap' => $tab]) }}" class="btn btn-link btn-sm text-secondary">
                        Reset
                    </a>
                @endif
            </form>

            @if ($daftar->isEmpty())
                <div class="kosong">
                    <i class="bi bi-search"></i>
                    <h3>Tidak ada hasil</h3>
                    <p>Tidak ada peserta yang cocok dengan pencarian tersebut. Coba kata kunci lain atau reset filter.</p>
                </div>
            @else
                <div class="p-3 p-md-4">
                    <p class="text-secondary mb-3" style="font-size: .88rem;">
                        Menampilkan <strong>{{ $daftar->firstItem() }}</strong>–<strong>{{ $daftar->lastItem() }}</strong>
                        dari <strong>{{ $daftar->total() }}</strong> peserta yang lolos
                        {{ $tab === 'finalis' ? 'ke babak final' : 'verifikasi administrasi' }}.
                    </p>

                    <div class="row g-3">
                        @foreach ($daftar as $p)
                            <div class="col-sm-6 col-lg-4 muncul">
                                <article class="kartu-tim">
                                    <i class="bi bi-patch-check-fill centang"></i>
                                    <div class="nama">{{ $p->nama_tim }}</div>

                                    @if ($p->judul_inovasi)
                                        <div class="judul">{{ Str::limit($p->judul_inovasi, 110) }}</div>
                                    @endif

                                    <div class="meta">
                                        @if ($p->bidangKompetisi)
                                            <span class="cip bidang">{{ $p->bidangKompetisi->nama }}</span>
                                        @endif
                                        @if ($p->kategoriPeserta)
                                            <span class="cip">{{ $p->kategoriPeserta->nama }}</span>
                                        @endif
                                        @if ($p->kota || $p->provinsi)
                                            <span class="cip">
                                                <i class="bi bi-geo-alt me-1"></i>{{ collect([$p->kota, $p->provinsi])->filter()->join(', ') }}
                                            </span>
                                        @endif
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    @if ($daftar->hasPages())
                        <div class="mt-4">{{ $daftar->links() }}</div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <p class="text-center text-secondary mt-4 mb-0" style="font-size: .85rem;">
        Ada pertanyaan mengenai hasil seleksi? Hubungi panitia lewat kontak di bawah.
    </p>
</div>

<div style="height: 3rem;"></div>

@endsection
