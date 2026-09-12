<?php

namespace App\Support;

use App\Models\IndikatorPenilaian;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;

/**
 * Hitung ulang nilai semua penilaian yang sudah tersimpan.
 *
 * Dipanggil setiap kali rubrik (kelompok/indikator/bobot) atau pengaturan
 * skala & rumus berubah — tanpa ini `nilai_final` yang tersimpan jadi basi
 * terhadap aturan baru dan ranking di Rekap ikut salah.
 */
class HitungUlangPenilaian
{
    /** @return int jumlah baris penilaian yang diperbarui */
    public static function semua(): int
    {
        $indikator = IndikatorPenilaian::aktif()->with('kategoriPenilaian')->get();
        $pengaturan = PengaturanPenilaian::aktif();
        $jumlah = 0;

        Penilaian::with('detail')->chunkById(200, function ($daftar) use ($indikator, $pengaturan, &$jumlah) {
            foreach ($daftar as $penilaian) {
                $tersimpan = $penilaian->detail->pluck('skor', 'indikator_penilaian_id')->all();

                // hanya indikator aktif yang dihitung; indikator baru otomatis kosong
                $skorMap = [];
                foreach ($indikator as $ind) {
                    $skorMap[$ind->id] = $tersimpan[$ind->id] ?? null;
                }

                $hitung = PerhitunganNilai::hitung($skorMap, $indikator, $pengaturan);
                $lengkap = $hitung['lengkap'] && $penilaian->rekomendasi !== null;

                $penilaian->update([
                    'nilai_final' => $hitung['nilai_final'],
                    'nilai_mentah' => $hitung['nilai_mentah'],
                    // kalau jadi tidak lengkap (mis. ada indikator baru), turun lagi jadi draft
                    'dinilai_at' => $lengkap ? ($penilaian->dinilai_at ?? now()) : null,
                ]);

                $jumlah++;
            }
        });

        return $jumlah;
    }
}
