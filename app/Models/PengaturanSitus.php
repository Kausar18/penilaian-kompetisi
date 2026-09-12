<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan halaman publik — satu baris, diambil lewat PengaturanSitus::ambil().
 */
class PengaturanSitus extends Model
{
    protected $table = 'pengaturan_situs';

    protected $guarded = ['id'];

    protected $casts = [
        'situs_aktif' => 'boolean',
        'umumkan_administrasi' => 'boolean',
        'umumkan_finalis' => 'boolean',
        'tampilkan_statistik' => 'boolean',
        'tampilkan_tanggal_jadwal' => 'boolean',
        'timeline' => 'array',
    ];

    public const BAWAAN = [
        'situs_aktif' => true,
        'judul' => 'PRIMESTeP Startup Competition',
        'subjudul' => 'Program inkubasi dan akselerasi startup agromaritim & innopreneurship',
        'deskripsi' => 'Seleksi terbuka bagi startup untuk mengikuti program pembinaan, '
            .'pendanaan, dan pendampingan bisnis. Peserta melewati dua tahap penilaian: '
            .'verifikasi administrasi sesuai Juklak, lalu penilaian substansi lewat Pitching Battle.',
        'penyelenggara' => 'Lembaga Pengembangan Agromaritim dan Akselerasi Innopreneurship IPB',
        'umumkan_administrasi' => false,
        'umumkan_finalis' => false,
        'tampilkan_statistik' => true,
        'tampilkan_tanggal_jadwal' => false,
        'instagram' => '@stpipb',
        'situs_lembaga' => 'https://stp.ipb.ac.id',
        'timeline' => [
            ['tahap' => 'Pendaftaran dibuka', 'tanggal' => '', 'selesai' => true],
            ['tahap' => 'Penutupan pendaftaran', 'tanggal' => '', 'selesai' => false],
            ['tahap' => 'Verifikasi administrasi', 'tanggal' => '', 'selesai' => false],
            ['tahap' => 'Pengumuman lolos administrasi', 'tanggal' => '', 'selesai' => false],
            ['tahap' => 'Pitching Battle', 'tanggal' => '', 'selesai' => false],
            ['tahap' => 'Pengumuman finalis', 'tanggal' => '', 'selesai' => false],
        ],
    ];

    public static function ambil(): self
    {
        return static::query()->firstOrCreate([], self::BAWAAN);
    }

    /** Timeline yang sudah dibersihkan dari baris kosong. */
    public function timelineRapi(): array
    {
        return collect($this->timeline ?? [])
            ->filter(fn ($t) => filled($t['tahap'] ?? null))
            ->map(fn ($t) => [
                'tahap' => $t['tahap'],
                'tanggal' => $t['tanggal'] ?? '',
                'selesai' => (bool) ($t['selesai'] ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * Tautan Instagram dari nilai yang tersimpan.
     *
     * Panitia bisa mengetik bebas: "@stpipb", "stpipb", "instagram.com/stpipb",
     * atau URL penuh — semuanya tetap menghasilkan tautan yang benar.
     */
    public function instagramUrl(): ?string
    {
        if (blank($this->instagram)) {
            return null;
        }

        $nilai = trim($this->instagram);

        // sudah URL penuh -> pakai apa adanya
        if (preg_match('#^https?://#i', $nilai)) {
            return $nilai;
        }

        $akun = preg_replace('#^(?:www\.)?instagram\.com/#i', '', $nilai);
        $akun = trim((string) $akun, "@/ \t\n\r");

        return $akun === '' ? null : 'https://www.instagram.com/'.$akun;
    }

    /** Label Instagram yang ditampilkan — selalu dinormalkan jadi bentuk @nama. */
    public function instagramLabel(): ?string
    {
        if (! $url = $this->instagramUrl()) {
            return null;
        }

        $akun = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $akun === '' ? $this->instagram : '@'.$akun;
    }

    /** Ada sesuatu yang sudah boleh diumumkan? */
    public function adaPengumuman(): bool
    {
        return $this->umumkan_administrasi || $this->umumkan_finalis;
    }
}
