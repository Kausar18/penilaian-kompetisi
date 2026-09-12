@extends('layouts.admin')

@section('judul', $p->nama_tim)
@section('subjudul', $p->nama_ketua . ' — ' . ($p->kategoriPeserta?->nama ?? 'Tanpa kategori'))

@section('aksi')
    <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <a href="{{ route('admin.penilaian.edit', $p) }}" class="btn btn-sm btn-utama">
        <i class="bi bi-clipboard-check me-1"></i> Edit Nilai
    </a>
@endsection

@section('konten')

@php
    $baris = fn ($label, $nilai) => view('admin.pendaftar._baris', ['label' => $label, 'nilai' => $nilai]);
@endphp

<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel p-4 mb-3">
            <h2 class="h6 mb-3"><i class="bi bi-person-badge me-1"></i> Informasi Peserta</h2>

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <span class="label-filter mb-0"><i class="bi bi-patch-check me-1"></i>Status Verifikasi</span>

                {{-- Baca-saja. Status ini cermin dari hasil Verifikasi Administrasi; mengubahnya
                     di sini akan menghasilkan keputusan tanpa jejak butir Juklak mana yang gagal. --}}
                <span class="status-baca st-{{ $p->status }}">{{ \App\Models\Pendaftar::STATUS[$p->status] ?? $p->status }}</span>

                <a href="{{ route('admin.verifikasi.edit', $p) }}" class="status-hint text-decoration-none">
                    <i class="bi bi-pencil-square"></i> ubah lewat Verifikasi Administrasi
                </a>
            </div>

            <dl class="row row-cols-1 mb-0 small">
                {!! $baris('Nama Ketua', $p->nama_ketua) !!}
                {!! $baris('Nama Tim / Usaha', $p->nama_tim) !!}
                {!! $baris('Kategori', $p->kategoriPeserta?->nama) !!}
                {!! $baris('Bidang Kompetisi', $p->bidangKompetisi?->nama) !!}
                {!! $baris('Kota / Provinsi', collect([$p->kota, $p->provinsi])->filter()->join(' / ')) !!}
                {!! $baris('Asal Institusi', $p->asal_institusi) !!}
                {!! $baris('Fakultas / Prodi', $p->fakultas_prodi) !!}
                {!! $baris('Tanggal Daftar', optional($p->tanggal_daftar)->format('d M Y')) !!}
            </dl>
            <div class="mt-3 d-flex gap-2 flex-wrap">
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

        <div class="panel p-4">
            <h2 class="h6 mb-3"><i class="bi bi-people me-1"></i> Anggota Tim</h2>
            @forelse ($p->anggotaTim as $a)
                <div class="d-flex justify-content-between py-1 small {{ ! $loop->last ? 'border-bottom' : '' }}" style="border-color: var(--garis) !important;">
                    <span>{{ $a->nama }}</span>
                    <span style="color: var(--redup);">{{ $a->nim ?? '—' }}</span>
                </div>
            @empty
                <p class="small mb-0" style="color: var(--redup);">Tidak ada data anggota tim.</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel p-4">
            <h2 class="h6 mb-3"><i class="bi bi-lightbulb me-1"></i> Informasi Inovasi</h2>

            @php
                $narasi = [
                    'Judul Inovasi' => $p->judul_inovasi,
                    'Deskripsi Singkat' => $p->deskripsi_singkat,
                    'Permasalahan' => $p->permasalahan,
                    'Solusi' => $p->solusi,
                ];
            @endphp

            @foreach ($narasi as $label => $isi)
                <div class="mb-3">
                    <div class="label-filter">{{ $label }}</div>
                    <div class="small" style="white-space: pre-line; line-height: 1.6;">{{ $isi ?: '—' }}</div>
                </div>
            @endforeach

            @if ($p->penilaian)
                <hr style="border-color: var(--garis);">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="label-filter">Status Penilaian</div>
                        <span class="tag {{ ['selesai' => 'tag-hijau', 'sebagian' => 'tag-kuning', 'belum' => 'tag-netral'][$p->penilaian->status_penilaian] }}">
                            {{ ucfirst($p->penilaian->status_penilaian) }}
                        </span>
                    </div>
                    @if ($p->penilaian->nilai_final !== null)
                        <div>
                            <div class="label-filter">Nilai Akhir</div>
                            <span class="fw-bold" style="color: var(--navy);">{{ $p->penilaian->nilai_final }}/{{ $nilaiMaks }}</span>
                        </div>
                    @endif
                    @if ($p->penilaian->rekomendasi)
                        <div>
                            <div class="label-filter">Rekomendasi</div>
                            <span class="tag {{ $p->penilaian->rekomendasi === 'lolos' ? 'tag-hijau' : 'tag-merah' }}">
                                {{ $p->penilaian->rekomendasi_label }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
