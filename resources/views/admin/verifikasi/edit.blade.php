@extends('layouts.admin')

@section('judul', 'Verifikasi Administrasi')
@section('subjudul', $p->nama_tim . ' — ' . $p->nama_ketua)

@section('aksi')
    <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar
    </a>
@endsection

@push('gaya')
<style>
    .vf-kepala { display: flex; flex-wrap: wrap; gap: 1.5rem 3rem; }
    .vf-kepala .ket { font-size: .72rem; color: var(--redup); }
    .vf-kepala .isi { font-size: .95rem; font-weight: 700; color: var(--navy); }

    .vf-kelompok {
        display: flex; align-items: center; gap: .55rem;
        margin: 1.5rem 0 .3rem; padding-bottom: .45rem;
        border-bottom: 2px solid var(--biru-muda);
        font-weight: 800; color: var(--navy);
    }
    .vf-kelompok:first-of-type { margin-top: .5rem; }

    .vf-head, .vf-row {
        display: grid;
        grid-template-columns: 2.2rem 1fr 250px minmax(200px, 1fr);
        gap: .7rem; align-items: start;
    }
    .vf-head {
        font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
        color: var(--redup); padding: .5rem .1rem .4rem; border-bottom: 1px solid var(--garis);
    }
    .vf-row { padding: .75rem .1rem; border-bottom: 1px solid var(--garis); }
    .vf-row:hover { background: #FBFCFE; }
    .vf-no { color: var(--redup); font-size: .82rem; padding-top: .35rem; }
    .vf-uraian { font-size: .87rem; line-height: 1.5; padding-top: .3rem; }

    .vf-pilih { display: flex; flex-wrap: wrap; gap: .3rem; }
    .vf-pilih input { position: absolute; opacity: 0; pointer-events: none; }
    .vf-pilih label {
        cursor: pointer; font-size: .74rem; font-weight: 600;
        border: 1.5px solid var(--garis); border-radius: 999px;
        padding: .22rem .6rem; color: var(--redup); background: #fff;
        transition: all .12s ease; user-select: none;
    }
    .vf-pilih label:hover { border-color: var(--cyan); }
    .vf-pilih input:checked + label.p-sesuai { background: #E3F5EC; border-color: #9BDBB6; color: var(--hijau); }
    .vf-pilih input:checked + label.p-tidak  { background: #FBE6E4; border-color: #EBB0A8; color: var(--merah); }
    .vf-pilih input:checked + label.p-na     { background: #EEF1F6; border-color: #CBD3DF; color: var(--redup); }
    .vf-pilih input:focus-visible + label { box-shadow: 0 0 0 .2rem rgba(34,166,201,.25); }

    @media (max-width: 900px) {
        .vf-head { display: none; }
        .vf-row { grid-template-columns: 1fr; gap: .5rem; }
        .vf-no { display: none; }
    }
</style>
@endpush

@section('konten')

<form method="POST" action="{{ route('admin.verifikasi.update', $p) }}">
    @csrf @method('PUT')

    {{-- ============ KEPALA FORM ============ --}}
    <div class="panel p-4 mb-3">
        <div class="vf-kepala">
            <div>
                <div class="ket">Nama Calon Startup</div>
                <div class="isi">{{ $p->nama_tim }}</div>
            </div>
            <div>
                <div class="ket">Produk / Judul Inovasi</div>
                <div class="isi">{{ $p->judul_inovasi ?: '—' }}</div>
            </div>
            <div>
                <div class="ket">Ketua Tim</div>
                <div class="isi">{{ $p->nama_ketua }}</div>
            </div>
            <div>
                <div class="ket">Kategori / Bidang</div>
                <div class="isi">
                    {{ $p->kategoriPeserta?->nama ?? '—' }} · {{ $p->bidangKompetisi?->nama ?? '—' }}
                </div>
            </div>
            @if ($p->link_pitchdeck || $p->link_logo)
                <div>
                    <div class="ket">Berkas</div>
                    <div class="d-flex gap-2 mt-1">
                        @if ($p->link_pitchdeck)
                            <a href="{{ $p->link_pitchdeck }}" target="_blank" rel="noopener" class="chip text-decoration-none">
                                <i class="bi bi-file-earmark-slides"></i> Pitch Deck
                            </a>
                        @endif
                        @if ($p->link_logo)
                            <a href="{{ $p->link_logo }}" target="_blank" rel="noopener" class="chip text-decoration-none">
                                <i class="bi bi-image"></i> Logo
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ============ CHECKLIST ============ --}}
    <div class="panel p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-1">
            <h2 class="h6 mb-0"><i class="bi bi-ui-checks me-1"></i> Checklist Kesesuaian</h2>
            @if ($verifikasi->exists && $verifikasi->verifikator)
                <div class="small" style="color: var(--redup);">
                    Terakhir diisi oleh <strong>{{ $verifikasi->verifikator->name }}</strong>
                    {{ optional($verifikasi->diverifikasi_at ?? $verifikasi->updated_at)->format('d M Y H:i') }}
                </div>
            @endif
        </div>

        @forelse ($kelompok as $namaKelompok => $daftarItem)
            <div class="vf-kelompok"><i class="bi bi-list-check"></i> {{ $namaKelompok }}</div>

            <div class="vf-head">
                <div>No</div>
                <div>Uraian</div>
                <div>Kesesuaian</div>
                <div>Catatan</div>
            </div>

            @foreach ($daftarItem as $item)
                @php $d = $tersimpan[$item->id] ?? null; @endphp
                <div class="vf-row">
                    <div class="vf-no">{{ $loop->iteration }}.</div>
                    <div class="vf-uraian">{{ $item->nama }}</div>
                    <div class="vf-pilih">
                        @foreach (\App\Models\DetailVerifikasi::STATUS as $kunci => $label)
                            @php $idRadio = 'st-'.$item->id.'-'.$kunci; @endphp
                            <input type="radio" name="status[{{ $item->id }}]" value="{{ $kunci }}"
                                   id="{{ $idRadio }}" @checked(($d->status ?? null) === $kunci)>
                            <label for="{{ $idRadio }}" class="p-{{ ['sesuai' => 'sesuai', 'tidak_sesuai' => 'tidak', 'na' => 'na'][$kunci] }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <div>
                        <input type="text" name="catatan_item[{{ $item->id }}]" class="form-control form-control-sm"
                               value="{{ old('catatan_item.'.$item->id, $d->catatan ?? '') }}" placeholder="Catatan (opsional)">
                    </div>
                </div>
            @endforeach
        @empty
            <div class="kotak-kosong">
                Butir checklist belum dibuat.
                @if (auth()->user()->isAdmin())
                    Atur di <a href="{{ route('admin.formadm.index') }}">Form Administrasi</a>.
                @endif
            </div>
        @endforelse
    </div>

    {{-- ============ REKOMENDASI ============ --}}
    <div class="panel p-4">
        <h2 class="h6 mb-3"><i class="bi bi-flag me-1"></i> Rekomendasi &amp; Catatan</h2>

        <div class="banner-info mb-3">
            <i class="bi bi-info-circle mt-1"></i>
            <span>Memilih <strong>Lolos</strong> membuat status pendaftar menjadi <strong>Terverifikasi</strong>
                sehingga peserta masuk ke tahap <strong>Penilaian</strong>. <strong>Tidak Lolos</strong> menjadikannya <strong>Ditolak</strong>.
                Biarkan kosong untuk menyimpan sebagai draft.</span>
        </div>

        <div class="row g-3">
            <div class="col-md-5">
                <label for="hasil" class="label-filter d-block">Rekomendasi</label>
                <select name="hasil" id="hasil" class="form-select">
                    <option value="">— belum diputuskan (draft) —</option>
                    @foreach (\App\Models\VerifikasiAdministrasi::HASIL as $kunci => $label)
                        <option value="{{ $kunci }}" @selected($verifikasi->hasil === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7">
                <label class="label-filter d-block">Status pendaftar saat ini</label>
                <div class="form-control bg-light" style="pointer-events: none;">
                    {{ \App\Models\Pendaftar::STATUS[$p->status] ?? $p->status }}
                </div>
            </div>
            <div class="col-12">
                <label for="catatan" class="label-filter d-block">Catatan verifikator</label>
                <textarea name="catatan" id="catatan" class="form-control" rows="3"
                          placeholder="Rekomendasi dan catatan hasil verifikasi…">{{ old('catatan', $verifikasi->catatan) }}</textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                <button type="submit" class="btn btn-utama btn-sm px-4">Simpan Verifikasi</button>
            </div>
        </div>
    </div>
</form>

@endsection
