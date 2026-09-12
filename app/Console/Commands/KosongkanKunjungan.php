<?php

namespace App\Console\Commands;

use App\Models\Kunjungan;
use Illuminate\Console\Command;

/**
 * Mengosongkan statistik pengunjung.
 *
 * Dipakai dua kali dalam daur hidup sistem:
 * 1. sebelum situs publik benar-benar dibuka, supaya angka dari uji coba lokal
 *    tidak bercampur dengan pengunjung sungguhan;
 * 2. sebelum salinan diserahkan ke instansi (lihat checklist kloning di README).
 */
class KosongkanKunjungan extends Command
{
    protected $signature = 'kunjungan:kosongkan {--force : jalankan tanpa konfirmasi}';

    protected $description = 'Hapus seluruh catatan kunjungan halaman publik (statistik Pengunjung kembali nol)';

    public function handle(): int
    {
        $jumlah = Kunjungan::count();

        if ($jumlah === 0) {
            $this->info('Tidak ada catatan kunjungan — sudah kosong.');

            return self::SUCCESS;
        }

        $rentang = Kunjungan::selectRaw('MIN(tanggal) AS awal, MAX(tanggal) AS akhir')->first();

        $this->warn("Akan menghapus {$jumlah} catatan kunjungan ({$rentang->awal} s/d {$rentang->akhir}).");
        $this->line('Data pendaftar, penilaian, dan verifikasi TIDAK tersentuh.');

        if (! $this->option('force') && ! $this->confirm('Lanjutkan?', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        Kunjungan::query()->delete();

        $this->info("Selesai. {$jumlah} catatan dihapus, statistik Pengunjung kembali nol.");

        return self::SUCCESS;
    }
}
