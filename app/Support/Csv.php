<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helper ekspor CSV sederhana tanpa paket tambahan.
 * Menyisipkan BOM UTF-8 supaya Excel di Windows membaca karakter Indonesia
 * dengan benar.
 */
class Csv
{
    /**
     * @param  string  $namaFile  nama file tanpa ekstensi
     * @param  array<int,string>  $header  baris judul kolom
     * @param  iterable  $baris  tiap item = array nilai kolom (urutannya sama dengan $header)
     */
    public static function unduh(string $namaFile, array $header, iterable $baris): StreamedResponse
    {
        $namaFile = trim($namaFile) ?: 'export';
        $namaFile .= '_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($header, $baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($out, $header);

            foreach ($baris as $row) {
                fputcsv($out, array_map(static fn ($v) => $v instanceof \Stringable ? (string) $v : $v, (array) $row));
            }

            fclose($out);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
