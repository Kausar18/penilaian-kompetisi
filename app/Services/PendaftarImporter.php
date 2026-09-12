<?php

namespace App\Services;

use App\Models\BidangKompetisi;
use App\Models\ImportLog;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Support\ParserData;
use App\Support\WilayahIndonesia;
use Illuminate\Support\Facades\DB;

/**
 * Memetakan baris export Google Form ke tabel pendaftar.
 *
 * Pencocokan kolom berbasis KATA KUNCI pada header — bukan posisi kolom —
 * supaya tahan terhadap perbedaan susunan / label form.
 *
 * Kunci anti-duplikat dipilih dari identitas yang tersedia (lihat kunciUnik):
 * email + nama tim kalau ada emailnya, kalau tidak no WA + nama tim. Form
 * PRIMESTeP tidak memuat kolom email sama sekali, sehingga tanpa ini setiap
 * import ulang akan menggandakan seluruh baris.
 */
class PendaftarImporter
{
    /** nama kolom internal => daftar kata kunci pada header (huruf kecil). */
    private const PETA_KOLOM = [
        'tanggal_daftar' => ['timestamp', 'tanggal daftar', 'stempel waktu', 'waktu'],
        'kategori' => ['kategori peserta', 'kategori pendaftar', 'pilihan program', 'kategori'],
        'bidang' => ['bidang kompetisi', 'bidang usaha', 'bidang', 'sektor'],
        'nama_ketua' => ['nama ketua', 'nama lengkap ketua', 'ketua tim', 'nama ketua tim', 'nama ceo', 'nama pemilik'],
        'email' => ['email', 'alamat email', 'e-mail'],
        'no_wa' => ['whatsapp', 'no wa', 'nomor wa', 'no. hp', 'nomor hp', 'kontak'],
        'nama_tim' => ['nama tim', 'nama startup', 'nama usaha', 'nama tim/usaha', 'nama brand'],
        'judul_inovasi' => ['judul inovasi', 'judul', 'nama inovasi', 'judul produk', 'nama produk'],
        'deskripsi_singkat' => ['deskripsi singkat', 'deskripsi', 'ringkasan inovasi', 'deskripsi produk'],
        'permasalahan' => ['permasalahan', 'latar belakang masalah', 'masalah yang'],
        'solusi' => ['solusi', 'solusi yang ditawarkan', 'rencana pengembangan'],
        'target_pengguna' => ['target pengguna', 'target pasar', 'segmen pasar', 'target konsumen'],
        'dampak_sosial' => ['dampak sosial', 'dampak', 'manfaat sosial'],
        'asal_institusi' => ['asal institusi', 'institusi', 'nama instansi', 'asal instansi', 'asal sekolah'],
        'perguruan_tinggi' => ['perguruan tinggi', 'universitas', 'kampus'],
        'fakultas_prodi' => ['fakultas', 'program studi', 'prodi', 'jurusan'],
        'nim_ketua' => ['nim ketua', 'nim'],
        'semester' => ['semester'],
        'kota' => ['kota', 'kabupaten', 'kota/kabupaten', 'domisili'],
        'provinsi' => ['provinsi'],
        // sengaja di bawah 'kota'/'provinsi': kalau form punya kolom kota sendiri,
        // kolom itu yang dipakai dan alamat hanya jadi cadangan
        'alamat' => ['alamat rumah', 'alamat domisili', 'alamat lengkap', 'alamat'],
        'link_pitchdeck' => ['pitch deck', 'pitchdeck', 'link proposal', 'upload proposal', 'berkas proposal'],
        'link_logo' => ['logo', 'upload logo'],
        'anggota_tim' => ['anggota tim', 'nama anggota', 'daftar anggota', 'tim inti', 'anggota'],
    ];

    private array $indeks = [];

    private array $catatan = [];

    private int $berhasil = 0;

    private int $gagal = 0;

