<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemVerifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Konfigurasi butir checklist Form Seleksi Administrasi (khusus admin). */
class ItemVerifikasiController extends Controller
{
    public function index(): View
    {
        return view('admin.verifikasi.form', [
            'kelompok' => ItemVerifikasi::terurut()->get()->groupBy('kelompok'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $data['aktif'] = true;
        $data['urutan'] = (int) ItemVerifikasi::where('kelompok', $data['kelompok'])->max('urutan') + 1;

        ItemVerifikasi::create($data);

        return back()->with('sukses', 'Butir checklist ditambahkan.');
    }

    public function update(Request $request, ItemVerifikasi $itemVerifikasi): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['aktif'] = $request->boolean('aktif');

        $itemVerifikasi->update($data);

        return back()->with('sukses', 'Butir checklist diperbarui.');
    }

    public function destroy(ItemVerifikasi $itemVerifikasi): RedirectResponse
    {
        $nama = $itemVerifikasi->nama;
        $itemVerifikasi->delete();

        return back()->with('sukses', "Butir \"{$nama}\" dihapus.");
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'kelompok' => ['required', 'string', 'max:150'],
            'nama' => ['required', 'string', 'max:400'],
        ], [], [
            'kelompok' => 'nama kelompok',
            'nama' => 'butir checklist',
        ]);
    }
}
