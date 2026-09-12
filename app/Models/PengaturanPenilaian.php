<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan cara menilai — satu baris saja. Ambil lewat PengaturanPenilaian::aktif().
 */
class PengaturanPenilaian extends Model
{
    protected $table = 'pengaturan_penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'skala' => 'array',
        'hanya_terverifikasi' => 'boolean',
    ];

    /** Preset skala siap pakai untuk halaman Rubrik. */
    public const PRESET = [
        'nol_lima' => [
            'label' => '0 – 5 (enam tingkat)',
            'skala' => [
                ['nilai' => 0, 'label' => 'Tidak ada'],
                ['nilai' => 1, 'label' => 'Sangat kurang'],
                ['nilai' => 2, 'label' => 'Kurang'],
                ['nilai' => 3, 'label' => 'Cukup'],
                ['nilai' => 4, 'label' => 'Baik'],
                ['nilai' => 5, 'label' => 'Sangat baik'],
            ],
        ],
        'satu_sembilan' => [
            'label' => '1 / 3 / 5 / 7 / 9 (Form Penilaian Substansi)',
            // Form aslinya hanya memberi nama pada ujungnya: "9 (ideal)" dan
            // "1 (kurang)". Label tengah diisi supaya tetap terbaca di layar.
            'skala' => [
                ['nilai' => 1, 'label' => 'Kurang'],
                ['nilai' => 3, 'label' => 'Cukup'],
                ['nilai' => 5, 'label' => 'Baik'],
                ['nilai' => 7, 'label' => 'Sangat Baik'],
                ['nilai' => 9, 'label' => 'Ideal'],
            ],
        ],
        'satu_tujuh' => [
            'label' => '1 / 3 / 5 / 7 (Form Pitching Battle)',
            'skala' => [
                ['nilai' => 1, 'label' => 'Kurang'],
                ['nilai' => 3, 'label' => 'Sedang'],
                ['nilai' => 5, 'label' => 'Baik'],
                ['nilai' => 7, 'label' => 'Sangat Baik'],
            ],
        ],
        'satu_lima' => [
            'label' => '1 – 5 (lima tingkat)',
            'skala' => [
                ['nilai' => 1, 'label' => 'Sangat kurang'],
                ['nilai' => 2, 'label' => 'Kurang'],
                ['nilai' => 3, 'label' => 'Cukup'],
                ['nilai' => 4, 'label' => 'Baik'],
                ['nilai' => 5, 'label' => 'Sangat baik'],
            ],
        ],
    ];

    public const RUMUS = [
        'normalisasi' => 'Dinormalkan — (skor ÷ nilai tertinggi) × bobot, hasil berskala total bobot',
        'mentah' => 'Mentah — skor × bobot (seperti Form Pitching Battle)',
    ];

    /** Baris pengaturan yang dipakai aplikasi; dibuat otomatis kalau belum ada. */
    public static function aktif(): self
    {
        return static::firstOrCreate([], [
            'skala' => self::PRESET['satu_sembilan']['skala'], // Form Penilaian Substansi
            'rumus' => 'mentah',                               // Total = Nilai x Bobot
            'hanya_terverifikasi' => true,                     // hanya yang lolos administrasi
        ]);
    }

    /** @return array<int,int> daftar nilai yang boleh dipilih */
    public function daftarNilai(): array
    {
        return array_map(static fn ($b) => (int) $b['nilai'], $this->skala ?? []);
    }

    public function maksNilai(): int
    {
        return max([1, ...$this->daftarNilai()]);
    }

    public function label(int $nilai): string
    {
        foreach ($this->skala ?? [] as $baris) {
            if ((int) $baris['nilai'] === $nilai) {
                return (string) $baris['label'];
            }
        }

        return (string) $nilai;
    }

    /** Nilai akhir tertinggi yang mungkin, dipakai untuk tampilan "x / maks". */
    public function nilaiMaksimum(float $totalBobot): float
    {
        return $this->rumus === 'mentah'
            ? round($this->maksNilai() * $totalBobot, 2)
            : round($totalBobot, 2);
    }

    /** Preset mana yang sedang dipakai (untuk menandai pilihan di form). */
    public function presetSaatIni(): ?string
    {
        foreach (self::PRESET as $kunci => $def) {
            if ($this->daftarNilai() === array_map(static fn ($b) => $b['nilai'], $def['skala'])) {
                return $kunci;
            }
        }

        return null;
    }
}
