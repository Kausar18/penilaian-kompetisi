@if (session('sukses'))
    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 js-flash-auto" role="status">
        <i class="bi bi-check-circle"></i>
        <span class="small">{{ session('sukses') }}</span>
    </div>
@endif

@if (session('gagal'))
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 js-flash-auto" role="alert">
        <i class="bi bi-exclamation-circle"></i>
        <span class="small">{{ session('gagal') }}</span>
    </div>
@endif

@if (session('catatanGagal') && count(session('catatanGagal')))
    <div class="alert alert-warning py-2 px-3" role="alert">
        <div class="small fw-semibold mb-1">Sebagian baris tidak terproses:</div>
        <ul class="small mb-0 ps-3">
            @foreach (session('catatanGagal') as $catatan)
                <li>{{ $catatan }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger py-2 px-3" role="alert">
        <div class="small fw-semibold mb-1">Data belum bisa disimpan:</div>
        <ul class="small mb-0 ps-3">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif
