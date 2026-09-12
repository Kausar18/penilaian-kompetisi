@extends('layouts.admin')

@section('judul', 'Pilih Sheet')
@section('subjudul', 'Berkas punya lebih dari satu sheet — pilih yang berisi data pendaftar')

@section('konten')

<div class="panel p-4" style="max-width: 520px;">
    <form method="POST" action="{{ route('admin.pendaftar.import.proses') }}">
        @csrf
        <input type="hidden" name="path_sementara" value="{{ $pathSementara }}">
        <input type="hidden" name="nama_asli" value="{{ $namaAsli }}">
        <input type="hidden" name="ekstensi" value="{{ $ekstensi }}">

        <label class="label-filter d-block" for="sheet">Sheet</label>
        <select name="sheet" id="sheet" class="form-select mb-3">
            @foreach ($daftarSheet as $sheet)
                <option value="{{ $sheet }}">{{ $sheet }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-utama btn-sm px-4">Proses sheet ini</button>
        <a href="{{ route('admin.pendaftar.import') }}" class="btn btn-outline-secondary btn-sm ms-2">Batal</a>
    </form>
</div>

@endsection
