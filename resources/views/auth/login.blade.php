@extends('layouts.tamu')

@section('judul', 'Masuk')
@section('lebar', '840px')

@section('konten')
<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-tex"></div>
        <div class="d-flex align-items-center gap-3">
            <span class="merek">PK</span>
            <div>
                <div class="fw-semibold">{{ config('app.name') }}</div>
                <div class="small" style="color: rgba(255,255,255,.7);">Panel Admin</div>
            </div>
        </div>

        <div>
            <h2>Sistem Penilaian<br>Kompetisi</h2>
            <p>Kelola pendaftar, penilaian rubrik berbobot, dan rekap ranking peserta dalam satu tempat.</p>
            <ul class="poin">
                <li><i class="bi bi-check-circle-fill"></i> Statistik &amp; tren pendaftaran</li>
                <li><i class="bi bi-check-circle-fill"></i> Import data Google Form</li>
                <li><i class="bi bi-check-circle-fill"></i> Rekap nilai &amp; export CSV</li>
            </ul>
        </div>

        <div class="small" style="color: rgba(255,255,255,.6);">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
    </div>

    <div class="auth-form">
        <h1 class="h4 fw-bold mb-1">Selamat datang kembali</h1>
        <p class="small mb-4" style="color: var(--redup);">Masuk untuk melanjutkan ke panel admin.</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>{{ $errors->first() }}
            </div>
        @endif

        @if (session('sukses'))
            <div class="alert alert-success py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i>{{ session('sukses') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="mb-3">
                <label for="username" class="label-filter">Username</label>
                <div class="input-ikon">
                    <i class="bi bi-person"></i>
                    <input type="text" name="username" id="username" class="form-control"
                           value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username"
                           required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="label-filter">Password</label>
                <div class="input-ikon">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Masukkan password" autocomplete="current-password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-utama w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
        </form>
    </div>
</div>
@endsection
