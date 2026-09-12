<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKompetisi;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Penugasan reviewer ke pendaftar (Opsi A: satu pendaftar = satu reviewer).
 * Admin memilih reviewer per baris; reviewer hanya bisa menilai pendaftar
 * yang ditugaskan ke dirinya.
 *
 * Populasinya HARUS sama dengan menu Penilaian: kalau pengaturan
 * `hanya_terverifikasi` aktif, hanya peserta yang lolos verifikasi administrasi
 * yang boleh ditugaskan. Tanpa ini admin bisa menugaskan peserta yang tidak
 * akan pernah muncul di halaman Penilaian.
 */
class PenugasanController extends Controller
{
    private ?PengaturanPenilaian $pengaturan = null;

    private function pengaturan(): PengaturanPenilaian
    {
        return $this->pengaturan ??= PengaturanPenilaian::aktif();
    }

    /** Peserta yang boleh ditugaskan — gerbangnya sama persis dengan Penilaian. */
    private function query()
    {
        return Pendaftar::query()->when(
            $this->pengaturan()->hanya_terverifikasi,
            fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos')),
        );
    }

    public function index(Request $request): View
    {
        $tugas = $request->input('tugas'); // 'sudah' | 'belum' | null

        $pendaftar = $this->query()
            ->cari($request->input('q'))
            ->kategori($request->input('kategori'))
            ->bidang($request->input('bidang'))
            ->when($tugas === 'sudah', fn ($q) => $q->whereNotNull('reviewer_id'))
            ->when($tugas === 'belum', fn ($q) => $q->whereNull('reviewer_id'))
            ->with(['kategoriPeserta', 'bidangKompetisi', 'reviewer', 'penilaian'])
            ->orderBy('nama_tim')
            ->paginate(25)
            ->withQueryString();

        $daftarReviewer = User::where('peran', 'reviewer')->orderBy('name')->get();

        return view('admin.penugasan.index', [
            'pendaftar' => $pendaftar,
            'daftarReviewer' => $daftarReviewer,
            'bisaUbah' => $request->user()->isAdmin(),
            'daftarKategori' => KategoriPeserta::orderBy('urutan')->get(),
            'daftarBidang' => BidangKompetisi::orderBy('urutan')->get(),
            'hanyaLolos' => $this->pengaturan()->hanya_terverifikasi,
            'kartu' => [
                'total' => $this->query()->count(),
                'ditugaskan' => $this->query()->whereNotNull('reviewer_id')->count(),
                'belum' => $this->query()->whereNull('reviewer_id')->count(),
                'reviewer' => $daftarReviewer->count(),
            ],
            'bebanReviewer' => $this->query()
                ->selectRaw('reviewer_id, count(*) as jml')
                ->whereNotNull('reviewer_id')
                ->groupBy('reviewer_id')
                ->pluck('jml', 'reviewer_id'),
        ]);
    }

    public function update(Request $request, Pendaftar $pendaftar): RedirectResponse
    {
        // jangan sampai peserta di luar gerbang ikut ditugaskan lewat request langsung
        abort_unless(
            $this->query()->whereKey($pendaftar->getKey())->exists(),
            403,
            'Peserta ini belum lolos verifikasi administrasi.',
        );

        $data = $request->validate([
            'reviewer_id' => ['nullable', Rule::exists('users', 'id')->where('peran', 'reviewer')],
        ]);

        $pendaftar->update(['reviewer_id' => $data['reviewer_id'] ?: null]);

        $pesan = $data['reviewer_id']
            ? "\"{$pendaftar->nama_tim}\" ditugaskan ke {$pendaftar->reviewer->name}."
            : "Penugasan \"{$pendaftar->nama_tim}\" dikosongkan.";

        return back()->with('sukses', $pesan);
    }
}
