@extends('layouts.admin')

@section('judul', 'Rubrik Penilaian')
@section('subjudul', 'Kelompok & indikator penilaian berbobot — bisa diubah kapan saja')

@section('aksi')
    <button type="button" class="btn btn-sm btn-utama" data-bs-toggle="collapse" data-bs-target="#addKat">
        <i class="bi bi-plus-lg me-1"></i> Kelompok baru
    </button>
@endsection

@push('gaya')
<style>
    .rubrik-ringkas { display: flex; flex-wrap: wrap; gap: 1.5rem 2.5rem; align-items: center; }
    .rubrik-ringkas .angka { font-size: 1.5rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .rubrik-ringkas .ket { font-size: .72rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--redup); margin-bottom: .25rem; }
    .bobot-meter { flex: 1; min-width: 220px; }
    .bobot-bar { height: 9px; border-radius: 999px; background: #EEF1F6; overflow: hidden; }
    .bobot-bar > span { display: block; height: 100%; border-radius: 999px; transition: width .4s ease; }
    .bobot-ok  > span { background: linear-gradient(90deg, var(--teal), var(--hijau)); }
    .bobot-off > span { background: linear-gradient(90deg, var(--amber), var(--merah)); }

    .kelompok-head { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem 1rem; }
    .kelompok-kode {
        display: inline-grid; place-items: center; min-width: 46px; height: 34px; padding: 0 .55rem;
        border-radius: .55rem; background: linear-gradient(135deg, var(--biru), var(--navy));
        color: #fff; font-weight: 800; font-size: .8rem; letter-spacing: .03em;
    }
    .kelompok-nama { font-size: 1.05rem; font-weight: 800; color: var(--navy); margin: 0; }
    .kelompok-meta { font-size: .78rem; color: var(--redup); }
    .badge-bobot {
        font-size: .8rem; font-weight: 700; color: var(--biru);
        background: var(--biru-muda); border-radius: 999px; padding: .2rem .7rem;
    }
    .btn-ikon {
        border: 1px solid var(--garis); background: #fff; color: var(--redup);
        width: 32px; height: 32px; border-radius: .5rem; display: inline-grid; place-items: center;
        transition: all .14s ease;
    }
    .btn-ikon:hover { color: var(--merah); border-color: #F1C7C2; background: var(--merah-muda); }

    .strip-edit {
        background: #F8FAFD; border: 1px solid var(--garis); border-radius: .6rem;
        padding: .8rem .9rem; margin-top: .9rem;
    }

    /* --- tabel indikator (grid biar kolom rata) --- */
    .ind-head, .ind-row {
        display: grid;
        grid-template-columns: minmax(170px, 1.5fr) minmax(210px, 2fr) 108px max-content max-content;
        gap: .6rem;
        align-items: start;
    }
    .ind-head {
        font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
        color: var(--redup); padding: .2rem .1rem .55rem; border-bottom: 1px solid var(--garis);
    }
    .ind-row {
        padding: .75rem .1rem;
        border-bottom: 1px solid var(--garis);
    }
    .ind-row:hover { background: #FBFCFE; }
    .ind-form { display: contents; }
    .ind-c-bobot label { font-size: .74rem; color: var(--redup); }

    .ind-tambah {
        display: grid;
        grid-template-columns: minmax(170px, 1.5fr) minmax(210px, 2fr) 108px max-content;
        gap: .6rem; align-items: start;
        margin-top: .9rem; padding: .85rem .9rem;
        border: 1px dashed var(--garis); border-radius: .6rem; background: #FCFDFF;
    }

    @media (max-width: 860px) {
        .ind-head { display: none; }
        .ind-row, .ind-tambah { grid-template-columns: 1fr; gap: .5rem; }
        .ind-row > *, .ind-form > * { width: 100%; }
    }
</style>
@endpush

@section('konten')

@php $bobotCocok = abs($totalBobot - 100) < 0.01; @endphp

{{-- ============ RINGKASAN BOBOT ============ --}}
<div class="panel p-4 mb-4">
    <div class="rubrik-ringkas">
        <div>
            <div class="ket">Bobot indikator aktif</div>
            <div class="angka {{ $bobotCocok ? '' : 'text-danger' }}">
                {{ rtrim(rtrim(number_format($totalBobot, 2), '0'), '.') }}<span class="fs-6 fw-normal" style="color:var(--redup);"> / 100</span>
            </div>
        </div>
        <div>
            <div class="ket">Total persen kelompok</div>
            <div class="angka">{{ $totalPersen }}<span class="fs-6 fw-normal" style="color:var(--redup);">%</span></div>
        </div>
        <div>
            <div class="ket">Kelompok</div>
            <div class="angka">{{ $kategori->count() }}</div>
        </div>
        <div class="bobot-meter">
            <div class="d-flex justify-content-between small mb-1" style="color: var(--redup);">
                <span>{{ $bobotCocok ? 'Bobot sudah pas' : 'Belum berskala 100' }}</span>
                <span>{{ min(100, (int) round($totalBobot)) }}%</span>
            </div>
            <div class="bobot-bar {{ $bobotCocok ? 'bobot-ok' : 'bobot-off' }}">
                <span style="width: {{ min(100, max(0, $totalBobot)) }}%;"></span>
            </div>
            @unless ($bobotCocok)
                <div class="small mt-1 text-danger">Sesuaikan bobot indikator agar totalnya 100.</div>
            @endunless
        </div>
    </div>
</div>

{{-- ============ PENGATURAN PENILAIAN ============ --}}
<div class="panel p-4 mb-4">
    <div class="kartu-judul"><i class="bi bi-sliders2"></i> Pengaturan Penilaian</div>
    <form method="POST" action="{{ route('admin.rubrik.pengaturan') }}" class="row g-3 align-items-end">
        @csrf @method('PUT')

        <div class="col-lg-4">
            <label class="label-filter d-block" for="preset">Skala nilai</label>
            <select class="form-select form-select-sm" id="preset" name="preset">
                @foreach (\App\Models\PengaturanPenilaian::PRESET as $kunci => $def)
                    <option value="{{ $kunci }}" @selected($pengaturan->presetSaatIni() === $kunci)>{{ $def['label'] }}</option>
                @endforeach
            </select>
            <div class="small mt-1" style="color: var(--redup);">
                Sekarang:
                @foreach ($pengaturan->skala as $tingkat)
                    <span class="tag tag-netral">{{ $tingkat['nilai'] }} = {{ $tingkat['label'] }}</span>
                @endforeach
            </div>
        </div>

        <div class="col-lg-5">
            <label class="label-filter d-block" for="rumus">Rumus nilai akhir</label>
            <select class="form-select form-select-sm" id="rumus" name="rumus">
                @foreach (\App\Models\PengaturanPenilaian::RUMUS as $kunci => $label)
                    <option value="{{ $kunci }}" @selected($pengaturan->rumus === $kunci)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="small mt-1" style="color: var(--redup);">
                Nilai akhir tertinggi:
                <strong>{{ rtrim(rtrim(number_format($pengaturan->nilaiMaksimum($totalBobot), 2, '.', ''), '0'), '.') }}</strong>
            </div>
        </div>

        <div class="col-lg-3">
            <label class="d-flex align-items-center gap-2 small mb-2">
                <input type="checkbox" name="hanya_terverifikasi" value="1" @checked($pengaturan->hanya_terverifikasi)>
                Hanya nilai peserta Terverifikasi
            </label>
            <button class="btn btn-utama btn-sm w-100">Simpan Pengaturan</button>
        </div>
    </form>
</div>

{{-- ============ FORM TAMBAH KELOMPOK (tersembunyi) ============ --}}
<div class="collapse mb-4" id="addKat">
    <div class="panel p-4">
        <div class="kartu-judul"><i class="bi bi-folder-plus"></i> Tambah kelompok penilaian</div>
        <form method="POST" action="{{ route('admin.rubrik.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-sm-2">
                <label class="label-filter d-block">Kode</label>
                <input type="text" name="kode" class="form-control form-control-sm" placeholder="ADM" required>
            </div>
            <div class="col-sm-5">
                <label class="label-filter d-block">Nama kelompok</label>
                <input type="text" name="nama" class="form-control form-control-sm" placeholder="Administrasi" required>
            </div>
            <div class="col-sm-3">
                <label class="label-filter d-block">Bobot %</label>
                <input type="number" name="bobot_persen" class="form-control form-control-sm" min="0" max="100" required>
            </div>
            <div class="col-sm-2">
                <button class="btn btn-utama btn-sm w-100">Tambah</button>
            </div>
        </form>
    </div>
</div>

{{-- ============ KELOMPOK ============ --}}
@foreach ($kategori as $kat)
    @php
        $aktifCount = $kat->indikator->where('aktif', true)->count();
        $bobotKat = $kat->indikator->where('aktif', true)->sum('bobot');
    @endphp
    <div class="panel p-4 mb-3">

        {{-- header --}}
        <div class="kelompok-head">
            <span class="kelompok-kode">{{ $kat->kode }}</span>
            <div class="flex-grow-1 min-w-0">
                <h2 class="kelompok-nama text-truncate">{{ $kat->nama }}</h2>
                <div class="kelompok-meta">
                    {{ $aktifCount }} indikator aktif · bobot {{ rtrim(rtrim(number_format($bobotKat, 2), '0'), '.') }}
                </div>
            </div>
            <span class="badge-bobot">{{ $kat->bobot_persen }}%</span>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-bs-toggle="collapse" data-bs-target="#editKat{{ $kat->id }}">
                <i class="bi bi-pencil me-1"></i> Edit
            </button>
            <button type="button" class="btn-ikon" title="Hapus kelompok"
                    data-hapus-url="{{ route('admin.rubrik.destroy', $kat) }}"
                    data-hapus-nama="{{ $kat->nama }}"
                    data-hapus-sub="Kode {{ $kat->kode }} · {{ $kat->indikator->count() }} indikator"
                    data-hapus-rincian='["Kelompok penilaian ini dihapus","Semua indikator di dalamnya ikut terhapus"]'>
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        {{-- edit kelompok (tersembunyi) --}}
        <div class="collapse" id="editKat{{ $kat->id }}">
            <form method="POST" action="{{ route('admin.rubrik.update', $kat) }}" class="strip-edit row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-sm-2">
                    <label class="label-filter d-block">Kode</label>
                    <input type="text" name="kode" class="form-control form-control-sm" value="{{ $kat->kode }}" required>
                </div>
                <div class="col-sm-5">
                    <label class="label-filter d-block">Nama kelompok</label>
                    <input type="text" name="nama" class="form-control form-control-sm" value="{{ $kat->nama }}" required>
                </div>
                <div class="col-sm-3">
                    <label class="label-filter d-block">Bobot %</label>
                    <input type="number" name="bobot_persen" class="form-control form-control-sm" value="{{ $kat->bobot_persen }}" min="0" max="100" required>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-utama btn-sm w-100">Simpan</button>
                </div>
            </form>
        </div>

        {{-- indikator --}}
        <div class="ind-head mt-3">
            <div>Indikator</div>
            <div>Deskripsi</div>
            <div>Bobot</div>
            <div></div>
            <div></div>
        </div>

        @forelse ($kat->indikator as $ind)
            <div class="ind-row">
                <form method="POST" action="{{ route('admin.rubrik.indikator.update', $ind) }}" class="ind-form">
                    @csrf @method('PUT')
                    <div class="ind-c-nama">
                        <input type="text" name="nama" class="form-control form-control-sm mb-1" value="{{ $ind->nama }}" required>
                        <input type="text" name="peran_khusus" class="form-control form-control-sm" value="{{ $ind->peran_khusus }}" placeholder="peran khusus (opsional)">
                    </div>
                    <div class="ind-c-desk">
                        <textarea name="deskripsi" class="form-control form-control-sm" rows="2" placeholder="Deskripsi indikator">{{ $ind->deskripsi }}</textarea>
                    </div>
                    <div class="ind-c-bobot">
                        <input type="number" step="0.5" name="bobot" class="form-control form-control-sm mb-1" value="{{ rtrim(rtrim(number_format($ind->bobot, 2), '0'), '.') }}" min="0" max="100" required>
                        <label class="d-flex align-items-center gap-1">
                            <input type="checkbox" name="aktif" value="1" @checked($ind->aktif)> aktif
                        </label>
                    </div>
                    <button class="btn btn-utama btn-sm" title="Simpan indikator"><i class="bi bi-check-lg"></i></button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus indikator"
                        data-hapus-langsung
                        data-hapus-url="{{ route('admin.rubrik.indikator.destroy', $ind) }}"
                        data-hapus-nama="{{ $ind->nama }}"
                        data-hapus-sub="Kelompok {{ $kat->nama }}">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        @empty
            <div class="kotak-kosong border-0 my-2">Belum ada indikator di kelompok ini.</div>
        @endforelse

        {{-- tambah indikator --}}
        <form method="POST" action="{{ route('admin.rubrik.indikator.store') }}" class="ind-tambah">
            @csrf
            <input type="hidden" name="kategori_penilaian_id" value="{{ $kat->id }}">
            <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama indikator baru" required>
            <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Deskripsi (opsional)">
            <input type="number" step="0.5" name="bobot" class="form-control form-control-sm" placeholder="Bobot" min="0" max="100" required>
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg me-1"></i> Tambah</button>
        </form>
    </div>
@endforeach

@endsection
