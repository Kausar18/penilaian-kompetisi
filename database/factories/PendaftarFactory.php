<?php

namespace Database\Factories;

use App\Models\BidangKompetisi;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pendaftar>
 */
class PendaftarFactory extends Factory
{
    protected $model = Pendaftar::class;

    public function definition(): array
    {
        $provinsi = $this->faker->randomElement([
            'Jawa Barat', 'Jawa Timur', 'Jawa Tengah', 'DKI Jakarta', 'DI Yogyakarta',
            'Sulawesi Selatan', 'Aceh', 'Banten', 'Sumatera Utara', 'Kalimantan Selatan',
            'Sumatera Barat', 'Bali', 'Nusa Tenggara Barat', 'Riau', 'Lampung',
        ]);

        $namaTim = ucwords($this->faker->words($this->faker->numberBetween(1, 3), true));
        $produk = ucwords($this->faker->words(2, true));

        return [
            'nomor' => 'REG-'.$this->faker->unique()->numberBetween(1000, 9999),
            'tanggal_daftar' => $this->faker->dateTimeBetween('-20 days', 'now')->format('Y-m-d'),
            'kategori_peserta_id' => KategoriPeserta::inRandomOrder()->value('id'),
            'bidang_kompetisi_id' => BidangKompetisi::inRandomOrder()->value('id'),

            'nama_ketua' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'no_wa' => '08'.$this->faker->numerify('##########'),
            'nama_tim' => $namaTim,

            'judul_inovasi' => "{$produk}: Inovasi ".ucfirst($this->faker->words(4, true)),
            'deskripsi_singkat' => $this->faker->paragraph(4),
            'permasalahan' => $this->faker->paragraph(3),
            'solusi' => $this->faker->paragraph(3),
            'target_pengguna' => $this->faker->sentence(10),
            'dampak_sosial' => $this->faker->paragraph(2),

            'asal_institusi' => 'Universitas '.$this->faker->city(),
            'perguruan_tinggi' => 'Universitas '.$this->faker->city(),
            'fakultas_prodi' => $this->faker->randomElement([
                'Fakultas Ekonomi / Manajemen', 'Fakultas Teknik / Teknik Industri',
                'Fakultas Pertanian / Agribisnis', 'FMIPA / Kimia', 'Fakultas Ilmu Komputer / Informatika',
            ]),
            'nim_ketua' => $this->faker->numerify('##########'),
            'semester' => (string) $this->faker->numberBetween(2, 8),

            'kota' => 'Kota '.$this->faker->city(),
            'provinsi' => $provinsi,
            'link_pitchdeck' => 'https://drive.google.com/file/d/'.$this->faker->uuid(),
            'link_logo' => 'https://drive.google.com/file/d/'.$this->faker->uuid(),

            // status hanya berubah lewat Verifikasi Administrasi, jadi awalnya selalu 'submitted'
            'status' => 'submitted',
            'data_asli' => null,
        ];
    }
}