    /**
     * @param  array<int,array<int,mixed>>  $baris  seluruh baris termasuk header
     */
    public function jalankan(array $baris, string $namaFile, ?int $userId = null): array
    {
        if (count($baris) < 2) {
            throw new \RuntimeException('Berkas kosong atau tidak punya baris data.');
        }

        $this->indeks = $this->petakanHeader(array_shift($baris));

        if (! isset($this->indeks['nama_ketua']) && ! isset($this->indeks['nama_tim'])) {
            throw new \RuntimeException(
                'Kolom "Nama Ketua" atau "Nama Tim" tidak ditemukan. Pastikan sheet/berkas yang dipilih benar.'
            );
        }

        foreach ($baris as $nomor => $data) {
            $namaTim = ParserData::teks($this->ambil($data, 'nama_tim'), 200);
            $namaKetua = ParserData::teks($this->ambil($data, 'nama_ketua'), 150);

            if ($namaTim === null && $namaKetua === null) {
                continue; // baris kosong di akhir
            }

            $namaKetua ??= $namaTim;
            $namaTim ??= $namaKetua;

            try {
                DB::transaction(fn () => $this->simpanBaris($data, $namaTim, $namaKetua));
                $this->berhasil++;
            } catch (\Throwable $e) {
                $this->gagal++;
                $this->catatan[] = 'Baris '.($nomor + 2).' ('.$namaTim.'): '.$e->getMessage();
            }
        }

        ImportLog::create([
            'nama_file' => $namaFile,
            'jumlah_berhasil' => $this->berhasil,
            'jumlah_gagal' => $this->gagal,
            'catatan' => $this->catatan ? implode("\n", array_slice($this->catatan, 0, 50)) : null,
            'user_id' => $userId,
        ]);

        return ['berhasil' => $this->berhasil, 'gagal' => $this->gagal, 'catatan' => $this->catatan];
    }

    /**
     * Kolom tabel pendaftar => kolom berkas asalnya.
     *
     * Dipakai untuk melewati kolom yang tidak ada di berkas. `nama_tim`,
     * `nama_ketua`, dan `data_asli` sengaja tidak didaftarkan karena selalu ada.
     */
    private const SUMBER_KOLOM = [
        'tanggal_daftar' => 'tanggal_daftar',
        'kategori_peserta_id' => 'kategori',
        'bidang_kompetisi_id' => 'bidang',
        'no_wa' => 'no_wa',
        'judul_inovasi' => 'judul_inovasi',
        'deskripsi_singkat' => 'deskripsi_singkat',
        'permasalahan' => 'permasalahan',
        'solusi' => 'solusi',
        'target_pengguna' => 'target_pengguna',
        'dampak_sosial' => 'dampak_sosial',
        'asal_institusi' => 'asal_institusi',
        'perguruan_tinggi' => 'perguruan_tinggi',
        'fakultas_prodi' => 'fakultas_prodi',
        'nim_ketua' => 'nim_ketua',
        'semester' => 'semester',

        'link_pitchdeck' => 'link_pitchdeck',
        'link_logo' => 'link_logo',
    ];

