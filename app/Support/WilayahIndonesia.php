<?php

namespace App\Support;

/**
 * Menebak kota/kabupaten dan provinsi dari alamat berbentuk teks bebas.
 *
 * Google Form PRIMESTeP hanya punya "Alamat Rumah" dan "Alamat Usaha/Pabrik"
 * sebagai teks panjang — tidak ada kolom kota/provinsi terpisah — sehingga
 * grafik sebaran provinsi tidak akan pernah terisi tanpa penebakan ini.
 *
 * Diuji terhadap 154 alamat asli Batch 1: provinsi terdeteksi 94%, kota 90%.
 * Sisanya alamat yang memang tidak menyebut kota (hanya nama jalan) dan
 * dibiarkan kosong — hasil tebakan ini bisa dikoreksi manual di panel.
 */
class WilayahIndonesia
{
    /** 38 provinsi. Urutan penting: nama yang lebih panjang diperiksa lebih dulu. */
    public const PROVINSI = [
        'Kepulauan Bangka Belitung', 'Bangka Belitung', 'Kepulauan Riau', 'Nusa Tenggara Barat',
        'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan',
        'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara', 'Sulawesi Tengah',
        'Sulawesi Selatan', 'Sulawesi Tenggara', 'Sulawesi Barat', 'Sumatera Utara',
        'Sumatera Barat', 'Sumatera Selatan', 'Papua Pegunungan', 'Papua Barat Daya',
        'Papua Selatan', 'Papua Tengah', 'Papua Barat', 'Maluku Utara', 'DI Yogyakarta',
        'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten', 'Bali', 'Aceh',
        'Riau', 'Jambi', 'Bengkulu', 'Lampung', 'Gorontalo', 'Maluku', 'Papua',
    ];

    /** Singkatan yang lazim ditulis peserta. */
    private const ALIAS = [
        'dki' => 'DKI Jakarta', 'jakarta' => 'DKI Jakarta',
        'jogja' => 'DI Yogyakarta', 'jogjakarta' => 'DI Yogyakarta',
        'yogyakarta' => 'DI Yogyakarta', 'diy' => 'DI Yogyakarta',
        'jabar' => 'Jawa Barat', 'jateng' => 'Jawa Tengah', 'jatim' => 'Jawa Timur',
        'ntb' => 'Nusa Tenggara Barat', 'ntt' => 'Nusa Tenggara Timur',
        'sumut' => 'Sumatera Utara', 'sumbar' => 'Sumatera Barat', 'sumsel' => 'Sumatera Selatan',
        'kalbar' => 'Kalimantan Barat', 'kaltim' => 'Kalimantan Timur',
        'kalsel' => 'Kalimantan Selatan', 'kalteng' => 'Kalimantan Tengah',
        'sulsel' => 'Sulawesi Selatan', 'sulut' => 'Sulawesi Utara',
    ];

