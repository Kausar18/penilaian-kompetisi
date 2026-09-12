@extends('layouts.admin')

@section('judul', 'Form Administrasi')
@section('subjudul', 'Butir checklist yang dipakai saat verifikasi administrasi')

@section('aksi')
    <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@push('gaya')
<style>
    .bt-head, .bt-row {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) minmax(260px, 2.4fr) max-content max-content max-content;
        gap: .6rem; align-items: start;
    }
    .bt-head {
        font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
        color: var(--redup); padding: .2rem .1rem .55rem; border-bottom: 1px solid var(--garis);
    }
    .bt-row { padding: .7rem .1rem; border-bottom: 1px solid var(--garis); }
    .bt-row:hover { background: #FBFCFE; }
    .bt-form { display: contents; }

    .bt-tambah {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) minmax(260px, 2.4fr) max-content;
        gap: .6rem; align-items: start;
        margin-top: .9rem; padding: .85rem .9rem;
        border: 1px dashed var(--garis); border-radius: .6rem; background: #FCFDFF;
    }

    @media (max-width: 860px) {
        .bt-head { display: none; }
        .bt-row, .bt-tambah { grid-template-columns: 1fr; gap: .5rem; }
        .bt-row > *, .bt-form > * { width: 100%; }
    }
</style>
@endpush

@section('konten')

<div class="banner-info mb-4">
    <i class="bi bi-info-circle mt-1"></i>
    <span>Butir dikelompokkan berdasarkan teks di kolom <strong>Kelompok</strong> — tulis nama kelompok yang sama
        (mis. <em>A. Kesesuaian Kriteria Persyaratan Startup</em>) untuk menaruh beberapa butir dalam satu bagian.</span>
</div>

@forelse ($kelompok as $namaKelompok => $daftarItem)
    <div class="panel p-4 mb-3">
        <div class="kartu-judul"><i class="bi bi-list-check"></i> {{ $namaKelompok }}</div>

        <div class="bt-head">
            <div>Kelompok</div>
            <div>Butir checklist</div>
            <div>Aktif</div>
            <div></div>
            <div></div>
        </div>

        @foreach ($daftarItem as $item)
            <div class="bt-row">
                <form method="POST" action="{{ route('admin.formadm.update', $item) }}" class="bt-form">
                    @csrf @method('PUT')
                    <input type="text" name="kelompok" class="form-control form-control-sm" value="{{ $item->kelompok }}" required>
                    <textarea name="nama" class="form-control form-control-sm" rows="2" required>{{ $item->nama }}</textarea>
                    <label class="d-flex align-items-center gap-1 small pt-1" style="color: var(--redup);">
                        <input type="checkbox" name="aktif" value="1" @checked($item->aktif)> aktif
                    </label>
                    <button class="btn btn-utama btn-sm" title="Simpan butir"><i class="bi bi-check-lg"></i></button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" title="Hapus butir"
                        data-hapus-langsung
                        data-hapus-url="{{ route('admin.formadm.destroy', $item) }}"
                        data-hapus-nama="{{ Str::limit($item->nama, 80) }}"
                        data-hapus-sub="{{ $item->kelompok }}">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        @endforeach
    </div>
@empty
    <div class="kotak-kosong mb-3">Belum ada butir checklist. Tambahkan di bawah.</div>
@endforelse

{{-- ============ TAMBAH BUTIR ============ --}}
<div class="panel p-4">
    <div class="kartu-judul"><i class="bi bi-plus-square"></i> Tambah butir checklist</div>
    <form method="POST" action="{{ route('admin.formadm.store') }}" class="bt-tambah">
        @csrf
        <input type="text" name="kelompok" class="form-control form-control-sm"
               list="daftarKelompok" placeholder="Nama kelompok (mis. A. Kesesuaian Kriteria…)" required>
        <datalist id="daftarKelompok">
            @foreach ($kelompok->keys() as $namaKelompok)
                <option value="{{ $namaKelompok }}"></option>
            @endforeach
        </datalist>
        <input type="text" name="nama" class="form-control form-control-sm" placeholder="Uraian butir checklist" required>
        <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg me-1"></i> Tambah</button>
    </form>
</div>

@endsection
