@extends('layouts.admin')

@section('judul', 'Statistik')
@section('subjudul', 'Ringkasan pendaftar & penilaian kompetisi')

@section('aksi')
    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </a>
@endsection

@section('konten')

{{-- ============ KARTU RINGKASAN ============ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--biru);">
            <div class="statistik-label">Total Pendaftar</div>
            <div class="statistik-angka">{{ number_format($ringkasan['total'], 0, ',', '.') }}</div>
            <div class="statistik-catatan">{{ count($ringkasan['per_kategori']) }} kategori peserta</div>
        </div>
    </div>
    @foreach ($ringkasan['per_kategori'] as $namaKategori => $jml)
        <div class="col-6 col-lg-3">
            <div class="statistik" style="--aksen: {{ $loop->first ? 'var(--teal)' : 'var(--amber)' }};">
                <div class="statistik-label">{{ $namaKategori }}</div>
                <div class="statistik-angka">{{ number_format($jml, 0, ',', '.') }}</div>
                <div class="statistik-catatan">
                    {{ $ringkasan['total'] ? round($jml / $ringkasan['total'] * 100) : 0 }}% dari total
                </div>
            </div>
        </div>
    @endforeach
    <div class="col-6 col-lg-3">
        <div class="statistik" style="--aksen: var(--cyan);">
            <div class="statistik-label">Pendaftar Hari Ini</div>
            <div class="statistik-angka">{{ number_format($ringkasan['hari_ini'], 0, ',', '.') }}</div>
            <div class="statistik-catatan">{{ number_format($ringkasan['sudah_dinilai'], 0, ',', '.') }} sudah dinilai</div>
        </div>
    </div>
</div>

{{-- ============ TREN PENDAFTARAN ============ --}}
<div class="panel p-4 mb-3">
    <h2 class="h6 mb-1">Tren Pendaftaran</h2>
    {{-- periode mengikuti jendela yang dipakai controller; untuk data arsip
         jendelanya bergeser ke masa pendaftaran yang sebenarnya --}}
    <p class="small mb-3" style="color: var(--redup);">
        {{ array_key_first($tren) }} &ndash; {{ array_key_last($tren) }} (berdasarkan tanggal daftar)
    </p>
    <div style="height: 260px;"><canvas id="grafikTren"></canvas></div>
</div>

<div class="row g-3 mb-3">
    {{-- ============ PER BIDANG ============ --}}
    <div class="col-lg-6">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-1">Per Bidang Kompetisi</h2>
            <p class="small mb-3" style="color: var(--redup);">Sebaran pendaftar per bidang</p>
            <div style="height: 300px;"><canvas id="grafikBidang"></canvas></div>
        </div>
    </div>
    {{-- ============ STATUS VERIFIKASI ============ --}}
    <div class="col-lg-6">
        <div class="panel p-4 h-100">
            <h2 class="h6 mb-1">Status Verifikasi</h2>
            <p class="small mb-3" style="color: var(--redup);">Kondisi berkas pendaftar</p>
            <div style="height: 300px;"><canvas id="grafikStatus"></canvas></div>
        </div>
    </div>
</div>

{{-- ============ PROVINSI ============ --}}
<div class="panel p-4 mb-3">
    <h2 class="h6 mb-1">Provinsi Asal Peserta</h2>
    <p class="small mb-3" style="color: var(--redup);">15 provinsi terbanyak</p>
    <div style="height: {{ max(220, count($perProvinsi) * 26) }}px;"><canvas id="grafikProvinsi"></canvas></div>
</div>

{{-- ============ RINGKASAN PENILAIAN ============ --}}
<div class="row g-3 mb-4">
    @foreach ($statusPenilaian as $label => $jml)
        <div class="col-6 col-lg-4">
            <div class="statistik" style="--aksen: {{ ['Belum dinilai' => 'var(--redup)', 'Sebagian' => 'var(--amber)', 'Selesai' => 'var(--teal)'][$label] ?? 'var(--biru)' }};">
                <div class="statistik-label">{{ $label }}</div>
                <div class="statistik-angka">{{ number_format($jml, 0, ',', '.') }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ============ STATISTIK PENGUNJUNG (STUB) ============ --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h2 class="h6 mb-0">Statistik Pengunjung</h2>
    <a href="{{ route('admin.pengunjung') }}" class="small text-decoration-none">Detail <i class="bi bi-arrow-right"></i></a>
</div>
<div class="banner-info mb-3">
    <i class="bi bi-info-circle mt-1"></i>
    <span>
        Angka pengunjung dihitung dari <strong>kunjungan nyata</strong> ke halaman publik.
        Panitia yang sedang login dan robot mesin pencari tidak dihitung.
    </span>
</div>
<div class="row g-3">
    @php
        $kartuPengunjung = [
            ['Total Page Views', $pengunjung['total_views'], 'var(--biru)'],
            ['Unique Visitors', $pengunjung['total_unique'], 'var(--teal)'],
            ['Views Hari Ini', $pengunjung['views_hari_ini'], 'var(--amber)'],
            ['Unique Hari Ini', $pengunjung['unique_hari_ini'], 'var(--cyan)'],
        ];
    @endphp
    @foreach ($kartuPengunjung as [$label, $angka, $aksen])
        <div class="col-6 col-lg-3">
            <div class="statistik" style="--aksen: {{ $aksen }};">
                <div class="statistik-label">{{ $label }}</div>
                <div class="statistik-angka">{{ number_format($angka, 0, ',', '.') }}</div>
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('skrip')
@include('partials.animasi-angka')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const biru = '#12459B', cyan = '#22A6C9', teal = '#0E9B8A', amber = '#D98B2B',
          redup = '#64748B', garis = '#E2E7F0', navy = '#0B2545';
    const palet = [biru, amber, teal, cyan, '#7C4DBC', '#C0392B', '#0E9B6A', '#E17055'];

    Chart.defaults.font.family = "'Inter', Arial, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = redup;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;

    const rapi = (n) => new Intl.NumberFormat('id-ID').format(n);
    const persen = (v, arr) => { const t = arr.reduce((a, b) => a + b, 0); return t ? Math.round(v / t * 100) : 0; };
    const legendaPersen = (chart) => {
        const ds = chart.data.datasets[0];
        return chart.data.labels.map((label, i) => ({
            text: `${label} (${persen(ds.data[i], ds.data)}%)`,
            fillStyle: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[i] : ds.backgroundColor,
            index: i,
        }));
    };

    // ---- Tren pendaftaran
    const tren = @json($tren);
    new Chart(document.getElementById('grafikTren'), {
        type: 'line',
        data: {
            labels: Object.keys(tren),
            datasets: [{
                label: 'Pendaftar',
                data: Object.values(tren),
                borderColor: biru,
                backgroundColor: 'rgba(18,69,155,.08)',
                fill: true,
                tension: .35,
                pointRadius: 3,
                pointBackgroundColor: biru,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: garis } },
            },
        },
    });

    // ---- Per bidang
    const bidang = @json($perBidang);
    new Chart(document.getElementById('grafikBidang'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(bidang),
            datasets: [{ data: Object.values(bidang), backgroundColor: palet, borderWidth: 0 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '58%',
            plugins: {
                legend: { position: 'bottom', labels: { generateLabels: legendaPersen } },
                tooltip: { callbacks: { label: (k) => ` ${k.label}: ${rapi(k.parsed)} (${persen(k.parsed, k.dataset.data)}%)` } },
            },
        },
    });

    // ---- Status verifikasi
    const status = @json($statusVerifikasi);
    new Chart(document.getElementById('grafikStatus'), {
        type: 'bar',
        data: {
            labels: Object.keys(status),
            datasets: [{ label: 'Pendaftar', data: Object.values(status), backgroundColor: [amber, teal, redup], borderRadius: 6, barThickness: 60 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: garis } } },
        },
    });

    // ---- Provinsi
    const prov = @json($perProvinsi);
    new Chart(document.getElementById('grafikProvinsi'), {
        type: 'bar',
        data: {
            labels: Object.keys(prov),
            datasets: [{ label: 'Pendaftar', data: Object.values(prov), backgroundColor: biru, borderRadius: 4, barThickness: 14 }],
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: garis } }, y: { grid: { display: false } } },
        },
    });
})();
</script>
@endpush