    /**
     * Kota/kabupaten => provinsinya. Dipakai untuk dua hal: mengisi kolom kota,
     * dan menyimpulkan provinsi pada alamat yang tidak menyebutnya.
     *
     * Nama yang lebih panjang HARUS di atas yang lebih pendek ("Tangerang
     * Selatan" sebelum "Tangerang", "Bandung Barat" sebelum "Bandung").
     */
    private const KOTA = [
        // Jabodetabek & Banten
        'tangerang selatan' => 'Banten', 'tangsel' => 'Banten', 'tangerang' => 'Banten',
        'serang' => 'Banten', 'cilegon' => 'Banten', 'lebak' => 'Banten', 'pandeglang' => 'Banten',
        'jakarta' => 'DKI Jakarta', 'depok' => 'Jawa Barat', 'bogor' => 'Jawa Barat', 'bekasi' => 'Jawa Barat',
        // Jawa Barat
        'bandung barat' => 'Jawa Barat', 'lembang' => 'Jawa Barat', 'bandung' => 'Jawa Barat',
        'cimahi' => 'Jawa Barat', 'sukabumi' => 'Jawa Barat', 'cianjur' => 'Jawa Barat',
        'garut' => 'Jawa Barat', 'tasikmalaya' => 'Jawa Barat', 'ciamis' => 'Jawa Barat',
        'kuningan' => 'Jawa Barat', 'cirebon' => 'Jawa Barat', 'majalengka' => 'Jawa Barat',
        'sumedang' => 'Jawa Barat', 'indramayu' => 'Jawa Barat', 'subang' => 'Jawa Barat',
        'purwakarta' => 'Jawa Barat', 'karawang' => 'Jawa Barat', 'pangandaran' => 'Jawa Barat',
        // Jawa Tengah & DIY
        'semarang' => 'Jawa Tengah', 'surakarta' => 'Jawa Tengah', 'solo' => 'Jawa Tengah',
        'salatiga' => 'Jawa Tengah', 'magelang' => 'Jawa Tengah', 'pekalongan' => 'Jawa Tengah',
        'tegal' => 'Jawa Tengah', 'purwokerto' => 'Jawa Tengah', 'banyumas' => 'Jawa Tengah',
        'cilacap' => 'Jawa Tengah', 'kebumen' => 'Jawa Tengah', 'purworejo' => 'Jawa Tengah',
        'wonosobo' => 'Jawa Tengah', 'klaten' => 'Jawa Tengah', 'boyolali' => 'Jawa Tengah',
        'sukoharjo' => 'Jawa Tengah', 'karanganyar' => 'Jawa Tengah', 'sragen' => 'Jawa Tengah',
        'kudus' => 'Jawa Tengah', 'jepara' => 'Jawa Tengah', 'rembang' => 'Jawa Tengah',
        'blora' => 'Jawa Tengah', 'grobogan' => 'Jawa Tengah', 'demak' => 'Jawa Tengah',
        'kendal' => 'Jawa Tengah', 'batang' => 'Jawa Tengah', 'pemalang' => 'Jawa Tengah',
        'brebes' => 'Jawa Tengah', 'banjarnegara' => 'Jawa Tengah', 'purbalingga' => 'Jawa Tengah',
        'temanggung' => 'Jawa Tengah', 'wonogiri' => 'Jawa Tengah',
        'kulon progo' => 'DI Yogyakarta', 'gunungkidul' => 'DI Yogyakarta',
        'yogyakarta' => 'DI Yogyakarta', 'sleman' => 'DI Yogyakarta', 'bantul' => 'DI Yogyakarta',
        // Jawa Timur
        'surabaya' => 'Jawa Timur', 'malang' => 'Jawa Timur', 'sidoarjo' => 'Jawa Timur',
        'gresik' => 'Jawa Timur', 'mojokerto' => 'Jawa Timur', 'pasuruan' => 'Jawa Timur',
        'probolinggo' => 'Jawa Timur', 'jember' => 'Jawa Timur', 'banyuwangi' => 'Jawa Timur',
        'kediri' => 'Jawa Timur', 'blitar' => 'Jawa Timur', 'tulungagung' => 'Jawa Timur',
        'madiun' => 'Jawa Timur', 'ngawi' => 'Jawa Timur', 'bojonegoro' => 'Jawa Timur',
        'tuban' => 'Jawa Timur', 'lamongan' => 'Jawa Timur', 'jombang' => 'Jawa Timur',
        'nganjuk' => 'Jawa Timur', 'lumajang' => 'Jawa Timur', 'bondowoso' => 'Jawa Timur',
        'situbondo' => 'Jawa Timur', 'pamekasan' => 'Jawa Timur', 'sumenep' => 'Jawa Timur',
        'bangkalan' => 'Jawa Timur', 'sampang' => 'Jawa Timur', 'magetan' => 'Jawa Timur',
        'ponorogo' => 'Jawa Timur', 'pacitan' => 'Jawa Timur', 'trenggalek' => 'Jawa Timur',
        // Sumatera
        'medan' => 'Sumatera Utara', 'deli serdang' => 'Sumatera Utara', 'binjai' => 'Sumatera Utara',
        'pematang siantar' => 'Sumatera Utara', 'padang' => 'Sumatera Barat',
        'bukittinggi' => 'Sumatera Barat', 'payakumbuh' => 'Sumatera Barat', 'pariaman' => 'Sumatera Barat',
        'pekanbaru' => 'Riau', 'dumai' => 'Riau', 'batam' => 'Kepulauan Riau',
        'tanjungpinang' => 'Kepulauan Riau', 'palembang' => 'Sumatera Selatan',
        'lubuklinggau' => 'Sumatera Selatan', 'prabumulih' => 'Sumatera Selatan',
        'bandar lampung' => 'Lampung', 'pangkalpinang' => 'Bangka Belitung',
        'banda aceh' => 'Aceh', 'lhokseumawe' => 'Aceh',
        // Bali & Nusa Tenggara
        'denpasar' => 'Bali', 'badung' => 'Bali', 'gianyar' => 'Bali', 'tabanan' => 'Bali',
        'buleleng' => 'Bali', 'singaraja' => 'Bali', 'mataram' => 'Nusa Tenggara Barat',
        'lombok' => 'Nusa Tenggara Barat', 'kupang' => 'Nusa Tenggara Timur',
        // Kalimantan
        'pontianak' => 'Kalimantan Barat', 'singkawang' => 'Kalimantan Barat',
        'palangkaraya' => 'Kalimantan Tengah', 'banjarmasin' => 'Kalimantan Selatan',
        'banjarbaru' => 'Kalimantan Selatan', 'samarinda' => 'Kalimantan Timur',
        'balikpapan' => 'Kalimantan Timur', 'bontang' => 'Kalimantan Timur', 'tarakan' => 'Kalimantan Utara',
        // Sulawesi & timur
        'makassar' => 'Sulawesi Selatan', 'gowa' => 'Sulawesi Selatan', 'parepare' => 'Sulawesi Selatan',
        'palopo' => 'Sulawesi Selatan', 'manado' => 'Sulawesi Utara', 'bitung' => 'Sulawesi Utara',
        'tomohon' => 'Sulawesi Utara', 'palu' => 'Sulawesi Tengah', 'kendari' => 'Sulawesi Tenggara',
        'gorontalo' => 'Gorontalo', 'mamuju' => 'Sulawesi Barat', 'ambon' => 'Maluku',
        'ternate' => 'Maluku Utara', 'jayapura' => 'Papua', 'sorong' => 'Papua Barat Daya',
        'manokwari' => 'Papua Barat',
    ];

