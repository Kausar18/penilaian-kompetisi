<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorPenilaian;
use App\Support\HitungUlangPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RubrikIndikatorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, [
            'kategori_penilaian_id' => ['required', 'exists:kategori_penilaian,id'],
        ]);

        $data['urutan'] = (int) IndikatorPenilaian::where('kategori_penilaian_id', $data['kategori_penilaian_id'])->max('urutan') + 1;
        $data['aktif'] = true;

        IndikatorPenilaian::create($data);
        HitungUlangPenilaian::semua();

        return back()->with('sukses', 'Indikator ditambahkan. Penilaian yang sudah ada kembali jadi draft karena indikator baru belum diberi skor.');
    }

    public function update(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['aktif'] = $request->boolean('aktif');

        $indikatorPenilaian->update($data);
        HitungUlangPenilaian::semua();

        return back()->with('sukses', 'Indikator diperbarui. Nilai peserta dihitung ulang.');
    }

    public function destroy(IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $nama = $indikatorPenilaian->nama;
        $indikatorPenilaian->delete();
        HitungUlangPenilaian::semua();

        return back()->with('sukses', "Indikator \"{$nama}\" dihapus. Nilai peserta dihitung ulang.");
    }

    private function validasi(Request $request, array $tambahan = []): array
    {
        return $request->validate($tambahan + [
            'nama' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'bobot' => ['required', 'numeric', 'min:0', 'max:100'],
            'peran_khusus' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
