@extends('layouts.admin')

@section('judul', 'Import Pendaftar')
@section('subjudul', 'Unggah hasil export Google Form (CSV / Excel)')

@section('aksi')
    <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('konten')

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel p-4">
            <h2 class="h6 mb-3">Unggah berkas</h2>

            <form method="POST" action="{{ route('admin.pendaftar.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="berkas" class="label-filter d-block">Berkas CSV / XLSX (maks 10 MB)</label>
                    <input type="file" name="berkas" id="berkas" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                </div>
                <button type="submit" class="btn btn-utama btn-sm px-4">
                    <i class="bi bi-upload me-1"></i> Unggah &amp; proses
                </button>
                <a href="{{ route('admin.pendaftar.import.template') }}" class="btn btn-outline-secondary btn-sm ms-2">
                    <i class="bi bi-download me-1"></i> Unduh template
                </a>
            </form>

            <div class="banner-info mt-4">
                <i class="bi bi-lightbulb mt-1"></i>
                <div>
                    Kolom dicocokkan berdasarkan <strong>kata kunci pada judul kolom</strong>, jadi urutan kolom
                    bebas. Satu tim dianggap sama bila <em>email + nama tim</em> sama — import ulang memperbarui,
                    bukan menggandakan. Kolom <em>Kategori</em> &amp; <em>Bidang</em> yang belum terdaftar dibuat otomatis.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="panel p-4">
            <h2 class="h6 mb-3">Riwayat import</h2>
            @forelse ($riwayat as $log)
                <div class="py-2 {{ ! $loop->last ? 'border-bottom' : '' }}" style="border-color: var(--garis) !important;">
                    <div class="d-flex justify-content-between small">
                        <span class="fw-semibold text-truncate" style="max-width: 60%;">{{ $log->nama_file }}</span>
                        <span style="color: var(--redup);">{{ $log->created_at->format('d/m/y H:i') }}</span>
                    </div>
                    <div class="small" style="color: var(--redup);">
                        <span class="tag tag-hijau">{{ $log->jumlah_berhasil }} berhasil</span>
                        @if ($log->jumlah_gagal)
                            <span class="tag tag-merah">{{ $log->jumlah_gagal }} gagal</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="small mb-0" style="color: var(--redup);">Belum ada import.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
