<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKompetisi;
use App\Models\KategoriPenilaian;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    private ?PengaturanPenilaian $pengaturan = null;

    private const SORT = [
        'nilai_desc' => ['Nilai akhir tertinggi', 'nilai_final', 'desc'],
        'nilai_asc' => ['Nilai akhir terendah', 'nilai_final', 'asc'],
        'update_desc' => ['Terakhir diperbarui', 'updated_at', 'desc'],
    ];

    public function index(Request $request): View
    {
        $sortKey = array_key_exists($request->input('sort'), self::SORT) ? $request->input('sort') : 'nilai_desc';
        [, $sortKolom, $sortArah] = self::SORT[$sortKey];

        $rows = $this->query($request)
            ->orderByRaw('penilaian.dinilai_at IS NULL')
            ->orderBy("penilaian.{$sortKolom}", $sortArah)
            ->paginate(25)
            ->withQueryString();

        $rows->getCollection()->transform(function (Pendaftar $p) {
            $p->setAttribute('subtotal', $this->subtotal($p->penilaian));

            return $p;
        });

        return view('admin.rekap.index', [
            'rows' => $rows,
            'kartu' => $this->kartu(),
            'kelompok' => KategoriPenilaian::orderBy('urutan')->get(),
            'pengaturan' => $this->pengaturan(),
            'daftarKategori' => KategoriPeserta::orderBy('urutan')->get(),
            'daftarBidang' => BidangKompetisi::orderBy('urutan')->get(),
            'daftarReviewer' => User::where('peran', 'reviewer')->orderBy('name')->get(),
            'sortKey' => $sortKey,
            'sortOpsi' => collect(self::SORT)->map(fn ($v) => $v[0]),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $kelompok = KategoriPenilaian::orderBy('urutan')->get();

        $baris = $this->query($request)
            ->orderByDesc('penilaian.nilai_final')
            ->lazy()
            ->map(function (Pendaftar $p) use ($kelompok) {
                $sub = $this->subtotal($p->penilaian);
                $n = $p->penilaian;

                return array_merge([
                    $p->nama_tim,
                    $p->judul_inovasi,
                    $p->kategoriPeserta?->nama,
                    $p->nama_ketua,
                    $p->bidangKompetisi?->nama,
                    $p->reviewer?->name,
                ], $kelompok->map(fn ($k) => $sub[$k->kode] ?? 0)->all(), [
                    $n?->nilai_final,
                    $n?->rekomendasi_label ?? '',
                    $n?->catatan_rab,
                    $n?->rekomendasi_anggaran,
                    $n?->catatan_reviewer,
                    optional($n?->updated_at)->format('Y-m-d H:i'),
                ]);
            });

        $header = array_merge(
            ['Tim/Usaha', 'Judul Inovasi', 'Kategori', 'Ketua', 'Bidang', 'Reviewer'],
            $kelompok->map(fn ($k) => $k->nama)->all(),
            ['Nilai Akhir', 'Rekomendasi', 'Komentar Verifikasi RAB', 'Rekomendasi Anggaran', 'Kesimpulan', 'Tanggal Update'],
        );

        return Csv::unduh('rekap-nilai', $header, $baris);
    }

    /** Pendaftar yang punya penilaian + filter. */
    private function query(Request $request)
    {
        return Pendaftar::query()
            ->join('penilaian', 'penilaian.pendaftar_id', '=', 'pendaftar.id')
            // ranking hanya untuk peserta yang lolos verifikasi administrasi
            ->when($this->pengaturan()->hanya_terverifikasi, fn ($q) => $q
                ->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos')))
            ->with(['kategoriPeserta', 'bidangKompetisi', 'reviewer', 'penilaian.detail.indikator.kategoriPenilaian'])
            ->select('pendaftar.*')
            ->cari($request->input('q'))
            ->kategori($request->input('kategori'))
            ->bidang($request->input('bidang'))
            ->when($request->filled('reviewer'), fn ($q) => $q->where('pendaftar.reviewer_id', $request->input('reviewer')))
            ->when($request->filled('rekomendasi'), fn ($q) => $q->where('penilaian.rekomendasi', $request->input('rekomendasi')))
            ->when($request->input('data') === 'selesai', fn ($q) => $q->whereNotNull('penilaian.dinilai_at'));
    }

    private function pengaturan(): PengaturanPenilaian
    {
        return $this->pengaturan ??= PengaturanPenilaian::aktif();
    }

    /** Subtotal tertimbang per kelompok penilaian, mengikuti rumus di pengaturan. */
    private function subtotal(?Penilaian $penilaian): array
    {
        if (! $penilaian) {
            return [];
        }

        $pengaturan = $this->pengaturan();
        $maksNilai = $pengaturan->maksNilai();
        $mentah = $pengaturan->rumus === 'mentah';

        $hasil = [];
        foreach ($penilaian->detail as $d) {
            if ($d->skor === null || ! $d->indikator || ! $d->indikator->kategoriPenilaian) {
                continue;
            }

            $bobot = (float) $d->indikator->bobot;
            $nilai = $mentah ? $d->skor * $bobot : $d->skor / $maksNilai * $bobot;

            $kode = $d->indikator->kategoriPenilaian->kode;
            $hasil[$kode] = round(($hasil[$kode] ?? 0) + $nilai, 2);
        }

        return $hasil;
    }

    private function kartu(): array
    {
        $gating = $this->pengaturan()->hanya_terverifikasi;
        $lolosAdm = fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos'));

        $pendaftarQ = fn () => Pendaftar::query()->when($gating, $lolosAdm);
        $penilaianQ = fn () => Penilaian::query()
            ->when($gating, fn ($q) => $q->whereHas('pendaftar', $lolosAdm));

        return [
            'total' => $pendaftarQ()->count(),
            'dinilai' => $penilaianQ()->whereNotNull('dinilai_at')->count(),
            'rata' => round((float) $penilaianQ()->whereNotNull('dinilai_at')->avg('nilai_final'), 2),
            'lolos' => $penilaianQ()->where('rekomendasi', 'lolos')->count(),
        ];
    }
}
