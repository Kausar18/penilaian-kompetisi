@extends('layouts.admin')

@section('judul', 'Penugasan Reviewer')
@section('subjudul', 'Bagi peserta ke reviewer — satu peserta satu reviewer')

@section('konten')

@if ($hanyaLolos)
    <div class="banner-info mb-4">
        <i class="bi bi-info-circle mt-1"></i>
        <span>
            Yang tampil di sini <strong>hanya peserta yang lolos verifikasi administrasi</strong> —
            sama persis dengan daftar di menu Penilaian. Peserta yang belum atau tidak lolos tidak
            bisa ditugaskan karena reviewer memang tidak akan bisa menilainya.
        </span>
    </div>
@endif

@unless ($bisaUbah)
    <div class="banner-info mb-4">
        <i class="bi bi-info-circle mt-1"></i>
        <span>Halaman ini <strong>hanya bisa dilihat</strong>. Perubahan penugasan dilakukan oleh admin.</span>
    </div>
@endunless

{{-- ============ KARTU ============ --}}
<div class="row g-3 mb-4">
    @php
        $kartuDef = [
            [$hanyaLolos ? 'Lolos Administrasi' : 'Total Peserta', $kartu['total'], 'var(--biru)'],
            ['Sudah Ditugaskan', $kartu['ditugaskan'], 'var(--teal)'],
            ['Belum Ditugaskan', $kartu['belum'], 'var(--amber)'],
            ['Jumlah Reviewer', $kartu['reviewer'], 'var(--redup)'],
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

@if ($daftarReviewer->isEmpty())
    <div class="banner-info mb-4" style="background:#FBE6E4;border-color:#F3C0BC;color:#7A241C;">
        <i class="bi bi-exclamation-triangle mt-1"></i>
        <div>
            Belum ada akun <strong>reviewer</strong>. Buat lewat terminal:
            <code>php artisan admin:buat --peran=reviewer --username=... --name="..." --password=...</code>
        </div>
    </div>
@else
    {{-- beban tiap reviewer --}}
    <div class="panel p-3 mb-4">
        <div class="label-filter mb-2">Beban per reviewer</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach ($daftarReviewer as $r)
                <span class="chip">{{ $r->name }} · {{ $bebanReviewer[$r->id] ?? 0 }}</span>
            @endforeach
            @if ($kartu['belum'] > 0)
                <span class="chip" style="background:#FBF0E0;color:var(--amber);">Belum ditugaskan · {{ $kartu['belum'] }}</span>
            @endif
        </div>
    </div>
@endif

{{-- ============ FILTER ============ --}}
<div class="panel p-3 mb-4">
    <form method="GET" action="{{ route('admin.penugasan.index') }}" class="row g-3">
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
            <label class="label-filter d-block" for="tugas">Status tugas</label>
            <select class="form-select" id="tugas" name="tugas">
                <option value="">Semua</option>
                <option value="belum" @selected(request('tugas') === 'belum')>Belum ditugaskan</option>
                <option value="sudah" @selected(request('tugas') === 'sudah')>Sudah ditugaskan</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-utama btn-sm px-4">Terapkan</button>
            @if (request()->hasAny(['q', 'kategori', 'bidang', 'tugas']))
                <a href="{{ route('admin.penugasan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                    <th>Kategori</th>
                    <th>Bidang</th>
                    <th>Status Penilaian</th>
                    <th style="min-width: 220px;">Ditugaskan ke</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendaftar as $p)
                    @php $st = $p->penilaian?->status_penilaian ?? 'belum'; @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($pendaftar->currentPage() - 1) * $pendaftar->perPage() }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama_tim }}</div>
                            <div class="small" style="color: var(--redup);">{{ $p->nama_ketua }}</div>
                        </td>
                        <td>{{ $p->kategoriPeserta?->nama ?? '—' }}</td>
                        <td>{{ $p->bidangKompetisi?->nama ?? '—' }}</td>
                        <td>
                            <span class="tag {{ ['selesai' => 'tag-hijau', 'sebagian' => 'tag-kuning', 'belum' => 'tag-netral'][$st] }}">
                                {{ ['selesai' => 'Selesai', 'sebagian' => 'Sebagian', 'belum' => 'Belum'][$st] }}
                            </span>
                        </td>
                        <td>
                            @if ($bisaUbah)
                                <form method="POST" action="{{ route('admin.penugasan.update', $p) }}">
                                    @csrf @method('PUT')
                                    <select name="reviewer_id" class="form-select form-select-sm"
                                            onchange="this.form.submit()"
                                            @disabled($daftarReviewer->isEmpty())>
                                        <option value="">— belum ditugaskan —</option>
                                        @foreach ($daftarReviewer as $r)
                                            <option value="{{ $r->id }}" @selected($p->reviewer_id === $r->id)>{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="{{ $p->reviewer_id ? '' : 'text-muted' }}">
                                    {{ $p->reviewer?->name ?? '— belum ditugaskan —' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kotak-kosong border-0">Tidak ada peserta yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($pendaftar->hasPages())
    <div class="mt-3">{{ $pendaftar->links() }}</div>
@endif

@endsection
