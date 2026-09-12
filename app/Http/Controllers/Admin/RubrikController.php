<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriPenilaian;
use App\Models\PengaturanPenilaian;
use App\Support\HitungUlangPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RubrikController extends Controller
{
    public function index(): View
    {
        $kategori = KategoriPenilaian::with(['indikator' => fn ($q) => $q->orderBy('urutan')->orderBy('id')])
            ->orderBy('urutan')
            ->get();

        return view('admin.rubrik.index', [
            'kategori' => $kategori,
            'totalBobot' => (float) $kategori->flatMap->indikator->where('aktif', true)->sum('bobot'),
            'totalPersen' => (int) $kategori->sum('bobot_persen'),
            'pengaturan' => PengaturanPenilaian::aktif(),
        ]);
    }

    /** Simpan skala nilai, rumus, dan gating status verifikasi. */
    public function simpanPengaturan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preset' => ['required', Rule::in(array_keys(PengaturanPenilaian::PRESET))],
            'rumus' => ['required', Rule::in(array_keys(PengaturanPenilaian::RUMUS))],
            'hanya_terverifikasi' => ['nullable', 'boolean'],
        ]);

        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET[$data['preset']]['skala'],
            'rumus' => $data['rumus'],
            'hanya_terverifikasi' => $request->boolean('hanya_terverifikasi'),
        ]);

        $jml = HitungUlangPenilaian::semua();

        return back()->with('sukses', "Pengaturan penilaian disimpan. {$jml} penilaian dihitung ulang memakai aturan baru.");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:kategori_penilaian,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'bobot_persen' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $data['kode'] = strtoupper($data['kode']);
        $data['urutan'] = (int) KategoriPenilaian::max('urutan') + 1;
        KategoriPenilaian::create($data);

        return back()->with('sukses', 'Kelompok penilaian ditambahkan.');
    }

    public function update(Request $request, KategoriPenilaian $kategoriPenilaian): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('kategori_penilaian', 'kode')->ignore($kategoriPenilaian->id)],
            'nama' => ['required', 'string', 'max:100'],
            'bobot_persen' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $data['kode'] = strtoupper($data['kode']);
        $kategoriPenilaian->update($data);
        HitungUlangPenilaian::semua();

        return back()->with('sukses', 'Kelompok penilaian diperbarui.');
    }

    public function destroy(KategoriPenilaian $kategoriPenilaian): RedirectResponse
    {
        $nama = $kategoriPenilaian->nama;
        $kategoriPenilaian->delete();
        HitungUlangPenilaian::semua();

        return back()->with('sukses', "Kelompok \"{$nama}\" beserta indikatornya dihapus. Nilai peserta dihitung ulang.");
    }
}
