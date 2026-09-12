<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PenilaianRequest;
use App\Models\BidangKompetisi;
use App\Models\IndikatorPenilaian;
use App\Models\KategoriPenilaian;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;
use App\Support\Csv;
use App\Support\PerhitunganNilai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenilaianController extends Controller
{
    private ?int $jmlIndikatorAktif = null;

    private ?PengaturanPenilaian $pengaturan = null;

    public function index(Request $request): View
    {
        $pendaftar = $this->query($request)
            ->with([
                'kategoriPeserta',
                'bidangKompetisi',
                'reviewer',
                'penilaian' => fn ($q) => $q
                    ->withCount(['detail as skor_terisi' => fn ($d) => $d->whereNotNull('skor')]),
            ])
            ->orderBy('nama_tim')
            ->paginate(20)
            ->withQueryString();

        return view('admin.penilaian.index', [
            'pendaftar' => $pendaftar,
            'jmlIndikator' => $this->jmlIndikatorAktif(),
            'adalahReviewer' => $request->user()->isReviewer(),
            'pengaturan' => $this->pengaturan(),
            'kartu' => $this->kartu($request),
            'daftarKategori' => KategoriPeserta::orderBy('urutan')->get(),
            'daftarBidang' => BidangKompetisi::orderBy('urutan')->get(),
        ]);
    }

    /** Reviewer hanya boleh membuka penilaian pendaftar yang ditugaskan kepadanya. */
    private function pastikanBolehMenilai(Request $request, Pendaftar $pendaftar): void
    {
        $user = $request->user();

        abort_if(
            $user->isReviewer() && $pendaftar->reviewer_id !== $user->id,
            403,
            'Pendaftar ini tidak ditugaskan kepada Anda.'
        );
    }

    private function jmlIndikatorAktif(): int
    {
        return $this->jmlIndikatorAktif ??= IndikatorPenilaian::aktif()->count();
    }

    private function pengaturan(): PengaturanPenilaian
    {
        return $this->pengaturan ??= PengaturanPenilaian::aktif();
    }

    public function edit(Request $request, Pendaftar $pendaftar): View
    {
        $this->pastikanBolehMenilai($request, $pendaftar);

        $pendaftar->load(['kategoriPeserta', 'bidangKompetisi', 'anggotaTim']);

        $penilaian = $pendaftar->penilaian()->firstOrNew([]);

        $kategori = KategoriPenilaian::with(['indikatorAktif' => fn ($q) => $q->orderBy('urutan')->orderBy('id')])
            ->orderBy('urutan')
            ->get();

        $skorTersimpan = $penilaian->exists
            ? $penilaian->detail()->pluck('skor', 'indikator_penilaian_id')->all()
            : [];

        return view('admin.penilaian.edit', [
            'p' => $pendaftar,
            'penilaian' => $penilaian,
            'kategori' => $kategori,
            'skorTersimpan' => $skorTersimpan,
            'pengaturan' => $this->pengaturan(),
        ]);
    }

    public function update(PenilaianRequest $request, Pendaftar $pendaftar): RedirectResponse
    {
        $this->pastikanBolehMenilai($request, $pendaftar);

        $indikator = IndikatorPenilaian::aktif()->with('kategoriPenilaian')->get();
        $skorInput = $request->validated('skor', []);

        // hanya skor untuk indikator aktif yang diproses
        $skorMap = [];
        foreach ($indikator as $ind) {
            $nilai = $skorInput[$ind->id] ?? null;
            $skorMap[$ind->id] = ($nilai === null || $nilai === '') ? null : (int) $nilai;
        }

        $hitung = PerhitunganNilai::hitung($skorMap, $indikator, $this->pengaturan());
        $rekomendasi = $request->validated('rekomendasi') ?: null;
        $lengkap = $hitung['lengkap'] && $rekomendasi !== null;

        DB::transaction(function () use ($pendaftar, $request, $skorMap, $hitung, $rekomendasi, $lengkap) {
            $penilaian = Penilaian::updateOrCreate(
                ['pendaftar_id' => $pendaftar->id],
                [
                    'reviewer_id' => $request->user()->id,
                    'rekomendasi' => $rekomendasi,
                    'catatan_reviewer' => $request->validated('catatan_reviewer'),
                    'catatan_rab' => $request->validated('catatan_rab'),
                    'rekomendasi_anggaran' => $request->validated('rekomendasi_anggaran'),
                    'nilai_final' => $hitung['nilai_final'],
                    'nilai_mentah' => $hitung['nilai_mentah'],
                    'dinilai_at' => $lengkap ? now() : null,
                ],
            );

            foreach ($skorMap as $indikatorId => $skor) {
                $penilaian->detail()->updateOrCreate(
                    ['indikator_penilaian_id' => $indikatorId],
                    ['skor' => $skor],
                );
            }
        });

        $maks = $this->pengaturan()->nilaiMaksimum(
            (float) $indikator->sum('bobot')
        );

        $pesan = $lengkap
            ? "Penilaian \"{$pendaftar->nama_tim}\" tersimpan & ditandai selesai (nilai akhir {$hitung['nilai_final']}/{$maks})."
            : "Penilaian \"{$pendaftar->nama_tim}\" tersimpan sebagai draft (belum lengkap).";

        return redirect()->route('admin.penilaian.index')->with('sukses', $pesan);
    }

    public function export(Request $request): StreamedResponse
    {
        $baris = $this->query($request)
            ->with(['kategoriPeserta', 'bidangKompetisi', 'reviewer', 'penilaian'])
            ->orderBy('nama_tim')
            ->lazy()
            ->map(function (Pendaftar $p) {
                $n = $p->penilaian;

                return [
                    $p->kategoriPeserta?->nama,
                    $p->nama_tim,
                    $p->judul_inovasi,
                    $p->nama_ketua,
                    $p->bidangKompetisi?->nama,
                    $p->status,
                    $n?->status_penilaian ?? 'belum',
                    $n?->nilai_final,
                    $n?->nilai_mentah,
                    $n ? ($n->rekomendasi_label ?? '') : '',
                    $p->reviewer?->name,
                    optional($n?->dinilai_at)->format('Y-m-d H:i'),
                    $n?->catatan_reviewer,
                ];
            });

        return Csv::unduh('penilaian', [
            'Kategori', 'Tim/Usaha', 'Judul Inovasi', 'Ketua', 'Bidang', 'Status Admin',
            'Status Penilaian', 'Nilai Akhir', 'Nilai Mentah', 'Rekomendasi', 'Reviewer',
            'Tanggal Dinilai', 'Catatan Reviewer',
        ], $baris);
    }

    /** Query + filter bersama index & export. */
    private function query(Request $request)
    {
        $status = $request->input('status_nilai');
        $jml = $this->jmlIndikatorAktif();
        $adaSkor = fn ($d) => $d->whereNotNull('skor');
        $user = $request->user();

        return Pendaftar::query()
            // hanya peserta yang LOLOS verifikasi administrasi (tahap 1) yang boleh dinilai
            ->when($this->pengaturan()->hanya_terverifikasi, fn ($q) => $q
                ->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos')))
            ->when($user?->isReviewer(), fn ($q) => $q->ditugaskanKe($user->id))
            ->cari($request->input('q'))
            ->kategori($request->input('kategori'))
            ->bidang($request->input('bidang'))
            // Belum Dinilai: belum ada baris penilaian, atau ada tapi tanpa satu skor pun
            ->when($status === 'belum', fn ($q) => $q->where(fn ($w) => $w
                ->whereDoesntHave('penilaian')
                ->orWhereHas('penilaian', fn ($p) => $p->whereDoesntHave('detail', $adaSkor))
            ))
            // Nilai Belum Lengkap: sudah ada sebagian skor, belum semua indikator, belum ada rekomendasi
            ->when($status === 'belum_lengkap', fn ($q) => $q->whereHas('penilaian', fn ($p) => $p
                ->whereNull('rekomendasi')
                ->whereHas('detail', $adaSkor)
                ->whereHas('detail', $adaSkor, '<', $jml)
            ))
            // Draft: semua indikator sudah diberi skor, tapi rekomendasi belum diputuskan
            ->when($status === 'draft', fn ($q) => $q->whereHas('penilaian', fn ($p) => $p
                ->whereNull('rekomendasi')
                ->whereHas('detail', $adaSkor, '>=', max(1, $jml))
            ))
            ->when($status === 'lolos', fn ($q) => $q->whereHas('penilaian', fn ($p) => $p->where('rekomendasi', 'lolos')))
            ->when($status === 'tidak_lolos', fn ($q) => $q->whereHas('penilaian', fn ($p) => $p->where('rekomendasi', 'tidak_lolos')));
    }

    private function kartu(Request $request): array
    {
        $user = $request->user();
        $reviewerId = $user?->isReviewer() ? $user->id : null;

        $gating = $this->pengaturan()->hanya_terverifikasi;

        $lolos = fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos'));

        $pendaftarQ = fn () => Pendaftar::query()
            ->when($gating, $lolos)
            ->when($reviewerId, fn ($q) => $q->where('reviewer_id', $reviewerId));
        $penilaianQ = fn () => Penilaian::query()
            ->whereHas('pendaftar', fn ($p) => $p
                ->when($gating, $lolos)
                ->when($reviewerId, fn ($w) => $w->where('reviewer_id', $reviewerId)));

        $total = $pendaftarQ()->count();
        $selesai = $penilaianQ()->whereNotNull('dinilai_at')->count();
        $sebagian = $penilaianQ()->whereNull('dinilai_at')
            ->whereHas('detail', fn ($d) => $d->whereNotNull('skor'))
            ->count();

        return [
            'total' => $total,
            'selesai' => $selesai,
            'sebagian' => $sebagian,
            'belum' => max(0, $total - $selesai - $sebagian),
        ];
    }
}
