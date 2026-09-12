<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\PengaturanSitus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan halaman publik (khusus admin).
 *
 * Di sinilah panitia memutuskan KAPAN hasil seleksi boleh dilihat umum —
 * hasil verifikasi tidak pernah tayang otomatis.
 */
class PengaturanSitusController extends Controller
{
    public function index(): View
    {
        $situs = PengaturanSitus::ambil();

        $lolosAdm = Pendaftar::whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos'));

        return view('admin.situs.index', [
            'situs' => $situs,
            'timeline' => $situs->timelineRapi(),
            'pratinjau' => [
                'lolos_administrasi' => (clone $lolosAdm)->count(),
                'finalis' => (clone $lolosAdm)
                    ->whereHas('penilaian', fn ($p) => $p->where('rekomendasi', 'lolos')->whereNotNull('dinilai_at'))
                    ->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // panitia biasanya mengetik alamat polos ("ipb.ipb.ac.id"); tanpa ini
        // validasi `url` menolaknya, padahal maksudnya sudah jelas
        if (filled($situs = $request->input('situs_lembaga')) && ! preg_match('#^https?://#i', $situs)) {
            $request->merge(['situs_lembaga' => 'https://'.ltrim($situs, '/')]);
        }

        $data = $request->validate([
            'situs_aktif' => ['nullable', 'boolean'],
            'judul' => ['required', 'string', 'max:150'],
            'subjudul' => ['nullable', 'string', 'max:250'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'penyelenggara' => ['nullable', 'string', 'max:200'],

            'umumkan_administrasi' => ['nullable', 'boolean'],
            'umumkan_finalis' => ['nullable', 'boolean'],
            'catatan_pengumuman' => ['nullable', 'string', 'max:1000'],
            'tampilkan_statistik' => ['nullable', 'boolean'],

            'tampilkan_tanggal_jadwal' => ['nullable', 'boolean'],
            'timeline' => ['nullable', 'array', 'max:12'],
            'timeline.*.tahap' => ['nullable', 'string', 'max:120'],
            'timeline.*.tanggal' => ['nullable', 'string', 'max:60'],
            'timeline.*.selesai' => ['nullable', 'boolean'],

            'kontak_email' => ['nullable', 'email', 'max:150'],
            'kontak_wa' => ['nullable', 'string', 'max:40'],
            'instagram' => ['nullable', 'string', 'max:150'],
            'situs_lembaga' => ['nullable', 'url', 'max:200'],
        ]);

        foreach (['situs_aktif', 'umumkan_administrasi', 'umumkan_finalis', 'tampilkan_statistik', 'tampilkan_tanggal_jadwal'] as $saklar) {
            $data[$saklar] = $request->boolean($saklar);
        }

        // buang baris timeline yang tahapnya kosong
        $data['timeline'] = collect($data['timeline'] ?? [])
            ->filter(fn ($t) => filled($t['tahap'] ?? null))
            ->map(fn ($t) => [
                'tahap' => $t['tahap'],
                'tanggal' => $t['tanggal'] ?? '',
                'selesai' => (bool) ($t['selesai'] ?? false),
            ])
            ->values()
            ->all();

        PengaturanSitus::ambil()->update($data);

        return back()->with('sukses', 'Pengaturan halaman publik disimpan.');
    }
}
