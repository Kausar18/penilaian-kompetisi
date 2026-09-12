<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Services\PendaftarImporter;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    private const DISK = 'local';

    private const FOLDER = 'import-sementara';

    public function create(): View
    {
        return view('admin.import.create', [
            'riwayat' => ImportLog::latest()->limit(8)->get(),
        ]);
    }

    /** Langkah 1: terima berkas. Kalau >1 sheet, minta pilih sheet dulu. */
    public function store(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], ['berkas' => 'berkas']);

        $path = $data['berkas']->getRealPath();
        $namaAsli = $data['berkas']->getClientOriginalName();
        $ekstensi = strtolower($data['berkas']->getClientOriginalExtension());

        $daftarSheet = in_array($ekstensi, ['csv', 'txt'], true)
            ? ['(CSV)']
            : IOFactory::createReaderForFile($path)->listWorksheetNames($path);

        $pathSementara = $data['berkas']->store(self::FOLDER, self::DISK);

        if (count($daftarSheet) <= 1) {
            return $this->jalankan($pathSementara, $daftarSheet[0] ?? null, $namaAsli, $ekstensi);
        }

        return view('admin.import.pilih-sheet', [
            'daftarSheet' => $daftarSheet,
            'pathSementara' => $pathSementara,
            'namaAsli' => $namaAsli,
            'ekstensi' => $ekstensi,
        ]);
    }

    /** Langkah 2 (hanya untuk berkas >1 sheet): impor sheet terpilih. */
    public function proses(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'path_sementara' => ['required', 'string'],
            'nama_asli' => ['required', 'string'],
            'ekstensi' => ['required', 'string'],
            'sheet' => ['required', 'string'],
        ]);

        abort_unless(Storage::disk(self::DISK)->exists($data['path_sementara']), 404);

        return $this->jalankan($data['path_sementara'], $data['sheet'], $data['nama_asli'], $data['ekstensi']);
    }

    public function template(): StreamedResponse
    {
        $header = [
            'Timestamp', 'Kategori Peserta', 'Bidang Kompetisi', 'Nama Ketua', 'Email', 'No WhatsApp',
            'Nama Tim/Usaha', 'Judul Inovasi', 'Deskripsi Singkat', 'Permasalahan', 'Solusi',
            'Target Pengguna', 'Dampak Sosial', 'Asal Institusi', 'Perguruan Tinggi', 'Fakultas/Prodi',
            'NIM Ketua', 'Semester', 'Kota', 'Provinsi', 'Link Pitch Deck', 'Link Logo', 'Anggota Tim',
        ];

        $contoh = [
            '2026-09-01 09:15:00', 'Mahasiswa', 'Food & Beverages (F&B)', 'Budi Santoso',
            'budi@example.com', '081234567890', 'Tim Bomcha', 'Bomcha: Inovasi Pangan Halal',
            'Produk pangan berbasis potensi lokal.', 'Nilai tambah produk lokal masih rendah.',
            'Hilirisasi jadi produk siap konsumsi.', 'Mahasiswa & keluarga muda.',
            'Membuka lapangan kerja pesisir.', 'Universitas Contoh', 'Universitas Contoh',
            'Fakultas Teknik / Teknik Informatika', '13020230312', '6', 'Kota Makassar',
            'Sulawesi Selatan', 'https://drive.google.com/xxx', 'https://drive.google.com/yyy',
            'Leon Octa - 13020240269; Zulkifli - 13020240266',
        ];

        return Csv::unduh('template-import-pendaftar', $header, [$contoh]);
    }

    private function jalankan(string $pathSementara, ?string $sheet, string $namaAsli, string $ekstensi): RedirectResponse
    {
        $pathAsli = Storage::disk(self::DISK)->path($pathSementara);

        try {
            $reader = IOFactory::createReaderForFile($pathAsli);
            $reader->setReadDataOnly(true);

            if ($sheet && $sheet !== '(CSV)' && method_exists($reader, 'setLoadSheetsOnly')) {
                $reader->setLoadSheetsOnly([$sheet]);
            }

            $spreadsheet = $reader->load($pathAsli);
            $baris = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $hasil = app(PendaftarImporter::class)->jalankan($baris, $namaAsli, auth()->id());
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($pathSementara);

            return redirect()->route('admin.pendaftar.import')
                ->withErrors(['berkas' => 'Import gagal: '.$e->getMessage()]);
        }

        Storage::disk(self::DISK)->delete($pathSementara);

        $pesan = "Berhasil impor {$hasil['berhasil']} pendaftar.";
        if ($hasil['gagal'] > 0) {
            $pesan .= " {$hasil['gagal']} baris gagal — lihat detail di bawah.";
        }

        return redirect()->route('admin.pendaftar.import')
            ->with('sukses', $pesan)
            ->with('catatanGagal', $hasil['catatan'] ?? []);
    }
}
