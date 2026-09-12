@extends('layouts.tamu')

@section('judul', 'Buat Admin Pertama')

@section('konten')
<div class="panel p-4 p-md-5">
    <h1 class="h5 mb-1">Buat akun admin pertama</h1>
    <p class="small mb-4" style="color: var(--redup);">
        Halaman sekali-pakai. Kosongkan <code>SETUP_TOKEN</code> di <code>.env</code> setelah selesai.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3">
            <ul class="small mb-0 ps-3">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('setup.admin.store', $token) }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="label-filter">Nama</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label for="username" class="label-filter">Nama pengguna</label>
            <input type="text" name="username" id="username" class="form-control"
                   value="{{ old('username') }}" autocomplete="username" required>
        </div>

        <div class="mb-3">
            <label for="password" class="label-filter">Kata sandi</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="label-filter">Ulangi kata sandi</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-utama w-100">Buat akun</button>
    </form>
</div>
@endsection
