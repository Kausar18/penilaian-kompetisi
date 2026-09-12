<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;
use App\Services\StatistikPengunjung;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatistikController extends Controller
{
    public function index(StatistikPengunjung $pengunjung): View
    {
        return view('admin.statistik', [
            'ringkasan' => $this->ringkasan(),
            'tren' => $this->trenPendaftaran(),
            'perBidang' => $this->perBidang(),
            'statusVerifikasi' => $this->statusVerifikasi(),
            'perProvinsi' => $this->perProvinsi(),
            'statusPenilaian' => $this->statusPenilaian(),
            'pengunjung' => $pengunjung->ringkas(),
        ]);
    }

    private function ringkasan(): array
    {
        $perKategori = Pendaftar::query()
            ->join('kategori_peserta', 'pendaftar.kategori_peserta_id', '=', 'kategori_peserta.id')
            ->select('kategori_peserta.nama', DB::raw('COUNT(*) as jml'))
            ->groupBy('kategori_peserta.nama')
            ->pluck('jml', 'nama')
            ->all();

        return [
            'total' => Pendaftar::count(),
            'per_kategori' => $perKategori,
            'hari_ini' => Pendaftar::whereDate('tanggal_daftar', today())->count(),
            'sudah_dinilai' => $this->penilaianQuery()->whereNotNull('dinilai_at')->count(),
        ];
    }

    /**
     * Jumlah pendaftar per periode, mencakup SELURUH masa pendaftaran.
     *
     * Dulu dipaku "14 hari terakhir" — cocok untuk kompetisi yang sedang
     * berjalan, tapi menyesatkan untuk data arsip: pada Batch 1 (21 Feb - 9 Apr)
     * jendela itu hanya memuat 65 dari 150 pendaftar dan menyembunyikan
     * lonjakan 14-15 Maret.
     *
     * Butir grafik dijaga tetap sedikit dengan menyesuaikan satuannya:
     * harian (<= 60 hari), mingguan (<= 60 minggu), selebihnya bulanan.
     */
    private function trenPendaftaran(): array
    {
        $rentang = Pendaftar::selectRaw('MIN(tanggal_daftar) AS awal, MAX(tanggal_daftar) AS akhir')->first();

        if (blank($rentang?->awal)) {
            // belum ada pendaftar sama sekali: tampilkan 14 hari terakhir sebagai kerangka
            $hasil = [];
            for ($i = 13; $i >= 0; $i--) {
                $hasil[today()->subDays($i)->translatedFormat('d M')] = 0;
            }

            return $hasil;
        }

        $awal = Carbon::parse($rentang->awal)->startOfDay();

        // Berhenti di pendaftaran terakhir, bukan di hari ini. Kalau pendaftaran
        // masih berjalan, tanggal itu memang dekat hari ini; kalau ini data arsip,
        // grafik tidak jadi memanjang berbulan-bulan dengan nilai nol.
        $akhir = Carbon::parse($rentang->akhir)->startOfDay();

        $jumlahHari = $awal->diffInDays($akhir) + 1;

        [$satuan, $format] = match (true) {
            $jumlahHari <= 60 => ['hari', 'd M'],
            $jumlahHari <= 420 => ['minggu', 'd M'],
            default => ['bulan', 'M Y'],
        };

        $data = Pendaftar::query()
            ->whereNotNull('tanggal_daftar')
            ->selectRaw('DATE(tanggal_daftar) AS tgl, COUNT(*) AS jml')
            ->groupBy('tgl')
            ->pluck('jml', 'tgl')
            ->all();

        $hasil = [];
        $titik = match ($satuan) {
            'hari' => $awal->copy(),
            'minggu' => $awal->copy()->startOfWeek(),
            default => $awal->copy()->startOfMonth(),
        };

        while ($titik->lte($akhir)) {
            $sampai = match ($satuan) {
                'hari' => $titik->copy(),
                'minggu' => $titik->copy()->addDays(6),
                default => $titik->copy()->endOfMonth(),
            };

            $jml = 0;
            foreach ($data as $tgl => $n) {
                $t = Carbon::parse($tgl);
                if ($t->betweenIncluded($titik, $sampai)) {
                    $jml += (int) $n;
                }
            }

            $hasil[$titik->translatedFormat($format)] = $jml;

            $titik = match ($satuan) {
                'hari' => $titik->addDay(),
                'minggu' => $titik->addWeek(),
                default => $titik->addMonth(),
            };
        }

        return $hasil;
    }

    private function perBidang(): array
    {
        return Pendaftar::query()
            ->leftJoin('bidang_kompetisi', 'pendaftar.bidang_kompetisi_id', '=', 'bidang_kompetisi.id')
            ->select(DB::raw('COALESCE(bidang_kompetisi.nama, "Tanpa bidang") as nama'), DB::raw('COUNT(*) as jml'))
            ->groupBy('nama')
            ->orderByDesc('jml')
            ->pluck('jml', 'nama')
            ->all();
    }

    private function statusVerifikasi(): array
    {
        $hitung = Pendaftar::select('status', DB::raw('COUNT(*) as jml'))
            ->groupBy('status')
            ->pluck('jml', 'status')
            ->all();

        $hasil = [];
        foreach (Pendaftar::STATUS as $key => $label) {
            $hasil[$label] = (int) ($hitung[$key] ?? 0);
        }

        return $hasil;
    }

    private function perProvinsi(): array
    {
        return Pendaftar::query()
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->select('provinsi', DB::raw('COUNT(*) as jml'))
            ->groupBy('provinsi')
            ->orderByDesc('jml')
            ->limit(15)
            ->pluck('jml', 'provinsi')
            ->all();
    }

    /** Peserta yang berhak dinilai (lolos verifikasi administrasi, kalau gerbang aktif). */
    private function pendaftarQuery()
    {
        return Pendaftar::query()->when(
            PengaturanPenilaian::aktif()->hanya_terverifikasi,
            fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos')),
        );
    }

    private function penilaianQuery()
    {
        return Penilaian::query()->when(
            PengaturanPenilaian::aktif()->hanya_terverifikasi,
            fn ($q) => $q->whereHas('pendaftar', fn ($p) => $p
                ->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos'))),
        );
    }

    private function statusPenilaian(): array
    {
        // populasinya = peserta yang sudah masuk tahap penilaian, bukan seluruh pendaftar
        $total = $this->pendaftarQuery()->count();
        $selesai = $this->penilaianQuery()->whereNotNull('dinilai_at')->count();
        $sebagian = $this->penilaianQuery()->whereNull('dinilai_at')
            ->whereHas('detail', fn ($q) => $q->whereNotNull('skor'))
            ->count();

        return [
            'Belum dinilai' => max(0, $total - $selesai - $sebagian),
            'Sebagian' => $sebagian,
            'Selesai' => $selesai,
        ];
    }
}
