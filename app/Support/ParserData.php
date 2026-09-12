<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Pembersih nilai mentah dari Google Form / Excel: teks bebas, tanggal
 * dengan format campur, dan daftar anggota tim satu sel.
 */
class ParserData
{
    /** Rapikan teks: trim, ubah kosong jadi null, potong sesuai batas. */
    public static function teks($nilai, ?int $maks = null): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $bersih = trim(preg_replace('/\s+/u', ' ', (string) $nilai));

        if ($bersih === '' || strtolower($bersih) === 'n/a' || $bersih === '-') {
            return null;
        }

        return $maks ? mb_substr($bersih, 0, $maks) : $bersih;
    }

    /** Teks panjang (deskripsi, dll): trim tanpa merapikan baris baru. */
    public static function teksPanjang($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $bersih = trim((string) $nilai);

        return $bersih === '' ? null : $bersih;
    }

    /** Coba parse tanggal dari berbagai format. Kembali Y-m-d atau null. */
    public static function tanggal($nilai): ?string
    {
        $teks = self::teks($nilai);
        if ($teks === null) {
            return null;
        }

        // Angka serial Excel
        if (is_numeric($teks) && (float) $teks > 30000 && (float) $teks < 90000) {
            return Carbon::createFromTimestamp(((float) $teks - 25569) * 86400)->toDateString();
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'd F Y', 'd M Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $teks)->toDateString();
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        try {
            return Carbon::parse($teks)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pecah satu sel anggota tim jadi daftar terstruktur.
     * Terima pemisah baris baru, koma, atau titik koma; tiap item bisa
     * "Nama - NIM", "Nama (NIM)", atau "Nama" saja.
     *
     * @return array<int,array{nama:string,nim:?string}>
     */
    public static function daftarAnggota($nilai): array
    {
        $teks = self::teksPanjang($nilai);
        if ($teks === null) {
            return [];
        }

        // Form PRIMESTeP menggabungkan dua pertanyaan dalam satu sel:
        // "1. 3 orang 2. 1. Komisaris Utama: Andi (Pria), 2. CFO: Rizal (Pria)".
        // Buang dulu bagian jumlahnya supaya tidak ikut terbaca sebagai nama.
        $teks = preg_replace('/^\s*1\s*[.)]?\s*\d+\s*orang\s*[,.]?\s*2\s*[.)]?\s*/iu', '', $teks) ?? $teks;

        $potongan = preg_split('/[\r\n;]+|,(?![^(]*\))/u', $teks) ?: [];
        $hasil = [];

        foreach ($potongan as $item) {
            $item = trim($item);

            // buang penomoran di depan tiap item: "1. ", "2) "
            $item = trim(preg_replace('/^\d+\s*[.)]\s*/u', '', $item) ?? $item);

            // sisa potongan yang cuma menyebut jumlah, mis. "3 orang"
            if ($item === '' || preg_match('/^\d+\s*orang$/iu', $item)) {
                continue;
            }

            $nama = $item;
            $nim = null;

            if (preg_match('/^(.*?)\s*[\-–]\s*([0-9A-Za-z.\/]+)$/u', $item, $m)) {
                $nama = trim($m[1]);
                $nim = trim($m[2]);
            } elseif (preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/u', $item, $m)) {
                $nama = trim($m[1]);
                $isi = trim($m[2]);
                // "(Pria)" / "(Wanita)" itu jenis kelamin, bukan NIM
                $nim = preg_match('/^(pria|laki-?laki|wanita|perempuan|p|l|w)$/iu', $isi) ? null : $isi;
            }

            // "Komisaris Utama: Andi" -> ambil nama setelah titik dua
            if (str_contains($nama, ':')) {
                $nama = trim(substr($nama, strpos($nama, ':') + 1));
            }

            // harus mengandung huruf supaya sisa tanda baca tidak ikut tersimpan
            if ($nama === '' || ! preg_match('/\p{L}/u', $nama)) {
                continue;
            }

            // Gelar yang terpotong koma ("Budi, S.Pt") dikembalikan ke nama
            // sebelumnya, bukan dianggap anggota baru.
            if (self::adalahGelar($nama) && $hasil !== []) {
                $akhir = array_key_last($hasil);
                $hasil[$akhir]['nama'] = mb_substr($hasil[$akhir]['nama'].', '.$nama, 0, 150);

                continue;
            }

            // Jabatan yang ditulis setelah koma ("Budi, Produksi") bukan orang.
            if (self::adalahJabatan($nama)) {
                continue;
            }

            $hasil[] = ['nama' => mb_substr($nama, 0, 150), 'nim' => $nim ? mb_substr($nim, 0, 40) : null];
        }

        return $hasil;
    }

    /**
     * Jabatan yang lazim ditulis setelah koma pada kolom anggota tim.
     *
     * Diambil dari data nyata Batch 1: "CEO" muncul 58x, "Produksi" 24x,
     * "CMO" 24x, "Keuangan" 20x — jelas jabatan, bukan nama orang.
     */
    private const JABATAN = [
        'ceo', 'cto', 'cfo', 'cmo', 'coo', 'cpo', 'cio', 'cdo', 'cso',
        'founder', 'co-founder', 'cofounder', 'co founder', 'owner', 'pemilik',
        'komisaris', 'komisaris utama', 'direktur', 'direktur utama', 'dirut',
        'manajer', 'manager', 'general manager', 'supervisor',
        'produksi', 'kepala produksi', 'manajer produksi',
        'keuangan', 'finance', 'bendahara', 'akuntan', 'akunting',
        'pemasaran', 'marketing', 'sales', 'penjualan', 'digital marketing',
        'operasional', 'operation', 'operations', 'logistik', 'distribusi',
        'business development', 'bisnis development', 'pengembangan bisnis',
        'admin', 'administrasi', 'sekretaris', 'hrd', 'sdm', 'legal',
        'quality control', 'qc', 'r&d', 'riset', 'research', 'peneliti',
        'it', 'teknis', 'teknisi', 'programmer', 'desainer', 'designer',
        'humas', 'media', 'konten', 'content', 'creative', 'kreatif',
        'inventor', 'advisor', 'mentor', 'konsultan', 'staff', 'staf',
        'ketua', 'anggota', 'wakil', 'koordinator',
    ];

    /** Apakah potongan ini nama jabatan, bukan nama orang? */
    private static function adalahJabatan(string $teks): bool
    {
        $bersih = mb_strtolower(trim($teks, ' .:-'));

        return in_array($bersih, self::JABATAN, true);
    }

    /**
     * Apakah potongan ini gelar akademik yang terpotong koma?
     * Contoh: "S.Pt", "S.T.", "M.M.", "A.Md", "Ir.", "drh."
     */
    private static function adalahGelar(string $teks): bool
    {
        $bersih = trim($teks);

        // gelar selalu memuat titik dan sangat pendek
        if (mb_strlen($bersih) > 12 || ! str_contains($bersih, '.')) {
            return false;
        }

        return (bool) preg_match('/^(?:[A-Za-z]{1,4}\.\s*){1,3}[A-Za-z]{0,4}\.?$/u', $bersih);
    }
}
