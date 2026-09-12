<?php

namespace Database\Seeders;

use App\Models\BidangKompetisi;
use App\Models\DetailPenilaian;
use App\Models\DetailVerifikasi;
use App\Models\IndikatorPenilaian;
use App\Models\ItemVerifikasi;
use App\Models\KategoriPenilaian;
use App\Models\KategoriPeserta;
use App\Models\Kunjungan;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\PengaturanSitus;
use App\Models\Penilaian;
use App\Models\User;
use App\Models\VerifikasiAdministrasi;
use App\Support\PerhitunganNilai;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->admin();
        $this->kategoriPeserta();
        $this->bidangKompetisi();
        $this->rubrik();
        $this->formAdministrasi();
        PengaturanPenilaian::aktif();   // pengaturan skala & rumus (dibuat sekali)
        PengaturanSitus::ambil();       // pengaturan halaman publik (pengumuman default MATI)

        if (app()->environment('local')) {
            $reviewer = $this->reviewerContoh();

            if (Pendaftar::count() === 0) {
                $this->dataContoh($reviewer);

                // demo lokal: nyalakan pengumuman administrasi supaya halaman publik ada isinya.
                // Di produksi blok ini tidak jalan, jadi pengumuman tetap default MATI.
                PengaturanSitus::ambil()->update(['umumkan_administrasi' => true]);
            }

            if (Kunjungan::count() === 0) {
                $this->kunjunganContoh();
            }
        }
    }

    private function admin(): User
    {
        return User::firstOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            [
                'name' => env('ADMIN_NAME', 'Admin Kompetisi'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'peran' => 'admin',
            ],
        );
    }

    /** Akun reviewer contoh untuk pengembangan lokal (login: reviewerN / password). */
    private function reviewerContoh(): Collection
    {
        $daftar = [
            'reviewer1' => 'Deva Primadia Almada',
            'reviewer2' => 'Nabila Rahmawati',
            'reviewer3' => 'Farhan Maulana',
            'reviewer4' => 'Sinta Kusuma Dewi',
        ];

        foreach ($daftar as $username => $nama) {
            User::firstOrCreate(
                ['username' => $username],
                ['name' => $nama, 'password' => Hash::make('password'), 'peran' => 'reviewer'],
            );
        }

        return User::where('peran', 'reviewer')->orderBy('id')->get();
    }

    /**
     * Kategori peserta = jalur program yang diusulkan (kolom "Pilihan Program
     * yang akan diusulkan" pada Google Form PRIMESTeP), bukan status pendidikan.
     */
    private function kategoriPeserta(): void
    {
        foreach (['Inkubasi', 'Akselerasi'] as $i => $nama) {
            KategoriPeserta::firstOrCreate(['nama' => $nama], ['urutan' => $i + 1]);
        }
    }

    /**
     * Bidang usaha bawaan diambil dari yang paling banyak muncul di data nyata
     * Batch 1 (Pangan 46, Industri Kreatif 32, Pertanian Tropis 27, Biosains 14
     * dari 154 pendaftar). SEMENTARA — menunggu daftar bidang resmi dari lembaga.
     *
     * Bidang di luar daftar ini tetap terbentuk sendiri saat import, karena
     * kolomnya di Google Form berupa isian bebas.
     */
    private function bidangKompetisi(): void
    {
        $daftar = [
            'Pangan',
            'Pertanian Tropis',
            'Industri Kreatif',
            'Biosains',
        ];

        foreach ($daftar as $i => $nama) {
            BidangKompetisi::firstOrCreate(['nama' => $nama], ['urutan' => $i + 1]);
        }
    }

    /**
     * Rubrik default = Form Penilaian Substansi (PRIMESTeP / LPAAI IPB).
     * 6 kelompok / 17 indikator, total bobot 100. Skala nilai 9-7-5-3-1.
     * Bisa diubah admin dari menu Rubrik.
     */
    public const RUBRIK_SUBSTANSI = [
        ['TIM', 'Tim', 20, [
            ['Karakter & Komitmen Tim Pendiri', 6],
            ['Komposisi & Kualifikasi Tim Pendiri', 10],
            ['Pengalaman Wirausaha', 4],
        ]],
        ['PRODUK', 'Produk dan Model Bisnis', 25, [
            ['Permasalahan yang Dipecahkan', 5],
            ['Kualitas Produk', 10],
            ['Model Bisnis', 5],
            ['Dampak Sosial dan Lingkungan', 5],
        ]],
        ['PASAR', 'Pasar dan Kompetisi', 15, [
            ['Market Size', 7],
            ['Keunggulan Kompetitif', 8],
        ]],
        ['STRATEGI', 'Strategi', 20, [
            ['Strategi Pemasaran', 8],
            ['Roadmap Produk dan Bisnis', 7],
            ['Action Plan', 5],
        ]],
        ['KELOLA', 'Pengelolaan Bisnis', 5, [
            ['Pengelolaan Keuangan dan Operasional', 5],
        ]],
        ['TRACTION', 'Traction / Perkembangan Usaha', 15, [
            ['Riset Pengguna', 4],
            ['Kesiapan Produk', 4],
            ['Jumlah Pengguna/Pelanggan', 3],
            ['Tingkat Retensi Pengguna/Pelanggan', 4],
        ]],
    ];

    private function rubrik(): void
    {
        foreach (self::RUBRIK_SUBSTANSI as $urutan => [$kode, $nama, $bobotPersen, $indikator]) {
            $kelompok = KategoriPenilaian::firstOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'bobot_persen' => $bobotPersen, 'urutan' => $urutan + 1],
            );

            foreach ($indikator as $i => [$namaIndikator, $bobot]) {
                IndikatorPenilaian::firstOrCreate(
                    ['kategori_penilaian_id' => $kelompok->id, 'nama' => $namaIndikator],
                    ['bobot' => $bobot, 'aktif' => true, 'urutan' => $i + 1],
                );
            }
        }
    }

    /**
     * Butir Form Seleksi Administrasi (Juklak PRIMESTeP). Bisa diubah admin
     * dari menu Konfigurasi -> Form Administrasi.
     */
    private function formAdministrasi(): void
    {
        $kelompokA = 'A. Kesesuaian Kriteria Persyaratan Startup sesuai Juklak';
        $kelompokB = 'B. Kesesuaian Isi dan Template Proposal sesuai Juklak';

        $butir = [
            [$kelompokA, 'CEO Fulltime, salah satu founder merupakan alumni IPB'],
            [$kelompokA, 'Jumlah tim 2-5 orang dengan multiple ekspertise'],
            [$kelompokA, 'Usaha sudah berjalan minimal 6 bulan'],
            [$kelompokA, 'Sudah ada legalitas usaha, berusia paling lama 5 tahun untuk inkubasi dan 7 tahun untuk akselerasi'],
            [$kelompokA, 'Produk inovatif, sesuai bidang fokus IPB dan hasil inovasi dalam negeri'],

            [$kelompokB, 'Cover, Lembar Pengesahan, Identitas pengusul, dan Ringkasan Eksekutif'],
            [$kelompokB, 'Pendahuluan: Latar Belakang, Tujuan, Sasaran'],
            [$kelompokB, 'Aspek Perusahaan: Profil Perusahaan, Struktur Organisasi, Aset, Pengelola Utama, Jumlah Karyawan, % saham, Histori dan target pendanaan'],
            [$kelompokB, 'Aspek Produk: Keunggulan dan Keunikan produk, Spesifikasi, kontinuitas bahan baku, Uji produk, Dampak sosial dan lingkungan, Kepemilikan Kekayaan Intelektual (jika sudah ada), Izin edar, tim inventor, foto produk dan kegiatan produksi'],
            [$kelompokB, 'Aspek Pasar dan Bisnis: BMC, Target dan Potensi Pasar, Analisis Kompetitor, Strategi pemasaran, Roadmap Produk dan Bisnis, HPP HET Marjin, Proyeksi Penjualan, Mitra bisnis, Riset Pengguna, Laporan Keuangan, Foto Aktifitas Bisnis'],
            [$kelompokB, 'Hasil Pelaksanaan Kegiatan Tahun Sebelumnya (untuk pengusul yang pernah ikut PRIMESTeP): Capaian kegiatan, Kendala dan Solusi, Dokumentasi'],
            [$kelompokB, 'Rencana Kegiatan dan Anggaran: Rencana Aksi, RAB'],
            [$kelompokB, 'Lampiran: Surat Pernyataan Tidak Melakukan Gugatan, Surat Pernyataan Tidak Mendapatkan Pendanaan Hibah Lainnya pada Tahun Yang Sama untuk Komponen Yang Sama'],
        ];

        $urutan = [];
        foreach ($butir as [$kelompok, $nama]) {
            $urutan[$kelompok] = ($urutan[$kelompok] ?? 0) + 1;

            ItemVerifikasi::firstOrCreate(
                ['kelompok' => $kelompok, 'nama' => $nama],
                ['aktif' => true, 'urutan' => $urutan[$kelompok]],
            );
        }
    }

    /** Isi form verifikasi administrasi contoh + pasang status pendaftar. */
    private function verifikasiContoh(Pendaftar $p, ?User $verifikator, bool $lolos): void
    {
        $hasil = $lolos ? 'lolos' : 'tidak_lolos';

        $verifikasi = VerifikasiAdministrasi::create([
            'pendaftar_id' => $p->id,
            'verifikator_id' => $verifikator?->id,
            'hasil' => $hasil,
            'catatan' => $lolos
                ? 'Berkas lengkap dan sesuai Juklak.'
                : 'Ada butir yang belum sesuai Juklak.',
            'diverifikasi_at' => now(),
        ]);

        foreach (ItemVerifikasi::aktif()->pluck('id') as $itemId) {
            DetailVerifikasi::create([
                'verifikasi_administrasi_id' => $verifikasi->id,
                'item_verifikasi_id' => $itemId,
                'status' => $lolos
                    ? 'sesuai'
                    : (random_int(1, 100) <= 70 ? 'sesuai' : 'tidak_sesuai'),
            ]);
        }

        $p->update(['status' => VerifikasiAdministrasi::STATUS_PENDAFTAR[$hasil]]);
    }

    /**
     * Kunjungan contoh 30 hari terakhir supaya halaman Pengunjung ada isinya
     * saat pengembangan lokal. Tidak pernah jalan di produksi.
     */
    private function kunjunganContoh(): void
    {
        $halaman = ['/', '/', '/', '/', '/pengumuman', '/pengumuman'];
        $perangkat = ['mobile', 'mobile', 'mobile', 'desktop', 'desktop', 'tablet'];
        $perujuk = [null, null, 'www.google.com', 'instagram.com', 'ipb.ac.id'];

        $baris = [];

        for ($hari = 29; $hari >= 0; $hari--) {
            $tanggal = today()->subDays($hari);
            // makin dekat hari ini makin ramai, plus sedikit variasi
            $jumlah = (int) round((30 - $hari) * 1.6) + random_int(0, 12);

            for ($i = 0; $i < $jumlah; $i++) {
                $jam = random_int(7, 22);

                $baris[] = [
                    'path' => $halaman[array_rand($halaman)],
                    'pengunjung' => hash('sha256', $tanggal->toDateString().'-'.random_int(1, max(3, (int) ($jumlah * 0.7)))),
                    'perangkat' => $perangkat[array_rand($perangkat)],
                    'referrer' => $perujuk[array_rand($perujuk)],
                    'tanggal' => $tanggal->toDateString(),
                    'jam' => $jam,
                    'created_at' => $tanggal->copy()->setTime($jam, random_int(0, 59)),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($baris, 500) as $potongan) {
            Kunjungan::insert($potongan);
        }
    }

    /** Data contoh untuk mengisi dashboard & rekap saat pengembangan lokal. */
    private function dataContoh(Collection $reviewer): void
    {
        $indikator = IndikatorPenilaian::aktif()->with('kategoriPenilaian')->get();
        $jmlReviewer = $reviewer->count();
        $skalaNilai = PengaturanPenilaian::aktif()->daftarNilai();

        Pendaftar::factory()->count(28)->create()->values()->each(function (Pendaftar $p, int $i) use ($reviewer, $jmlReviewer, $indikator, $skalaNilai) {
            // 0-3 anggota tim
            foreach (range(0, random_int(0, 3)) as $ignored) {
                $p->anggotaTim()->create([
                    'nama' => fake('id_ID')->name(),
                    'nim' => fake()->numerify('##########'),
                ]);
            }

            // ~85% peserta ditugaskan ke reviewer (round-robin), sisanya dibiarkan kosong
            $r = ($jmlReviewer > 0 && random_int(1, 100) <= 85)
                ? $reviewer[$i % $jmlReviewer]
                : null;

            if ($r) {
                $p->update(['reviewer_id' => $r->id]);
            }

            // --- Tahap 1: verifikasi administrasi (~75% peserta sudah diverifikasi) ---
            if (random_int(1, 100) > 75) {
                return;     // belum diverifikasi -> status tetap 'submitted'
            }

            $lolosAdm = random_int(1, 100) <= 75;
            $this->verifikasiContoh($p, $r, $lolosAdm);

            // --- Tahap 2: penilaian hanya untuk yang lolos administrasi & sudah ditugaskan ---
            if (! $lolosAdm || ! $r || random_int(1, 100) > 70) {
                return;
            }

            $lengkap = random_int(1, 100) <= 75;
            $skorMap = [];

            $penilaian = Penilaian::create([
                'pendaftar_id' => $p->id,
                'reviewer_id' => $r->id,
            ]);

            foreach ($indikator as $ind) {
                $isi = $lengkap || random_int(1, 100) <= 70;
                $skor = $isi ? $skalaNilai[array_rand($skalaNilai)] : null;
                $skorMap[$ind->id] = $skor;

                DetailPenilaian::create([
                    'penilaian_id' => $penilaian->id,
                    'indikator_penilaian_id' => $ind->id,
                    'skor' => $skor,
                ]);
            }

            $hitung = PerhitunganNilai::hitung($skorMap, $indikator);

            $penilaian->update([
                'nilai_final' => $hitung['nilai_final'],
                'nilai_mentah' => $hitung['nilai_mentah'],
                'rekomendasi' => $hitung['lengkap']
                    ? ($hitung['nilai_final'] >= 60 ? 'lolos' : 'tidak_lolos')
                    : null,
                'catatan_reviewer' => $hitung['lengkap'] ? 'Catatan reviewer contoh untuk kebutuhan pengembangan.' : null,
                'dinilai_at' => $hitung['lengkap'] ? now() : null,
            ]);
        });
    }
}
