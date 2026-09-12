<?php

namespace App\Services;

use App\Models\Kunjungan;
use Illuminate\Support\Facades\DB;

/**
 * Statistik pengunjung halaman publik — dihitung dari tabel `kunjungan`.
 *
 * Catatan soal "unique": identitas pengunjung disimpan sebagai hash yang
 * berganti tiap hari (demi privasi, lihat migrasi `kunjungan`). Jadi angka
 * unique itu **unique per hari yang dijumlahkan** — orang yang datang di 3
 * hari berbeda terhitung 3, bukan 1. Ini pilihan sadar: tidak ada IP mentah
 * atau cookie pelacak yang disimpan.
 */
class StatistikPengunjung
{
    /** Nama yang lebih enak dibaca untuk path yang sudah dikenal. */
    private const LABEL = [
        '/' => 'Beranda (/)',
        '/pengumuman' => 'Pengumuman (/pengumuman)',
    ];

    /** Ringkasan singkat untuk kartu di halaman Statistik. */
    public function ringkas(): array
    {
        $hariIni = today()->toDateString();

        return [
            'total_views' => Kunjungan::count(),
            'total_unique' => Kunjungan::distinct()->count('pengunjung'),
            'views_hari_ini' => Kunjungan::where('tanggal', $hariIni)->count(),
            'unique_hari_ini' => Kunjungan::where('tanggal', $hariIni)->distinct()->count('pengunjung'),
        ];
    }

    /** Data lengkap untuk halaman Pengunjung. */
    public function lengkap(): array
    {
        $ringkas = $this->ringkas();
        $kemarin = Kunjungan::where('tanggal', today()->subDay()->toDateString())->count();

        return [
            ...$ringkas,
            'selisih_kemarin' => $ringkas['views_hari_ini'] - $kemarin,
            'tren' => $this->tren30Hari(),
            'per_jam' => $this->kunjunganPerJam(),
            'halaman_populer' => $this->halamanPopuler(),
            'perangkat' => $this->perangkat(),
            'referrer' => $this->referrer(),
        ];
    }

    /** 30 hari terakhir, tanggal tanpa kunjungan tetap muncul sebagai 0. */
    private function tren30Hari(): array
    {
        $mulai = today()->subDays(29);

        $data = Kunjungan::where('tanggal', '>=', $mulai->toDateString())
            ->groupBy('tanggal')
            ->select('tanggal')
            ->selectRaw('COUNT(*) AS views')
            ->selectRaw('COUNT(DISTINCT pengunjung) AS uniq')
            ->get()
            ->keyBy(fn ($r) => $r->tanggal->toDateString());

        $hasil = [];
        for ($i = 0; $i < 30; $i++) {
            $tgl = $mulai->copy()->addDays($i);
            $baris = $data->get($tgl->toDateString());

            $hasil[$tgl->translatedFormat('d M')] = [
                'views' => (int) ($baris->views ?? 0),
                'unique' => (int) ($baris->uniq ?? 0),
            ];
        }

        return $hasil;
    }

    /** Sebaran per jam untuk hari ini (0-23), jam yang belum lewat tetap 0. */
    private function kunjunganPerJam(): array
    {
        $data = Kunjungan::where('tanggal', today()->toDateString())
            ->groupBy('jam')
            ->pluck(DB::raw('COUNT(*)'), 'jam');

        $hasil = [];
        for ($j = 0; $j <= 23; $j++) {
            $hasil[sprintf('%02d:00', $j)] = (int) ($data[$j] ?? 0);
        }

        return $hasil;
    }

    private function halamanPopuler(): array
    {
        return Kunjungan::groupBy('path')
            ->select('path')
            ->selectRaw('COUNT(*) AS views')
            ->selectRaw('COUNT(DISTINCT pengunjung) AS uniq')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'halaman' => self::LABEL[$r->path] ?? $r->path,
                'views' => (int) $r->views,
                'unique' => (int) $r->uniq,
            ])
            ->all();
    }

    /** Persentase per jenis perangkat; totalnya dipaksa pas 100. */
    private function perangkat(): array
    {
        $jumlah = Kunjungan::groupBy('perangkat')
            ->pluck(DB::raw('COUNT(*)'), 'perangkat');

        $total = $jumlah->sum();

        if ($total === 0) {
            return [];
        }

        $label = ['mobile' => 'Mobile', 'desktop' => 'Desktop', 'tablet' => 'Tablet'];
        $hasil = [];

        foreach ($label as $kunci => $nama) {
            $n = (int) ($jumlah[$kunci] ?? 0);

            if ($n > 0) {
                $hasil[$nama] = (int) round($n / $total * 100);
            }
        }

        // pembulatan bisa membuat totalnya 99 atau 101 — selisihnya ditambal ke yang terbesar
        if ($hasil !== [] && ($selisih = 100 - array_sum($hasil)) !== 0) {
            $terbesar = array_search(max($hasil), $hasil, true);
            $hasil[$terbesar] += $selisih;
        }

        return $hasil;
    }

    private function referrer(): array
    {
        return Kunjungan::selectRaw("COALESCE(referrer, 'Direct / Bookmark') AS sumber")
            ->selectRaw('COUNT(*) AS views')
            ->groupBy('sumber')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['sumber' => $r->sumber, 'views' => (int) $r->views])
            ->all();
    }
}