    private function simpanBaris(array $data, string $namaTim, string $namaKetua): void
    {
        $kategoriNama = ParserData::teks($this->ambil($data, 'kategori'), 100);
        $bidangNama = ParserData::teks($this->ambil($data, 'bidang'), 150);

        // Kolom kota/provinsi dipakai kalau ada; kalau tidak, ditebak dari alamat
        // teks bebas (form PRIMESTeP hanya punya "Alamat Rumah").
        $wilayah = [
            'kota' => ParserData::teks($this->ambil($data, 'kota'), 120),
            'provinsi' => ParserData::teks($this->ambil($data, 'provinsi'), 120),
        ];

        if (blank($wilayah['kota']) || blank($wilayah['provinsi'])) {
            $tebakan = WilayahIndonesia::dariAlamat(ParserData::teksPanjang($this->ambil($data, 'alamat')));
            $wilayah['kota'] ??= $tebakan['kota'];
            $wilayah['provinsi'] ??= $tebakan['provinsi'];
        }

        $kategoriId = $kategoriNama
            ? KategoriPeserta::firstOrCreate(['nama' => $kategoriNama])->id
            : null;
        $bidangId = $bidangNama
            ? BidangKompetisi::firstOrCreate(['nama' => $bidangNama])->id
            : null;

        $atribut = [
            'tanggal_daftar' => ParserData::tanggal($this->ambil($data, 'tanggal_daftar')) ?? now()->toDateString(),
            'kategori_peserta_id' => $kategoriId,
            'bidang_kompetisi_id' => $bidangId,
            'nama_ketua' => $namaKetua,
            'no_wa' => ParserData::teks($this->ambil($data, 'no_wa'), 40),
            'judul_inovasi' => ParserData::teksPanjang($this->ambil($data, 'judul_inovasi')),
            'deskripsi_singkat' => ParserData::teksPanjang($this->ambil($data, 'deskripsi_singkat')),
            'permasalahan' => ParserData::teksPanjang($this->ambil($data, 'permasalahan')),
            'solusi' => ParserData::teksPanjang($this->ambil($data, 'solusi')),
            'target_pengguna' => ParserData::teksPanjang($this->ambil($data, 'target_pengguna')),
            'dampak_sosial' => ParserData::teksPanjang($this->ambil($data, 'dampak_sosial')),
            'asal_institusi' => ParserData::teks($this->ambil($data, 'asal_institusi'), 200),
            'perguruan_tinggi' => ParserData::teks($this->ambil($data, 'perguruan_tinggi'), 200),
            'fakultas_prodi' => ParserData::teks($this->ambil($data, 'fakultas_prodi'), 200),
            'nim_ketua' => ParserData::teks($this->ambil($data, 'nim_ketua'), 40),
            'semester' => ParserData::teks($this->ambil($data, 'semester'), 20),
            'kota' => $wilayah['kota'],
            'provinsi' => $wilayah['provinsi'],
            'link_pitchdeck' => ParserData::teks($this->ambil($data, 'link_pitchdeck'), 500),
            'link_logo' => ParserData::teks($this->ambil($data, 'link_logo'), 500),
            'data_asli' => $data,
        ];

        // Berkas yang lebih ringkas (mis. sheet "Data 37 Lolos") tidak memuat
        // semua kolom. Kolom yang memang TIDAK ADA di berkas harus dilewati,
        // bukan ditulis kosong — kalau tidak, import kedua menghapus data hasil
        // import pertama (dulu tanggal daftar 37 peserta tertimpa tanggal hari ini).
        foreach (self::SUMBER_KOLOM as $kolomDb => $kolomBerkas) {
            if (! isset($this->indeks[$kolomBerkas])) {
                unset($atribut[$kolomDb]);
            }
        }

        // kota & provinsi punya tiga kemungkinan sumber; kalau berkasnya tidak
        // memuat satu pun, jangan sentuh nilai yang sudah ada
        $adaSumberWilayah = isset($this->indeks['kota'])
            || isset($this->indeks['provinsi'])
            || isset($this->indeks['alamat']);

        if (! $adaSumberWilayah) {
            unset($atribut['kota'], $atribut['provinsi']);
        }

        $pendaftar = Pendaftar::updateOrCreate($this->kunciUnik($data, $namaTim), $atribut);

        // peserta baru dari berkas tanpa kolom tanggal -> pakai tanggal import
        if (blank($pendaftar->tanggal_daftar)) {
            $pendaftar->update(['tanggal_daftar' => now()->toDateString()]);
        }

        // Anggota tim ditulis ulang tiap import agar tidak menumpuk — tapi hanya
        // kalau berkasnya memang punya kolomnya.
        if (isset($this->indeks['anggota_tim'])) {
            $pendaftar->anggotaTim()->delete();
            foreach (ParserData::daftarAnggota($this->ambil($data, 'anggota_tim')) as $anggota) {
                $pendaftar->anggotaTim()->create($anggota);
            }
        }
    }

    /**
     * Kunci untuk mengenali peserta yang sama saat import diulang.
     *
     * Google Form PRIMESTeP tidak punya kolom email, jadi kuncinya turun ke
     * nomor WhatsApp. Kalau dua-duanya kosong, nama tim dipakai sendirian —
     * konsekuensinya dua usaha bernama sama akan dianggap satu, dan itu
     * dicatat sebagai peringatan di ringkasan import.
     */
    private function kunciUnik(array $data, string $namaTim): array
    {
        $email = ParserData::teks($this->ambil($data, 'email'), 150);

        if (filled($email)) {
            return ['email' => $email, 'nama_tim' => $namaTim];
        }

        $noWa = ParserData::teks($this->ambil($data, 'no_wa'), 40);

        if (filled($noWa)) {
            return ['no_wa' => $noWa, 'nama_tim' => $namaTim];
        }

        $this->catatan[] = "Baris \"{$namaTim}\": tidak ada email maupun nomor WA, "
            .'peserta dikenali dari nama tim saja.';

        return ['nama_tim' => $namaTim];
    }

    /** @return array<string,int> nama kolom internal => indeks kolom */
    private function petakanHeader(array $header): array
    {
        $indeks = [];

        foreach ($header as $posisi => $judul) {
            if (blank($judul)) {
                continue;
            }

            $bersih = strtolower(trim(preg_replace('/\s+/u', ' ', (string) $judul)));

            foreach (self::PETA_KOLOM as $kolom => $kataKunci) {
                if (isset($indeks[$kolom])) {
                    continue;
                }
                foreach ($kataKunci as $kunci) {
                    if (str_contains($bersih, $kunci)) {
                        $indeks[$kolom] = $posisi;
                        break 2;
                    }
                }
            }
        }

        return $indeks;
    }

    private function ambil(array $baris, string $kolom)
    {
        return isset($this->indeks[$kolom]) ? ($baris[$this->indeks[$kolom]] ?? null) : null;
    }
}
