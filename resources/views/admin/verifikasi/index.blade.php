@extends('layouts.admin')

@section('judul', 'Verifikasi Administrasi')
@section('subjudul', 'Tahap 1 — checklist kesesuaian berkas & persyaratan sesuai Juklak')

@section('aksi')
    @if (auth()->user()->isAdmin())
        <a href="{{ route('admin.formadm.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-ui-checks me-1"></i> Atur Butir
        </a>
    @endif
    <a href="{{ route('admin.verifikasi.export', request()->query()) }}" class="btn btn-sm btn-utama">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
@endsection

@section('konten')

{{-- ============ KARTU ============ --}}
<div class="row g-3 mb-4">
    @php
        $kartuDef = [
            ['Total Peserta', $kartu['total'], 'var(--biru)'],
            ['Lolos Administrasi', $kartu['lolos'], 'var(--hijau)'],
            ['Tidak Lolos', $kartu['tidak_lolos'], 'var(--merah)'],
            ['Belum Diverifikasi', $kartu['belum'], 'var(--amber)'],
        ];
    @endphp
    @foreach ($kartuDef as [$label, $angka, $aksen])
        <div class="col-6 col-lg-3">
            <div class="statistik" style="--aksen: {{ $aksen }};">
                <div class="statistik-label">{{ $label }}</div>
                <div class="statistik-angka">{{ number_format($angka, 0, ',', '.') }}</div>
            </div>
        </div>
    @endforeach
</div>

@if ($jmlItem === 0)
    <div class="banner-info mb-4" style="background:#FBE6E4;border-color:#F3C0BC;color:#7A241C;">
        <i class="bi bi-exclamation-triangle mt-1"></i>
        <span>Belum ada butir checklist.
            @if (auth()->user()->isAdmin())
                Buat dulu di <a href="{{ route('admin.formadm.index') }}">Atur Butir</a>.
            @else
                Minta admin membuatnya lebih dulu.
            @endif
        </span>
    </div>
@endif

{{-- ============ FILTER ============ --}}
<div class="panel p-3 mb-4">
    <form method="GET" action="{{ route('admin.verifikasi.index') }}" class="row g-3">
        <div class="col-lg-4">
            <label class="label-filter d-block" for="q">Cari</label>
            <input type="text" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Nama, tim, judul…">
        </div>
        <div class="col-lg-3 col-md-4">
            <label class="label-filter d-block" for="kategori">Kategori</label>
            <select class="form-select" id="kategori" name="kategori">
                <option value="">Semua kategori</option>
                @foreach ($daftarKategori as $k)
                    <option value="{{ $k->id }}" @selected((string) request('kategori') === (string) $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-4">
            <label class="label-filter d-block" for="bidang">Bidang</label>
            <select class="form-select" id="bidang" name="bidang">
                <option value="">Semua bidang</option>
                @foreach ($daftarBidang as $b)
                    <option value="{{ $b->id }}" @selected((string) request('bidang') === (string) $b->id)>{{ $b->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="label-filter d-block" for="hasil">Hasil</label>
            <select class="form-select" id="hasil" name="hasil">
                <option value="">Semua</option>
                <option value="belum" @selected(request('hasil') === 'belum')>Belum diverifikasi</option>
                <option value="lolos" @selected(request('hasil') === 'lolos')>Lolos</option>
                <option value="tidak_lolos" @selected(request('hasil') === 'tidak_lolos')>Tidak Lolos</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-utama btn-sm px-4">Terapkan</button>
            @if (request()->hasAny(['q', 'kategori', 'bidang', 'hasil']))
                <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
        </div>
    </form>
</div>

{{-- ============ TABEL ============ --}}
<div class="panel">
    <div class="table-responsive">
        <table class="table tabel-rapi mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tim / Usaha</th>
                    <th>Ketua</th>
                    <th>Kategori</th>
                    <th>Bidang</th>
                    <th>Hasil Verifikasi</th>
                    <th>Verifikator</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendaftar as $p)
                    @php
                        $v = $p->verifikasi;
                        $badge = match ($v?->hasil) {
                            'lolos' => ['tag-hijau', 'Lolos'],
                            'tidak_lolos' => ['tag-merah', 'Tidak Lolos'],
                            default => $v ? ['tag-kuning', 'Draft'] : ['tag-netral', 'Belum diverifikasi'],
                        };
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($pendaftar->currentPage() - 1) * $pendaftar->perPage() }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama_tim }}</div>
                            <div class="small text-truncate" style="color: var(--redup); max-width: 300px;">{{ $p->judul_inovasi }}</div>
                        </td>
                        <td>
                            <div>{{ $p->nama_ketua }}</div>
                        </td>
                        <td>{{ $p->kategoriPeserta?->nama ?? '—' }}</td>
                        <td>{{ $p->bidangKompetisi?->nama ?? '—' }}</td>
                        <td><span class="tag {{ $badge[0] }}">{{ $badge[1] }}</span></td>
                        <td class="small">{{ $v?->verifikator?->name ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.verifikasi.edit', $p) }}" class="text-decoration-none fw-semibold">
                                {{ $v ? 'Buka Form' : 'Verifikasi' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kotak-kosong border-0">Tidak ada peserta yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($pendaftar->hasPages())
    <div class="mt-3">{{ $pendaftar->links() }}</div>
@endif

@endsection
