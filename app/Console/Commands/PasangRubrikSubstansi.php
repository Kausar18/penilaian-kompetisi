<?php

namespace App\Console\Commands;

use App\Models\IndikatorPenilaian;
use App\Models\KategoriPenilaian;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;
use App\Support\HitungUlangPenilaian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memasang Form Penilaian Substansi (6 kelompok / 17 indikator, skala 9-7-5-3-1)
 * ke database yang SUDAH berjalan.
 *
 * Seeder memakai firstOrCreate sehingga tidak pernah menimpa rubrik yang ada —
 * perintah inilah yang menggantinya secara sengaja.
 *
 * PERHATIAN: kategori_penilaian -> indikator_penilaian -> detail_penilaian
 * memakai cascadeOnDelete, jadi mengganti rubrik akan MENGHAPUS skor per
 * indikator yang sudah tersimpan. Karena itu perintah ini selalu konfirmasi.
 */
class PasangRubrikSubstansi extends Command
{
    protected $signature = 'rubrik:substansi {--force : jalankan tanpa konfirmasi}';

    protected $description = 'Ganti rubrik penilaian dengan Form Penilaian Substansi (skala 9-7-5-3-1)';

    public function handle(): int
    {
        $adaSkor = Penilaian::whereHas('detail', fn ($q) => $q->whereNotNull('skor'))->count();

        $this->info('Rubrik baru: Form Penilaian Substansi — 6 kelompok / 17 indikator, total bobot 100.');
        $this->line('Skala nilai diubah menjadi 9 (ideal) / 7 / 5 / 3 / 1 (kurang).');

        if ($adaSkor > 0) {
            $this->newLine();
            $this->warn("PERHATIAN: ada {$adaSkor} penilaian yang sudah punya skor.");
            $this->warn('Mengganti rubrik akan MENGHAPUS seluruh skor per indikator tersebut,');
            $this->warn('karena indikator lamanya ikut terhapus. Tindakan ini tidak bisa dibatalkan.');
        }

        if (! $this->option('force') && ! $this->confirm('Lanjutkan mengganti rubrik?', false)) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            // hapus rubrik lama (cascade ikut membersihkan indikator & detail skor)
            KategoriPenilaian::query()->delete();

            foreach (DatabaseSeeder::RUBRIK_SUBSTANSI as $urutan => [$kode, $nama, $bobotPersen, $indikator]) {
                $kelompok = KategoriPenilaian::create([
                    'kode' => $kode,
                    'nama' => $nama,
                    'bobot_persen' => $bobotPersen,
                    'urutan' => $urutan + 1,
                ]);

                foreach ($indikator as $i => [$namaIndikator, $bobot]) {
                    IndikatorPenilaian::create([
                        'kategori_penilaian_id' => $kelompok->id,
                        'nama' => $namaIndikator,
                        'bobot' => $bobot,
                        'aktif' => true,
                        'urutan' => $i + 1,
                    ]);
                }
            }

            PengaturanPenilaian::aktif()->update([
                'skala' => PengaturanPenilaian::PRESET['satu_sembilan']['skala'],
                'rumus' => 'mentah',
            ]);
        });

        // nilai tersimpan (kalau ada) dihitung ulang terhadap rubrik & skala baru
        $diperbarui = HitungUlangPenilaian::semua();

        $totalBobot = (float) IndikatorPenilaian::aktif()->sum('bobot');

        $this->newLine();
        $this->info('Rubrik terpasang.');
        $this->table(
            ['Kelompok', 'Bobot', 'Indikator'],
            KategoriPenilaian::withCount('indikator')->orderBy('urutan')->get()
                ->map(fn ($k) => [$k->nama, $k->bobot_persen, $k->indikator_count])
                ->all(),
        );
        $this->line("Total bobot     : {$totalBobot}");
        $this->line('Nilai maksimum  : '.PengaturanPenilaian::aktif()->nilaiMaksimum($totalBobot));
        $this->line("Penilaian dihitung ulang: {$diperbarui}");

        return self::SUCCESS;
    }
}
