<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKompetisi;
use App\Models\IndikatorPenilaian;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendaftarController extends Controller
{
    public function index(Request $request): View
    {
        $pendaftar = $this->query($request)
            ->with(['kategoriPeserta', 'bidangKompetisi'])
            ->latest('tanggal_daftar')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pendaftar.index', [
            'pendaftar' => $pendaftar,
            'daftarKategori' => KategoriPeserta::orderBy('urutan')->get(),
            'daftarBidang' => BidangKompetisi::orderBy('urutan')->get(),
        ]);
    }

    public function show(Pendaftar $pendaftar): View
    {
        $pendaftar->load(['kategoriPeserta', 'bidangKompetisi', 'anggotaTim', 'penilaian']);

        return view('admin.pendaftar.show', [
            'p' => $pendaftar,
            'nilaiMaks' => $this->nilaiMaksimum(),
        ]);
    }

    /** Partial detail (tanpa layout) untuk panel geser di halaman daftar. */
    public function kartu(Pendaftar $pendaftar): View
    {
        $pendaftar->load(['kategoriPeserta', 'bidangKompetisi', 'anggotaTim', 'penilaian']);

        return view('admin.pendaftar._kartu', [
            'p' => $pendaftar,
            'nilaiMaks' => $this->nilaiMaksimum(),
        ]);
    }

    // Catatan: dulu ada updateStatus() yang mengubah status verifikasi lewat
    // dropdown di panel detail. Dihapus atas keputusan 12 Sep 2026 — status
    // pendaftar kini BACA-SAJA dan hanya berubah lewat menu Verifikasi
    // Administrasi, supaya setiap keputusan lolos/tidak lolos selalu punya
    // jejak butir Juklak mana yang gagal beserta catatannya.

    /** Nilai akhir tertinggi menurut pengaturan penilaian, untuk tampilan "x / maks". */
    private function nilaiMaksimum(): string
    {
        $maks = PengaturanPenilaian::aktif()
            ->nilaiMaksimum((float) IndikatorPenilaian::aktif()->sum('bobot'));

        return rtrim(rtrim(number_format($maks, 2, '.', ''), '0'), '.');
    }

    public function destroy(Pendaftar $pendaftar): RedirectResponse
    {
        $nama = $pendaftar->nama_tim;
        $pendaftar->delete();

        return redirect()
            ->route('admin.pendaftar.index')
            ->with('sukses', "Pendaftar \"{$nama}\" beserta penilaiannya telah dihapus.");
    }

    public function export(Request $request): StreamedResponse
    {
        $baris = $this->query($request)
            ->with(['kategoriPeserta', 'bidangKompetisi'])
            ->orderBy('id')
            ->lazy()
            ->map(fn (Pendaftar $p) => [
                $p->nomor,
                optional($p->tanggal_daftar)->format('Y-m-d'),
                $p->kategoriPeserta?->nama,
                $p->nama_ketua,
                $p->no_wa,
                $p->nama_tim,
                $p->judul_inovasi,
                $p->bidangKompetisi?->nama,
                $p->asal_institusi,
                $p->kota,
                $p->provinsi,
                $p->status,
                $p->link_pitchdeck,
            ]);

        return Csv::unduh('pendaftar', [
            'Nomor', 'Tanggal Daftar', 'Kategori', 'Nama Ketua', 'No WA',
            'Nama Tim', 'Judul Inovasi', 'Bidang', 'Asal Institusi', 'Kota', 'Provinsi',
            'Status', 'Link Pitch Deck',
        ], $baris);
    }

    /** Query dasar + filter, dipakai bersama oleh index & export. */
    private function query(Request $request)
    {
        return Pendaftar::query()
            ->cari($request->input('q'))
            ->kategori($request->input('kategori'))
            ->bidang($request->input('bidang'))
            ->status($request->input('status'));
    }
}
