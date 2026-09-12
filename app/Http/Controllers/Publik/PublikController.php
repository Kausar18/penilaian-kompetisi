<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\BidangKompetisi;
use App\Models\Pendaftar;
use App\Models\PengaturanSitus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik — tanpa login.
 *
 * Aturan yang dipegang di sini:
 * 1. Kolom yang boleh keluar dibatasi eksplisit lewat KOLOM_AMAN. Email, no WA,
 *    catatan reviewer, dan nilai TIDAK pernah dikirim ke view publik.
 * 2. Yang diumumkan hanya peserta yang LOLOS. Yang tidak lolos tidak pernah
 *    ditampilkan ke publik.
 * 3. Pengumuman baru muncul kalau panitia menyalakannya di menu Halaman Publik,
 *    bukan otomatis saat reviewer menekan "Lolos".
 */
class PublikController extends Controller
{
    /** Satu-satunya kolom pendaftar yang boleh tampil di halaman publik. */
    private const KOLOM_AMAN = [
        'pendaftar.id', 'pendaftar.nama_tim', 'pendaftar.judul_inovasi',
        'pendaftar.kota', 'pendaftar.provinsi',
        'pendaftar.kategori_peserta_id', 'pendaftar.bidang_kompetisi_id',
    ];

    public function beranda(): View
    {
        $situs = PengaturanSitus::ambil();

        if (! $situs->situs_aktif) {
            return view('publik.tutup', ['situs' => $situs]);
        }

        return view('publik.beranda', [
            'situs' => $situs,
            'bidang' => BidangKompetisi::orderBy('urutan')->get(),
            'angka' => $this->angkaRingkas($situs),
            'timeline' => $situs->timelineRapi(),
        ]);
    }

    public function pengumuman(Request $request): View
    {
        $situs = PengaturanSitus::ambil();

        if (! $situs->situs_aktif) {
            return view('publik.tutup', ['situs' => $situs]);
        }

        // Tab boleh dibuka walau tahapnya belum diumumkan — yang tampil halaman
        // keterangan, bukan daftar. Datanya tetap tidak pernah ikut dikirim:
        // $daftar hanya diisi kalau tahap itu sudah diumumkan panitia.
        $tab = $request->input('tahap') === 'finalis' ? 'finalis' : 'administrasi';

        $diumumkan = $tab === 'finalis' ? $situs->umumkan_finalis : $situs->umumkan_administrasi;

        $daftar = $diumumkan
            ? $this->daftarLolos($tab, $request)->paginate(24)->withQueryString()
            : null;

        return view('publik.pengumuman', [
            'situs' => $situs,
            'tab' => $tab,
            'diumumkan' => $diumumkan,
            'daftar' => $daftar,
            'bidang' => BidangKompetisi::orderBy('urutan')->get(),
            'jumlah' => [
                'administrasi' => $situs->umumkan_administrasi ? $this->daftarLolos('administrasi')->count() : 0,
                'finalis' => $situs->umumkan_finalis ? $this->daftarLolos('finalis')->count() : 0,
            ],
        ]);
    }

    /**
     * Peserta yang lolos tahap tertentu.
     *
     * - administrasi: punya verifikasi dengan hasil `lolos`
     * - finalis: sudah lolos administrasi DAN penilaian selesai dengan rekomendasi `lolos`
     */
    private function daftarLolos(string $tahap, ?Request $request = null): Builder
    {
        $query = Pendaftar::query()
            ->select(self::KOLOM_AMAN)
            ->with(['kategoriPeserta:id,nama', 'bidangKompetisi:id,nama'])
            ->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos'));

        if ($tahap === 'finalis') {
            $query->whereHas('penilaian', fn ($p) => $p
                ->where('rekomendasi', 'lolos')
                ->whereNotNull('dinilai_at'));
        }

        if ($request) {
            $query->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $kata = $request->input('q');
                // sengaja tidak mencari lewat email — kolom itu tidak publik
                $w->where('nama_tim', 'like', "%{$kata}%")
                    ->orWhere('judul_inovasi', 'like', "%{$kata}%");
            }));

            $query->bidang($request->input('bidang'));
        }

        return $query->orderBy('nama_tim');
    }

    /** Angka untuk beranda. Jumlah yang lolos ikut gerbang pengumuman. */
    private function angkaRingkas(PengaturanSitus $situs): array
    {
        if (! $situs->tampilkan_statistik) {
            return [];
        }

        $angka = [
            ['label' => 'Startup mendaftar', 'nilai' => Pendaftar::count(), 'ikon' => 'bi-people'],
            ['label' => 'Bidang fokus', 'nilai' => BidangKompetisi::count(), 'ikon' => 'bi-grid'],
        ];

        // jumlah yang lolos baru boleh tampil setelah diumumkan — kalau tidak, bocor
        if ($situs->umumkan_administrasi) {
            $angka[] = [
                'label' => 'Lolos administrasi',
                'nilai' => $this->daftarLolos('administrasi')->count(),
                'ikon' => 'bi-clipboard-check',
            ];
        }

        if ($situs->umumkan_finalis) {
            $angka[] = [
                'label' => 'Finalis',
                'nilai' => $this->daftarLolos('finalis')->count(),
                'ikon' => 'bi-trophy',
            ];
        }

        return $angka;
    }
}
