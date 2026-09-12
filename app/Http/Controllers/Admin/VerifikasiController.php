<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidangKompetisi;
use App\Models\DetailVerifikasi;
use App\Models\ItemVerifikasi;
use App\Models\KategoriPeserta;
use App\Models\Pendaftar;
use App\Models\VerifikasiAdministrasi;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tahap 1 — Verifikasi Administrasi (Form Seleksi Administrasi sesuai Juklak).
 * Hasil "Lolos" memasang status pendaftar jadi Terverifikasi sehingga peserta
 * masuk ke tahap Penilaian.
 */
class VerifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $pendaftar = $this->query($request)
            ->with(['kategoriPeserta', 'bidangKompetisi', 'verifikasi.verifikator'])
            ->orderBy('nama_tim')
            ->paginate(20)
            ->withQueryString();

        return view('admin.verifikasi.index', [
            'pendaftar' => $pendaftar,
            'kartu' => $this->kartu(),
            'jmlItem' => ItemVerifikasi::aktif()->count(),
            'daftarKategori' => KategoriPeserta::orderBy('urutan')->get(),
            'daftarBidang' => BidangKompetisi::orderBy('urutan')->get(),
        ]);
    }

    public function edit(Pendaftar $pendaftar): View
    {
        $pendaftar->load(['kategoriPeserta', 'bidangKompetisi', 'anggotaTim']);

        $verifikasi = $pendaftar->verifikasi()->with('verifikator')->firstOrNew([]);

        $tersimpan = $verifikasi->exists
            ? $verifikasi->detail()->get()->keyBy('item_verifikasi_id')
            : collect();

        return view('admin.verifikasi.edit', [
            'p' => $pendaftar,
            'verifikasi' => $verifikasi,
            'kelompok' => ItemVerifikasi::aktif()->terurut()->get()->groupBy('kelompok'),
            'tersimpan' => $tersimpan,
        ]);
    }

    public function update(Request $request, Pendaftar $pendaftar): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['array'],
            'status.*' => ['nullable', Rule::in(array_keys(DetailVerifikasi::STATUS))],
            'catatan_item' => ['array'],
            'catatan_item.*' => ['nullable', 'string', 'max:1000'],
            'hasil' => ['nullable', Rule::in(array_keys(VerifikasiAdministrasi::HASIL))],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'hasil' => 'rekomendasi',
        ]);

        $item = ItemVerifikasi::aktif()->pluck('id');
        $hasil = $data['hasil'] ?? null;

        DB::transaction(function () use ($pendaftar, $request, $data, $item, $hasil) {
            $verifikasi = VerifikasiAdministrasi::updateOrCreate(
                ['pendaftar_id' => $pendaftar->id],
                [
                    'verifikator_id' => $request->user()->id,
                    'hasil' => $hasil,
                    'catatan' => $data['catatan'] ?? null,
                    'diverifikasi_at' => $hasil ? now() : null,
                ],
            );

            foreach ($item as $itemId) {
                $verifikasi->detail()->updateOrCreate(
                    ['item_verifikasi_id' => $itemId],
                    [
                        'status' => $data['status'][$itemId] ?? null,
                        'catatan' => $data['catatan_item'][$itemId] ?? null,
                    ],
                );
            }

            // hasil verifikasi menentukan status pendaftar (gerbang ke tahap penilaian)
            if ($hasil) {
                $pendaftar->update(['status' => VerifikasiAdministrasi::STATUS_PENDAFTAR[$hasil]]);
            }
        });

        $pesan = $hasil
            ? "Verifikasi \"{$pendaftar->nama_tim}\" disimpan — dinyatakan ".VerifikasiAdministrasi::HASIL[$hasil].'.'
            : "Verifikasi \"{$pendaftar->nama_tim}\" disimpan sebagai draft (rekomendasi belum diputuskan).";

        return redirect()->route('admin.verifikasi.index')->with('sukses', $pesan);
    }

    public function export(Request $request): StreamedResponse
    {
        $item = ItemVerifikasi::aktif()->terurut()->get();

        $baris = $this->query($request)
            ->with(['kategoriPeserta', 'bidangKompetisi', 'verifikasi.verifikator', 'verifikasi.detail'])
            ->orderBy('nama_tim')
            ->lazy()
            ->map(function (Pendaftar $p) use ($item) {
                $v = $p->verifikasi;
                $detail = $v ? $v->detail->keyBy('item_verifikasi_id') : collect();

                $centang = $item->map(function ($i) use ($detail) {
                    $status = $detail[$i->id]->status ?? null;

                    return $status ? DetailVerifikasi::STATUS[$status] : '';
                })->all();

                return array_merge([
                    $p->nama_tim,
                    $p->judul_inovasi,
                    $p->nama_ketua,
                    $p->kategoriPeserta?->nama,
                    $p->bidangKompetisi?->nama,
                ], $centang, [
                    $v?->hasil_label ?? '',
                    $v?->catatan,
                    $v?->verifikator?->name,
                    optional($v?->diverifikasi_at)->format('Y-m-d H:i'),
                ]);
            });

        $header = array_merge(
            ['Tim/Usaha', 'Judul Inovasi', 'Ketua', 'Kategori', 'Bidang'],
            $item->map(fn ($i) => $i->nama)->all(),
            ['Hasil', 'Catatan Verifikator', 'Verifikator', 'Tanggal Verifikasi'],
        );

        return Csv::unduh('verifikasi-administrasi', $header, $baris);
    }

    /** Query + filter bersama index & export. */
    private function query(Request $request)
    {
        $hasil = $request->input('hasil');

        return Pendaftar::query()
            ->cari($request->input('q'))
            ->kategori($request->input('kategori'))
            ->bidang($request->input('bidang'))
            ->when($hasil === 'belum', fn ($q) => $q->where(fn ($w) => $w
                ->whereDoesntHave('verifikasi')
                ->orWhereHas('verifikasi', fn ($v) => $v->whereNull('hasil'))
            ))
            ->when($hasil === 'lolos', fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'lolos')))
            ->when($hasil === 'tidak_lolos', fn ($q) => $q->whereHas('verifikasi', fn ($v) => $v->where('hasil', 'tidak_lolos')));
    }

    private function kartu(): array
    {
        $total = Pendaftar::count();
        $lolos = VerifikasiAdministrasi::where('hasil', 'lolos')->count();
        $tidak = VerifikasiAdministrasi::where('hasil', 'tidak_lolos')->count();

        return [
            'total' => $total,
            'lolos' => $lolos,
            'tidak_lolos' => $tidak,
            'belum' => max(0, $total - $lolos - $tidak),
        ];
    }
}
