<?php

namespace App\Support;

use App\Models\PengaturanPenilaian;
use Illuminate\Support\Collection;

/**
 * Menghitung nilai berbobot dari skor mentah per indikator.
 *
 * Rumus mengikuti pengaturan (menu Rubrik → Pengaturan Penilaian):
 *  - normalisasi : total per indikator = (skor / nilai tertinggi) * bobot
 *                  nilai akhir berskala total bobot (biasanya 100)
 *  - mentah      : total per indikator = skor * bobot
 *                  seperti Form Pitching Battle (kolom "Total (Nilai x Bobot)")
 *
 * Subtotal kelompok = jumlah total indikator di kelompok itu.
 */
class PerhitunganNilai
{
    /**
     * @param  array<int,int|null>  $skorPerIndikator  peta indikator_penilaian_id => skor atau null
     * @param  Collection  $indikator  koleksi IndikatorPenilaian aktif, tiap item punya relasi kategoriPenilaian
     * @return array{nilai_final: float, nilai_mentah: float, maks_mentah: int, subtotal: array<int,array>, jml_indikator: int, jml_terisi: int, lengkap: bool}
     */
    public static function hitung(array $skorPerIndikator, Collection $indikator, ?PengaturanPenilaian $pengaturan = null): array
    {
        $pengaturan ??= PengaturanPenilaian::aktif();
        $maksNilai = $pengaturan->maksNilai();
        $mentah = $pengaturan->rumus === 'mentah';

        $subtotal = [];
        $nilaiFinal = 0.0;
        $nilaiMentah = 0.0;
        $jmlTerisi = 0;

        foreach ($indikator as $ind) {
            $skor = $skorPerIndikator[$ind->id] ?? null;

            $kat = $ind->kategoriPenilaian;
            $katId = $kat?->id ?? 0;

            if (! isset($subtotal[$katId])) {
                $subtotal[$katId] = [
                    'kategori_id' => $katId,
                    'kode' => $kat?->kode,
                    'nama' => $kat?->nama ?? '—',
                    'bobot_persen' => (int) ($kat?->bobot_persen ?? 0),
                    'nilai' => 0.0,
                ];
            }

            if ($skor !== null && $skor !== '') {
                $skor = (int) $skor;
                $total = $mentah
                    ? round($skor * (float) $ind->bobot, 2)
                    : round($skor / $maksNilai * (float) $ind->bobot, 2);

                $subtotal[$katId]['nilai'] = round($subtotal[$katId]['nilai'] + $total, 2);
                $nilaiFinal = round($nilaiFinal + $total, 2);
                $nilaiMentah += $skor;
                $jmlTerisi++;
            }
        }

        $jmlIndikator = $indikator->count();

        return [
            'nilai_final' => round($nilaiFinal, 2),
            'nilai_mentah' => round($nilaiMentah, 2),
            'maks_mentah' => $jmlIndikator * $maksNilai,
            'subtotal' => array_values($subtotal),
            'jml_indikator' => $jmlIndikator,
            'jml_terisi' => $jmlTerisi,
            'lengkap' => $jmlIndikator > 0 && $jmlTerisi === $jmlIndikator,
        ];
    }
}
