@extends('layouts.admin')

@section('judul', 'Rekap Nilai')
@section('subjudul', 'Ranking peserta berdasarkan penilaian reviewer')

@section('aksi')
    <a href="{{ route('admin.penilaian.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil-square me-1"></i> Input Penilaian
    </a>
    <a href="{{ route('admin.rekap.export', request()->query()) }}" class="btn btn-sm btn-utama">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
@endsection

@section('konten')

{{-- ============ KARTU ============ --}}
<div class="row g-3 mb-4">
    @php
        $kartuDef = [
            ['Total Peserta', number_format($kartu['total'], 0, ',', '.'), 'var(--biru)'],
            ['Sudah Dinilai', number_format($kartu['dinilai'], 0, ',', '.'), 'var(--teal)'],
            ['Rekomendasi Lolos', number_format($kartu['lolos'], 0, ',', '.'), 'var(--hijau)'],
            ['Rata-rata Nilai Akhir', $kartu['rata'] ?: '–', 'var(--amber)'],
        ];
    @endphp
    @foreach ($kartuDef as [$label, $angka, $aksen])
        <div class="col-6 col-lg-3">
            <div class="statistik" style="--aksen: {{ $aksen }};">
                <div class="statistik-label">{{ $label }}</div>
                <div class="statistik-angka">{{ $angka }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ============ FILTER ============ --}}
<div class="panel p-3 mb-4">
    <form method="GET" action="{{ route('admin.rekap.index') }}" class="row g-3">
        <div class="col-lg-3 col-md-6">
            <label class="label-filter d-block" for="q">Cari</label>
            <input type="text" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Nama / email / tim / judul">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="label-filter d-block" for="kategori">Kategori</label>
            <select class="form-select" id="kategori" name="kategori">
                <option value="">Semua</option>
                @foreach ($daftarKategori as $k)
                    <option value="{{ $k->id }}" @selected((string) request('kategori') === (string) $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="label-filter d-block" for="bidang">Bidang</label>
            <select class="form-select" id="bidang" name="bidang">
                <option value="">Semua</option>
                @foreach ($daftarBidang as $b)
                    <option value="{{ $b->id }}" @selected((string) request('bidang') === (string) $b->id)>{{ $b->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="label-filter d-block" for="reviewer">Reviewer</label>
            <select class="form-select" id="reviewer" name="reviewer">
                <option value="">Semua</option>
                @foreach ($daftarReviewer as $r)
                    <option value="{{ $r->id }}" @selected((string) request('reviewer') === (string) $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-6">
            <label class="label-filter d-block" for="rekomendasi">Rekom.</label>
            <select class="form-select" id="rekomendasi" name="rekomendasi">
                <option value="">Semua</option>
                <option value="lolos" @selected(request('rekomendasi') === 'lolos')>Lolos</option>
                <option value="tidak_lolos" @selected(request('rekomendasi') === 'tidak_lolos')>Tidak</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="label-filter d-block" for="sort">Urutkan</label>
            <select class="form-select" id="sort" name="sort">
                @foreach ($sortOpsi as $key => $label)
                    <option value="{{ $key }}" @selected($sortKey === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-utama btn-sm px-4">Terapkan Filter</button>
            <a href="{{ route('admin.rekap.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- ============ TABEL ============ --}}
<div class="panel">
    <div class="table-responsive">
        <table class="table tabel-rapi mb-0">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Tim / Usaha</th>
                    <th>Kategori</th>
                    <th>Ketua</th>
                    <th>Bidang</th>
                    <th>Reviewer</th>
                    @foreach ($kelompok as $k)
                        <th class="text-end text-nowrap">{{ $k->nama }}<br><span style="font-weight:400;">/{{ $pengaturan->rumus === 'mentah' ? $pengaturan->maksNilai() * $k->bobot_persen : $k->bobot_persen }}</span></th>
                    @endforeach
                    <th class="text-end">Nilai Akhir</th>
                    <th>Rekomendasi</th>
                    <th>Catatan</th>
                    <th class="text-nowrap">Tgl Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $p)
                    @php $n = $p->penilaian; @endphp
                    <tr>
                        <td class="fw-bold">{{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama_tim }}</div>
                            <div class="small text-truncate" style="color: var(--redup); max-width: 260px;">{{ $p->judul_inovasi }}</div>
                        </td>
                        <td>{{ $p->kategoriPeserta?->nama ?? '—' }}</td>
                        <td>
                            <div>{{ $p->nama_ketua }}</div>
                            <div class="small" style="color: var(--redup);">{{ $p->email }}</div>
                        </td>
                        <td>{{ $p->bidangKompetisi?->nama ?? '—' }}</td>
                        <td class="small">{{ $p->reviewer?->name ?? '—' }}</td>
                        @foreach ($kelompok as $k)
                            <td class="text-end">{{ isset($p->subtotal[$k->kode]) ? rtrim(rtrim(number_format($p->subtotal[$k->kode], 2), '0'), '.') : '—' }}</td>
                        @endforeach
                        <td class="text-end fw-bold" style="color: var(--navy);">{{ $n?->nilai_final ?? '—' }}</td>
                        <td>
                            @if ($n?->rekomendasi)
                                <span class="tag {{ $n->rekomendasi === 'lolos' ? 'tag-hijau' : 'tag-merah' }}">{{ $n->rekomendasi_label }}</span>
                            @elseif ($n)
                                <span class="tag tag-kuning">Draft</span>
                            @else
                                <span class="tag tag-netral">—</span>
                            @endif
                        </td>
                        <td class="small" style="max-width: 260px;">{{ Str::limit($n?->catatan_reviewer, 90) ?: '—' }}</td>
                        <td class="small text-nowrap">{{ optional($n?->updated_at)->format('d/m/y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 9 + $kelompok->count() }}" class="kotak-kosong border-0">Belum ada penilaian yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($rows->hasPages())
    <div class="mt-3">{{ $rows->links() }}</div>
@endif

@endsection
