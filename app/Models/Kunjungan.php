<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = satu kali halaman publik dibuka.
 *
 * Kolom `pengunjung` adalah hash harian (lihat migrasi) — tidak ada IP mentah
 * yang tersimpan, jadi tabel ini aman kalau database diserahkan ke instansi.
 */
class Kunjungan extends Model
{
    protected $table = 'kunjungan';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jam' => 'integer',
    ];

    /** Tebak jenis perangkat dari user agent (kasar, cukup untuk statistik). */
    public static function tebakPerangkat(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'ipad') || str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone') => 'mobile',
            default => 'desktop',
        };
    }

    /**
     * Identitas semu pengunjung: hash IP + user agent + APP_KEY + tanggal.
     * Berganti tiap hari sehingga tidak bisa dipakai melacak orang antar hari.
     */
    public static function sidikJari(?string $ip, ?string $userAgent, string $tanggal): string
    {
        return hash('sha256', implode('|', [$ip, $userAgent, config('app.key'), $tanggal]));
    }
}
