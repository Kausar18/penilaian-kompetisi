<?php

namespace Tests\Feature;

use App\Models\IndikatorPenilaian;
use App\Models\KategoriPenilaian;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\User;
use App\Models\VerifikasiAdministrasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanPenilaianTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('peran', 'admin')->firstOrFail();
    }

    /** Tandai pendaftar lolos verifikasi administrasi (gerbang ke tahap penilaian). */
    private function loloskan(Pendaftar $pendaftar): Pendaftar
    {
        VerifikasiAdministrasi::create([
            'pendaftar_id' => $pendaftar->id,
            'hasil' => 'lolos',
            'diverifikasi_at' => now(),
        ]);
        $pendaftar->update(['status' => 'verified']);

        return $pendaftar;
    }

    /** Semua indikator aktif diberi skor yang sama, lalu kembalikan penilaian tersimpan. */
    private function nilaiSemua(Pendaftar $pendaftar, int $skor): void
    {
        $skorMap = IndikatorPenilaian::aktif()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $skor])
            ->all();

        $this->actingAs($this->admin)
            ->put(route('admin.penilaian.update', $pendaftar), [
                'skor' => $skorMap,
                'rekomendasi' => 'lolos',
                'catatan_reviewer' => 'uji',
            ])
            ->assertRedirect();
    }

    public function test_pengaturan_default_terbentuk(): void
    {
        $p = PengaturanPenilaian::aktif();

        // default mengikuti Form Penilaian Substansi (9 ideal ... 1 kurang)
        $this->assertSame([1, 3, 5, 7, 9], $p->daftarNilai());
        $this->assertSame('satu_sembilan', $p->presetSaatIni());
        $this->assertSame('mentah', $p->rumus);
        $this->assertTrue($p->hanya_terverifikasi);
    }

    public function test_admin_bisa_ganti_skala_dan_rumus(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.rubrik.pengaturan'), [
                'preset' => 'satu_tujuh',
                'rumus' => 'mentah',
                'hanya_terverifikasi' => '1',
            ])
            ->assertRedirect();

        $p = PengaturanPenilaian::aktif()->refresh();

        $this->assertSame([1, 3, 5, 7], $p->daftarNilai());
        $this->assertSame('mentah', $p->rumus);
        $this->assertSame('satu_tujuh', $p->presetSaatIni());
    }

    public function test_reviewer_tidak_bisa_ganti_pengaturan(): void
    {
        $reviewer = User::factory()->create(['peran' => 'reviewer']);

        $this->actingAs($reviewer)
            ->put(route('admin.rubrik.pengaturan'), ['preset' => 'satu_tujuh', 'rumus' => 'mentah'])
            ->assertForbidden();
    }

    public function test_rumus_normalisasi_menghasilkan_total_bobot(): void
    {
        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET['nol_lima']['skala'],
            'rumus' => 'normalisasi',
        ]);

        // 0-5 + normalisasi: semua skor tertinggi -> nilai akhir = total bobot (100)
        $pendaftar = Pendaftar::factory()->create(['status' => 'verified']);
        $this->nilaiSemua($pendaftar, 5);

        $this->assertEquals(100.0, (float) $pendaftar->penilaian()->first()->nilai_final);
    }

    public function test_rumus_mentah_mengalikan_nilai_dengan_bobot(): void
    {
        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET['satu_tujuh']['skala'],
            'rumus' => 'mentah',
        ]);

        // skala 1/3/5/7 + mentah: semua skor 7 -> 7 x total bobot (100) = 700
        $pendaftar = Pendaftar::factory()->create(['status' => 'verified']);
        $this->nilaiSemua($pendaftar, 7);

        $this->assertEquals(700.0, (float) $pendaftar->penilaian()->first()->nilai_final);
    }

    /** Ganti rumus setelah ada nilai tersimpan -> nilai lama ikut dihitung ulang, tidak basi. */
    public function test_ganti_pengaturan_menghitung_ulang_nilai_tersimpan(): void
    {
        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET['nol_lima']['skala'],
            'rumus' => 'normalisasi',
        ]);

        $pendaftar = $this->loloskan(Pendaftar::factory()->create());
        $this->nilaiSemua($pendaftar, 5);
        $this->assertEquals(100.0, (float) $pendaftar->penilaian()->first()->nilai_final);

        // pindah ke rumus mentah lewat halaman Rubrik
        $this->actingAs($this->admin)
            ->put(route('admin.rubrik.pengaturan'), [
                'preset' => 'nol_lima',
                'rumus' => 'mentah',
                'hanya_terverifikasi' => '1',
            ])->assertRedirect();

        // 5 x total bobot (100) = 500, bukan lagi 100
        $this->assertEquals(500.0, (float) $pendaftar->penilaian()->first()->nilai_final);
    }

    /** Menambah indikator baru membuat penilaian lama turun jadi draft (belum lengkap). */
    public function test_indikator_baru_menurunkan_penilaian_lama_jadi_draft(): void
    {
        $pendaftar = $this->loloskan(Pendaftar::factory()->create());
        $this->nilaiSemua($pendaftar, 5);
        $this->assertNotNull($pendaftar->penilaian()->first()->dinilai_at);

        $this->actingAs($this->admin)
            ->post(route('admin.rubrik.indikator.store'), [
                'kategori_penilaian_id' => KategoriPenilaian::firstOrFail()->id,
                'nama' => 'Indikator Tambahan',
                'bobot' => 5,
            ])->assertRedirect();

        $this->assertNull($pendaftar->penilaian()->first()->dinilai_at);
    }

    /** Rubrik bawaan harus persis mengikuti Form Penilaian Substansi. */
    public function test_rubrik_bawaan_sesuai_form_substansi(): void
    {
        $kelompok = KategoriPenilaian::orderBy('urutan')->get();

        $this->assertSame(
            ['Tim', 'Produk dan Model Bisnis', 'Pasar dan Kompetisi', 'Strategi',
                'Pengelolaan Bisnis', 'Traction / Perkembangan Usaha'],
            $kelompok->pluck('nama')->all(),
        );

        $this->assertSame([20, 25, 15, 20, 5, 15], $kelompok->pluck('bobot_persen')->map(fn ($b) => (int) $b)->all());
        $this->assertSame(17, IndikatorPenilaian::aktif()->count());   // 3+4+2+3+1+4
        $this->assertSame(100.0, (float) IndikatorPenilaian::aktif()->sum('bobot'));

        // bobot tiap kelompok = jumlah bobot indikatornya
        foreach ($kelompok as $k) {
            $this->assertSame(
                (float) $k->bobot_persen,
                (float) $k->indikatorAktif()->sum('bobot'),
                "bobot kelompok {$k->nama} tidak cocok dengan indikatornya",
            );
        }

        // skala 9 tertinggi x total bobot 100
        $this->assertSame(900.0, (float) PengaturanPenilaian::aktif()->nilaiMaksimum(100.0));
    }

    /** Nilai maksimum form substansi = 900 (semua indikator diberi 9). */
    public function test_semua_skor_ideal_menghasilkan_900(): void
    {
        $pendaftar = $this->loloskan(Pendaftar::factory()->create());
        $this->nilaiSemua($pendaftar, 9);

        $this->assertEquals(900.0, (float) $pendaftar->penilaian()->first()->nilai_final);
    }

    /** Bagian Catatan Verifikasi RAB ikut tersimpan. */
    public function test_catatan_rab_dan_kesimpulan_tersimpan(): void
    {
        $pendaftar = $this->loloskan(Pendaftar::factory()->create());

        $skorMap = IndikatorPenilaian::aktif()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => 7])->all();

        $this->actingAs($this->admin)
            ->put(route('admin.penilaian.update', $pendaftar), [
                'skor' => $skorMap,
                'rekomendasi' => 'lolos',
                'catatan_rab' => 'Ada komponen belanja modal yang perlu dikurangi.',
                'rekomendasi_anggaran' => 'Direkomendasikan Rp 45.000.000.',
                'catatan_reviewer' => 'Layak didanai dengan catatan perbaikan RAB.',
            ])
            ->assertRedirect();

        $n = $pendaftar->penilaian()->firstOrFail();

        $this->assertSame('Ada komponen belanja modal yang perlu dikurangi.', $n->catatan_rab);
        $this->assertSame('Direkomendasikan Rp 45.000.000.', $n->rekomendasi_anggaran);
        $this->assertSame('Layak didanai dengan catatan perbaikan RAB.', $n->catatan_reviewer);

        // ikut terbawa ke halaman edit dan ke export rekap
        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.edit', $pendaftar))
            ->assertOk()
            ->assertSee('Direkomendasikan Rp 45.000.000.');

        $isi = $this->actingAs($this->admin)->get(route('admin.rekap.export'))->streamedContent();

        $this->assertStringContainsString('Rekomendasi Anggaran', $isi);
        $this->assertStringContainsString('Direkomendasikan Rp 45.000.000.', $isi);
    }

    public function test_skor_di_luar_skala_ditolak(): void
    {
        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET['satu_tujuh']['skala'],
        ]);

        $pendaftar = Pendaftar::factory()->create(['status' => 'verified']);
        $indikator = IndikatorPenilaian::aktif()->first();

        // 4 tidak ada di skala 1/3/5/7
        $this->actingAs($this->admin)
            ->put(route('admin.penilaian.update', $pendaftar), ['skor' => [$indikator->id => 4]])
            ->assertSessionHasErrors('skor.'.$indikator->id);
    }

    public function test_gating_menyembunyikan_pendaftar_belum_terverifikasi(): void
    {
        $this->loloskan(Pendaftar::factory()->create(['nama_tim' => 'Tim Sudah Verif']));
        Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Verif', 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertOk()
            ->assertSee('Tim Sudah Verif')
            ->assertDontSee('Tim Belum Verif');
    }

    public function test_gating_bisa_dimatikan(): void
    {
        PengaturanPenilaian::aktif()->update(['hanya_terverifikasi' => false]);

        Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Verif', 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertOk()
            ->assertSee('Tim Belum Verif');
    }
}
