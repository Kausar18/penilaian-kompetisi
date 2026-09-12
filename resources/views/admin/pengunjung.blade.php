@extends('layouts.admin')

@section('judul', 'Pengunjung')
@section('subjudul', 'Statistik kunjungan halaman publik')

@section('aksi')
    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </a>
@endsection

@section('konten')

<div class="banner-info mb-3">
    <i class="bi bi-info-circle mt-1"></i>
    <span>
        <strong>Data nyata.</strong> Dihitung dari kunjungan halaman publik
        (<a href="{{ route('publik.beranda') }}" target="_blank" rel="noopener">beranda</a> &amp;
        <a href="{{ route('publik.pengumuman') }}" target="_blank" rel="noopener">pengumuman</a>).
        Kunjungan panitia yang sedang login dan robot mesin pencari tidak dihitung.
        Demi privasi, alamat IP tidak disimpan &mdash; identitas pengunjung dipakai dalam bentuk hash
        yang berganti tiap hari, sehingga <em>unique visitor</em> dihitung per hari.
    </span>
</div>

{{-- ============ KARTU ============ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--biru);">
            <div class="statistik-label">Total Page Views</div>
            <div class="statistik-angka">{{ number_format($data['total_views'], 0, ',', '.') }}</div>
            <div class="statistik-catatan">Sejak mulai tracking</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--teal);">
            <div class="statistik-label">Unique Visitors</div>
            <div class="statistik-angka">{{ number_format($data['total_unique'], 0, ',', '.') }}</div>
            <div class="statistik-catatan">Pengunjung unik</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--amber);">
            <div class="statistik-label">Views Hari Ini</div>
            <div class="statistik-angka">{{ number_format($data['views_hari_ini'], 0, ',', '.') }}</div>
            <div class="statistik-catatan {{ $data['selisih_kemarin'] < 0 ? 'text-danger' : '' }}">
                {{ $data['selisih_kemarin'] >= 0 ? '+' : '' }}{{ $data['selisih_kemarin'] }} vs kemarin
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--cyan);">
            <div class="statistik-label">Unique Hari Ini</div>
            <div class="statistik-angka">{{ number_format($data['unique_hari_ini'], 0, ',', '.') }}</div>
            <div class="statistik-catatan">Pengunjung unik hari ini</div>
        </div>
    </div>
</div>

{{-- ============ TREN 30 HARI ============ --}}
<div class="panel p-4 mb-3">
    <h2 class="h6 mb-1">Tren Kunjungan</h2>
    <p class="small mb-3" style="color: var(--redup);">30 hari terakhir</p>
    <div style="height: 280px;"><canvas id="grafikTren30"></canvas></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-1">Kunjungan Per Jam</h2>
            <p class="small mb-3" style="color: var(--redup);">Hari ini</p>
            <div style="height: 240px;"><canvas id="grafikJam"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-1">Perangkat Pengunjung</h2>
            <p class="small mb-3" style="color: var(--redup);">Distribusi perangkat</p>
            <div style="height: 240px;"><canvas id="grafikPerangkat"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-3">Halaman Terpopuler</h2>
            <table class="table tabel-rapi mb-0">
                <thead><tr><th>Halaman</th><th class="text-end">Views</th><th class="text-end">Unique</th></tr></thead>
                <tbody>
                    @foreach ($data['halaman_populer'] as $h)
                        <tr>
                            <td>{{ $h['halaman'] }}</td>
                            <td class="text-end">{{ number_format($h['views'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($h['unique'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-3">Sumber Trafik</h2>
            <table class="table tabel-rapi mb-0">
                <thead><tr><th>Sumber</th><th class="text-end">Views</th></tr></thead>
                <tbody>
                    @foreach ($data['referrer'] as $r)
                        <tr>
                            <td>{{ $r['sumber'] }}</td>
                            <td class="text-end">{{ number_format($r['views'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('skrip')
@include('partials.animasi-angka')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const biru = '#12459B', teal = '#0E9B8A', amber = '#D98B2B', redup = '#64748B', garis = '#E2E7F0';
    Chart.defaults.font.family = "'Inter', Arial, sans-serif";
    Chart.defaults.color = redup;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;

    const tren = @json($data['tren']);
    new Chart(document.getElementById('grafikTren30'), {
        type: 'line',
        data: {
            labels: Object.keys(tren),
            datasets: [
                { label: 'Page Views', data: Object.values(tren).map(d => d.views), borderColor: biru, backgroundColor: 'rgba(18,69,155,.08)', fill: true, tension: .35, pointRadius: 2 },
                { label: 'Unique Visitors', data: Object.values(tren).map(d => d.unique), borderColor: teal, borderDash: [5, 4], tension: .35, pointRadius: 2 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: garis } } },
        },
    });

    const jam = @json($data['per_jam']);
    new Chart(document.getElementById('grafikJam'), {
        type: 'bar',
        data: { labels: Object.keys(jam), datasets: [{ label: 'Kunjungan', data: Object.values(jam), backgroundColor: '#1B9AAA', borderRadius: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: garis } } } },
    });

    const perangkat = @json($data['perangkat']);
    new Chart(document.getElementById('grafikPerangkat'), {
        type: 'doughnut',
        data: { labels: Object.keys(perangkat), datasets: [{ data: Object.values(perangkat), backgroundColor: [amber, biru, '#7C4DBC'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } },
    });
})();
</script>
@endpush