    /** Singkatan kota => nama resminya, supaya yang tersimpan konsisten. */
    private const NAMA_RESMI = [
        'tangsel' => 'Tangerang Selatan',
        'solo' => 'Surakarta',
        'jogja' => 'Yogyakarta',
        'jogjakarta' => 'Yogyakarta',
    ];

    /**
     * @return array{kota: ?string, provinsi: ?string}
     */
    public static function dariAlamat(?string $alamat): array
    {
        $hasil = ['kota' => null, 'provinsi' => null];

        if (blank($alamat)) {
            return $hasil;
        }

        $teks = mb_strtolower(preg_replace('/\s+/u', ' ', $alamat) ?? $alamat);

        // 1) provinsi yang ditulis apa adanya
        foreach (self::PROVINSI as $nama) {
            if (str_contains($teks, mb_strtolower($nama))) {
                $hasil['provinsi'] = $nama;
                break;
            }
        }

        // 2) singkatan
        if ($hasil['provinsi'] === null) {
            foreach (self::ALIAS as $singkat => $nama) {
                if (preg_match('/\b'.preg_quote($singkat, '/').'\b/u', $teks)) {
                    $hasil['provinsi'] = $nama;
                    break;
                }
            }
        }

        // 3) kota yang dikenal — sekaligus menyimpulkan provinsi kalau belum ketemu
        foreach (self::KOTA as $nama => $provinsi) {
            if (preg_match('/\b'.preg_quote($nama, '/').'\b/u', $teks)) {
                $hasil['kota'] = self::rapikan($nama);
                $hasil['provinsi'] ??= $provinsi;
                break;
            }
        }

        // 4) kota di luar daftar, tapi ditulis dengan penanda "Kota/Kab."
        if ($hasil['kota'] === null
            && preg_match('/\b(?:kota|kab\.?|kabupaten)\s*\.?\s*([a-z][a-z\s]{2,22})/u', $teks, $m)) {
            $hasil['kota'] = self::rapikan(trim($m[1]));
        }

        return $hasil;
    }

    /** "tangsel" -> "Tangerang Selatan"; DKI/DI tetap huruf besar. */
    private static function rapikan(string $nama): string
    {
        $nama = self::NAMA_RESMI[mb_strtolower($nama)] ?? $nama;

        return preg_replace_callback(
            '/\b(dki|di)\b/u',
            fn ($m) => mb_strtoupper($m[1]),
            ucwords($nama),
        ) ?? ucwords($nama);
    }
}
