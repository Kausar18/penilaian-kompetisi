@extends('layouts.admin')

@section('judul', 'Pendaftar')
@section('subjudul', $pendaftar->total() . ' pendaftar terdaftar')

@section('aksi')
    <a href="{{ route('admin.pendaftar.import') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-upload me-1"></i> Import
    </a>
    <a href="{{ route('admin.pendaftar.export', request()->query()) }}" class="btn btn-sm btn-utama">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
@endsection

@section('konten')

{{-- ============ FILTER ============ --}}
<div class="panel p-3 mb-4">
    <form method="GET" action="{{ route('admin.pendaftar.index') }}" class="row g-3">
        <div class="col-lg-5">
            <label class="label-filter d-block" for="q">Cari</label>
            <input type="text" class="form-control" id="q" name="q"
                   value="{{ request('q') }}" placeholder="Nama, tim, atau judul…">
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
        <div class="col-lg-2 col-md-4">
            <label class="label-filter d-block" for="bidang">Bidang</label>
            <select class="form-select" id="bidang" name="bidang">
                <option value="">Semua bidang</option>
                @foreach ($daftarBidang as $b)
                    <option value="{{ $b->id }}" @selected((string) request('bidang') === (string) $b->id)>{{ $b->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="label-filter d-block" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Semua status</option>
                @foreach (\App\Models\Pendaftar::STATUS as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-utama btn-sm px-4">Terapkan</button>
            @if (request()->hasAny(['q', 'kategori', 'bidang', 'status']))
                <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Ketua Tim</th>
                    <th>Tim / Startup</th>
                    <th>Bidang</th>
                    <th>Kota / Provinsi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendaftar as $p)
                    <tr>
                        <td class="text-nowrap">{{ optional($p->tanggal_daftar)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $p->kategoriPeserta?->nama ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama_ketua }}</div>
                        </td>
                        <td class="fw-semibold">{{ $p->nama_tim }}</td>
                        <td>{{ $p->bidangKompetisi?->nama ?? '—' }}</td>
                        <td>{{ collect([$p->kota, $p->provinsi])->filter()->join(', ') ?: '—' }}</td>
                        <td>
                            <span class="tag {{ ['submitted' => 'tag-kuning', 'verified' => 'tag-hijau', 'rejected' => 'tag-merah'][$p->status] ?? 'tag-netral' }}">
                                {{ \App\Models\Pendaftar::STATUS[$p->status] ?? $p->status }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="aksi-link" data-detail-url="{{ route('admin.pendaftar.kartu', $p) }}">Lihat</button>
                            <span class="aksi-pisah">|</span>
                            <button type="button" class="aksi-link danger"
                                    data-hapus-url="{{ route('admin.pendaftar.destroy', $p) }}"
                                    data-hapus-nama="{{ $p->nama_tim }}"
                                    data-hapus-sub="{{ $p->nama_ketua }}"
                                    data-hapus-rincian='["Data pendaftar &amp; anggota tim dihapus dari database","Penilaian terkait pendaftar ini ikut terhapus"]'>Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="kotak-kosong border-0">Tidak ada pendaftar yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($pendaftar->hasPages())
    <div class="mt-3">{{ $pendaftar->links() }}</div>
@endif

{{-- ============ MODAL DETAIL (muncul di tengah, tanpa pindah halaman) ============ --}}
<div class="modal fade modal-detail" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0" style="border-radius: 1rem; overflow: hidden;">
            <div class="modal-header">
                <h2 class="h6 mb-0" id="modalDetailLabel"><i class="bi bi-person-lines-fill me-1"></i> Detail Pendaftar</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center py-5" style="color: var(--redup);">
                    <span class="spinner-border spinner-border-sm"></span> Memuat…
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('skrip')
<script>
(function () {
    const el = document.getElementById('modalDetail');
    if (!el) return;
    const modal = new bootstrap.Modal(el);
    const body = document.getElementById('modalDetailBody');
    const memuat = '<div class="text-center py-5" style="color: var(--redup);"><span class="spinner-border spinner-border-sm"></span> Memuat…</div>';

    document.addEventListener('click', function (e) {
        const t = e.target.closest('[data-detail-url]');
        if (!t) return;
        e.preventDefault();
        body.innerHTML = memuat;
        modal.show();
        fetch(t.getAttribute('data-detail-url'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.ok ? r.text() : Promise.reject(r.status))
            .then((html) => { body.innerHTML = html; })
            .catch(() => { body.innerHTML = '<div class="alert alert-danger small mb-0">Gagal memuat detail. Coba lagi.</div>'; });
    });
})();
</script>
@endpush
