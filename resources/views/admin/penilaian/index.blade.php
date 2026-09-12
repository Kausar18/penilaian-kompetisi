@extends('layouts.admin')

@section('judul', 'Penilaian')
@section('subjudul', $adalahReviewer ? 'Startup yang ditugaskan kepada Anda' : 'Scorecard seleksi administrasi & substansi')

@section('aksi')
    <a href="{{ route('admin.penilaian.export', request()->query()) }}" class="btn btn-sm btn-utama">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
@endsection

@section('konten')

@php
    $totalBobotAktif = (float) \App\Models\IndikatorPenilaian::aktif()->sum('bobot');
    $nilaiMaks = rtrim(rtrim(number_format($pengaturan->nilaiMaksimum($totalBobotAktif), 2, '.', ''), '0'), '.');
@endphp

@if ($pengaturan->hanya_terverifikasi)
    <div class="banner-info mb-3">
        <i class="bi bi-funnel mt-1"></i>
        <span>Hanya menampilkan peserta yang dinyatakan <strong>Lolos</strong> pada <a href="{{ route('admin.verifikasi.index') }}">Verifikasi Administrasi</a>.
            @unless ($adalahReviewer) Bisa diubah di <a href="{{ route('admin.rubrik.index') }}">Rubrik → Pengaturan Penilaian</a>. @endunless
        </span>
    </div>
@endif

{{-- ============ KARTU ============ --}}
<div class="row g-3 mb-4">
    @php
        $kartuDef = [
            ['Total Pendaftar', $kartu['total'], 'var(--biru)'],
            ['Sudah Dinilai', $kartu['selesai'], 'var(--teal)'],
            ['Sebagian', $kartu['sebagian'], 'var(--amber)'],
            ['Belum Dinilai', $kartu['belum'], 'var(--redup)'],
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

{{-- ============ FILTER ============ --}}
<div class="panel p-3 mb-4">
    <form method="GET" action="{{ route('admin.penilaian.index') }}" class="row g-3">
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
            <label class="label-filter d-block" for="status_nilai">Status penilaian</label>
            <select class="form-select" id="status_nilai" name="status_nilai">
                <option value="">Semua Status Penilaian</option>
                @foreach ([
                    'belum' => 'Belum Dinilai',
                    'belum_lengkap' => 'Nilai Belum Lengkap',
                    'draft' => 'Draft',
                    'lolos' => 'Lolos',
                    'tidak_lolos' => 'Tidak Lolos',
                ] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status_nilai') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-utama btn-sm px-4">Terapkan</button>
            @if (request()->hasAny(['q', 'kategori', 'bidang', 'status_nilai']))
                <a href="{{ route('admin.penilaian.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                    <th>Kategori</th>
                    <th>Tim / Usaha</th>
                    <th>Ketua</th>
                    <th>Bidang</th>
                    <th>Status Admin</th>
                    <th>Penilaian Saya</th>
                    @unless ($adalahReviewer)<th>Reviewer</th>@endunless
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendaftar as $p)
                    @php
                        $n = $p->penilaian;
                        $terisi = (int) ($n->skor_terisi ?? 0);
                        $adaSkor = $n && $terisi > 0;

                        if (! $adaSkor)                          $badge = ['tag-netral', 'Belum dinilai'];
                        elseif ($n->rekomendasi === 'lolos')     $badge = ['tag-hijau', 'Lolos'];
                        elseif ($n->rekomendasi === 'tidak_lolos') $badge = ['tag-merah', 'Tidak Lolos'];
                        elseif ($terisi < $jmlIndikator)         $badge = ['tag-kuning', 'Belum lengkap'];
                        else                                     $badge = ['tag-kuning', 'Draft'];
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($pendaftar->currentPage() - 1) * $pendaftar->perPage() }}</td>
                        <td>{{ $p->kategoriPeserta?->nama ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->nama_tim }}</div>
                            <div class="small text-truncate" style="color: var(--redup); max-width: 320px;">{{ $p->judul_inovasi }}</div>
                        </td>
                        <td>
                            <div>{{ $p->nama_ketua }}</div>
                        </td>
                        <td>{{ $p->bidangKompetisi?->nama ?? '—' }}</td>
                        <td>
                            <span class="tag {{ ['submitted' => 'tag-kuning', 'verified' => 'tag-hijau', 'rejected' => 'tag-merah'][$p->status] ?? 'tag-netral' }}">
                                {{ \App\Models\Pendaftar::STATUS[$p->status] ?? $p->status }}
                            </span>
                        </td>
                        <td>
                            @if ($adaSkor)
                                <div class="fw-bold" style="color: var(--navy);">{{ $n->nilai_final ?? 0 }}<span class="fw-normal" style="color: var(--redup);">/{{ $nilaiMaks }}</span></div>
                            @endif
                            <span class="tag {{ $badge[0] }}">{{ $badge[1] }}</span>
                        </td>
                        @unless ($adalahReviewer)
                            <td class="small">{{ $p->reviewer?->name ?? '—' }}</td>
                        @endunless
                        <td class="text-end">
                            <a href="{{ route('admin.penilaian.edit', $p) }}" class="text-decoration-none fw-semibold">Edit Nilai</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $adalahReviewer ? 8 : 9 }}" class="kotak-kosong border-0">
                        {{ $adalahReviewer ? 'Belum ada startup yang ditugaskan kepada Anda.' : 'Tidak ada pendaftar yang cocok.' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($pendaftar->hasPages())
    <div class="mt-3">{{ $pendaftar->links() }}</div>
@endif

@endsection
