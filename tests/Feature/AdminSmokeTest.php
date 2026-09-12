<?php

namespace Tests\Feature;

use App\Models\IndikatorPenilaian;
use App\Models\KategoriPenilaian;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\Penilaian;
use App\Models\User;
use App\Support\ParserData;
use App\Support\WilayahIndonesia;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('peran', 'admin')->firstOrFail();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        // root sekarang halaman publik (tanpa login); yang dijaga adalah panel
        $this->get('/')->assertOk();
        $this->get(route('admin.statistik'))->assertRedirect(route('login'));
        $this->get(route('admin.penilaian.index'))->assertRedirect(route('login'));
    }

    public function test_login_pakai_username(): void
    {
        $this->admin->update(['username' => 'admin', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.statistik'));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_login_gagal_kalau_sandi_salah(): void
    {
        $this->admin->update(['username' => 'admin', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'salah',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_semua_halaman_admin_utama_terbuka(): void
    {
        $this->actingAs($this->admin);
        $pendaftar = Pendaftar::factory()->create();

        $this->get(route('admin.statistik'))->assertOk();
        $this->get(route('admin.pendaftar.index'))->assertOk();
        $this->get(route('admin.pendaftar.show', $pendaftar))->assertOk();
        $this->get(route('admin.pendaftar.kartu', $pendaftar))
            ->assertOk()
            ->assertSee($pendaftar->nama_tim)
            ->assertDontSee('<!DOCTYPE', false);
        $this->get(route('admin.pendaftar.import'))->assertOk();
        $this->get(route('admin.pengunjung'))->assertOk();
        $this->get(route('admin.penilaian.index'))->assertOk();
        $this->get(route('admin.penilaian.edit', $pendaftar))->assertOk();
        $this->get(route('admin.rekap.index'))->assertOk();
        $this->get(route('admin.rubrik.index'))->assertOk();
        $this->get(route('admin.penugasan.index'))->assertOk();
    }

    /**
     * Status pendaftar BACA-SAJA: tidak ada lagi jalan pintas mengubahnya tanpa
     * mengisi checklist Juklak. Rutenya dihapus, bukan sekadar disembunyikan.
     */
    public function test_status_pendaftar_tidak_bisa_diubah_langsung(): void
    {
        $this->actingAs($this->admin);
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted']);

        $this->assertFalse(
            Route::has('admin.pendaftar.status'),
            'rute ubah status seharusnya sudah dihapus',
        );

        // request langsung ke URL lamanya pun tidak ada yang menanganinya
        $this->put("/admin/pendaftar/{$pendaftar->id}/status", ['status' => 'verified'])
            ->assertNotFound();

        $this->assertSame('submitted', $pendaftar->fresh()->status);

        // halaman detail menampilkan status sebagai teks + jalan ke checklist
        $this->get(route('admin.pendaftar.show', $pendaftar))
            ->assertOk()
            ->assertSee('Submitted')
            ->assertSee(route('admin.verifikasi.edit', $pendaftar), false);
    }

    public function test_export_csv_tiap_modul(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.pendaftar.export'))->assertOk();
        $this->get(route('admin.penilaian.export'))->assertOk();
        $this->get(route('admin.rekap.export'))->assertOk();
        $this->get(route('admin.pendaftar.import.template'))->assertOk();
    }

    public function test_simpan_penilaian_lengkap_menghitung_nilai_akhir(): void
    {
        $this->actingAs($this->admin);

        // uji jalur normalisasi secara eksplisit (skala 0-5) supaya tidak ikut default aplikasi
        PengaturanPenilaian::aktif()->update([
            'skala' => PengaturanPenilaian::PRESET['nol_lima']['skala'],
            'rumus' => 'normalisasi',
        ]);

        $pendaftar = Pendaftar::factory()->create();
        $indikator = IndikatorPenilaian::query()->where('aktif', true)->with('kategoriPenilaian')->get();
        $skor = $indikator->mapWithKeys(fn ($i) => [$i->id => 5])->all();

        $this->put(route('admin.penilaian.update', $pendaftar), [
            'skor' => $skor,
            'rekomendasi' => 'lolos',
            'catatan_reviewer' => 'Uji otomatis.',
        ])->assertRedirect(route('admin.penilaian.index'));

        $penilaian = Penilaian::where('pendaftar_id', $pendaftar->id)->firstOrFail();

        $this->assertEquals(100.0, (float) $penilaian->nilai_final);
        $this->assertEquals($indikator->count() * 5, (float) $penilaian->nilai_mentah);
        $this->assertNotNull($penilaian->dinilai_at);
        $this->assertSame($this->admin->id, $penilaian->reviewer_id);
        $this->assertSame($indikator->count(), $penilaian->detail()->count());
    }

    public function test_penilaian_parsial_jadi_draft(): void
    {
        $this->actingAs($this->admin);

        $pendaftar = Pendaftar::factory()->create();
        $indikator = IndikatorPenilaian::query()->where('aktif', true)->get();

        $this->put(route('admin.penilaian.update', $pendaftar), [
            'skor' => [$indikator->first()->id => 3],
        ])->assertRedirect();

        $penilaian = Penilaian::where('pendaftar_id', $pendaftar->id)->firstOrFail();

        $this->assertNull($penilaian->dinilai_at);
        $this->assertSame('sebagian', $penilaian->status_penilaian);
    }

    public function test_skor_di_luar_rentang_ditolak(): void
    {
        $this->actingAs($this->admin);

        $pendaftar = Pendaftar::factory()->create();
        $indikator = IndikatorPenilaian::query()->where('aktif', true)->firstOrFail();

        // skala aktif 1/3/5/7/9 -> 4 dan 11 sama-sama di luar skala
        $this->put(route('admin.penilaian.update', $pendaftar), [
            'skor' => [$indikator->id => 4],
        ])->assertSessionHasErrors('skor.'.$indikator->id);

        $this->put(route('admin.penilaian.update', $pendaftar), [
            'skor' => [$indikator->id => 11],
        ])->assertSessionHasErrors('skor.'.$indikator->id);
    }

    /**
     * Format Google Form PRIMESTeP: judul kolomnya berbeda jauh dari template
     * bawaan dan TIDAK punya kolom email sama sekali.
     */
    public function test_import_format_primestep_tanpa_email(): void
    {
        $this->actingAs($this->admin);

        $header = 'Timestamp,Nama CEO,Asal Sekolah,Jurusan/Program Studi,No Kontak/HP (Whatsapp),'
            .'Pilihan Program yang akan diusulkan,Nama Usaha,Nama Produk/Jasa,Deskripsi Produk/Jasa,'
            .'Bidang Usaha,Permasalahan Utama yang dihadapi,Rencana pengembangan usaha,Upload Proposal';

        // 46074 = angka serial Excel untuk 21 Feb 2026
        $baris = '46074,Sari Melati,Universitas Nusantara,Agribisnis,0812-0000-1234,'
            .'Inkubasi,BIOTANI NUSANTARA,Pupuk Nano Organik,Pembenah tanah berbahan arang aktif,'
            .'Biosains,Kapasitas produksi terbatas,Kolaborasi lintas sektor,https://drive.google.com/open?id=ABC';

        $file = UploadedFile::fake()->createWithContent('primestep.csv', $header.'
'.$baris.'
');

        $this->post(route('admin.pendaftar.import.store'), ['berkas' => $file])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $p = Pendaftar::where('nama_tim', 'BIOTANI NUSANTARA')->firstOrFail();

        // kolom yang dulu tidak dikenali
        $this->assertSame('Sari Melati', $p->nama_ketua);
        $this->assertSame('Pupuk Nano Organik', $p->judul_inovasi);
        $this->assertSame('Inkubasi', $p->kategoriPeserta->nama);
        $this->assertSame('Universitas Nusantara', $p->asal_institusi);
        $this->assertSame('Kolaborasi lintas sektor', $p->solusi);

        // yang sudah jalan sebelumnya
        $this->assertSame('Biosains', $p->bidangKompetisi->nama);
        $this->assertSame('0812-0000-1234', $p->no_wa);
        $this->assertNull($p->email);
        $this->assertSame('2026-02-21', $p->tanggal_daftar->toDateString());   // serial Excel dikonversi
    }

    /** Tanpa email, import ulang tidak boleh menggandakan baris. */
    public function test_import_ulang_tanpa_email_tidak_menggandakan(): void
    {
        $this->actingAs($this->admin);

        $csv = 'Nama CEO,No Kontak/HP (Whatsapp),Nama Usaha,Bidang Usaha
'
            .'Budi,0812345,Tani Maju,Pangan
'
            .'Budi,0812345,Tani Maju,Pangan
';      // baris kembar di file yang sama

        foreach ([1, 2] as $keBerapa) {
            $file = UploadedFile::fake()->createWithContent("ulang{$keBerapa}.csv", $csv);
            $this->post(route('admin.pendaftar.import.store'), ['berkas' => $file])->assertRedirect();
        }

        $this->assertSame(1, Pendaftar::where('nama_tim', 'Tani Maju')->count());
    }

    /** Nama usaha sama tapi orang berbeda (no WA beda) harus tetap jadi dua peserta. */
    public function test_nama_usaha_sama_dengan_wa_berbeda_tidak_digabung(): void
    {
        $this->actingAs($this->admin);

        $csv = 'Nama CEO,No Kontak/HP (Whatsapp),Nama Usaha,Bidang Usaha
'
            .'Ani,0811111,Fida Art,Industri Kreatif
'
            .'Rina,0822222,Fida Art,Industri Kreatif
';

        $file = UploadedFile::fake()->createWithContent('kembar.csv', $csv);
        $this->post(route('admin.pendaftar.import.store'), ['berkas' => $file])->assertRedirect();

        $this->assertSame(2, Pendaftar::where('nama_tim', 'Fida Art')->count());
    }

    /**
     * Kolom anggota tim di form PRIMESTeP menggabungkan jumlah + daftar nama
     * dalam satu sel. Jumlah dan jenis kelamin tidak boleh ikut jadi data.
     */
    public function test_parsing_anggota_tim_format_primestep(): void
    {
        $teks = '1. 3 orang 2. 1. Komisaris Utama: Andi Prasetyo (Pria), '
            .'2. CEO: Sari Melati (Wanita), 3. CFO: Rizal Hakim (Pria)';

        $hasil = ParserData::daftarAnggota($teks);

        $this->assertSame(
            ['Andi Prasetyo', 'Sari Melati', 'Rizal Hakim'],
            array_column($hasil, 'nama'),
        );

        // "(Pria)" itu jenis kelamin, bukan NIM
        $this->assertSame([null, null, null], array_column($hasil, 'nim'));
    }

    /**
     * Nama jabatan yang ditulis setelah koma ("Budi, Produksi") bukan orang.
     * Di data Batch 1, "CEO" sempat tercatat sebagai anggota tim 58 kali.
     */
    public function test_jabatan_tidak_ikut_jadi_anggota_tim(): void
    {
        $teks = 'Bagas Nugroho, Produksi (Pria), Dimas Saputra, Produksi (Pria), '
            .'Laras Wijaya, Keuangan (Wanita)';

        $this->assertSame(
            ['Bagas Nugroho', 'Dimas Saputra', 'Laras Wijaya'],
            array_column(ParserData::daftarAnggota($teks), 'nama'),
        );

        // jabatan lain yang sering muncul
        foreach (['CEO', 'CMO', 'Keuangan', 'Marketing', 'Business Development'] as $jabatan) {
            $hasil = ParserData::daftarAnggota("Budi Santoso, {$jabatan}");
            $this->assertSame(['Budi Santoso'], array_column($hasil, 'nama'), $jabatan);
        }
    }

    /** Gelar yang terpotong koma dikembalikan ke namanya, bukan jadi anggota baru. */
    public function test_gelar_tidak_terpisah_jadi_anggota(): void
    {
        $hasil = ParserData::daftarAnggota('Budi Santoso, S.Pt, Ani Lestari, M.M., Doni');

        $this->assertSame(
            ['Budi Santoso, S.Pt', 'Ani Lestari, M.M.', 'Doni'],
            array_column($hasil, 'nama'),
        );
    }

    /** Format NIM yang lama tetap harus terbaca seperti semula. */
    public function test_parsing_anggota_tim_format_lama_tidak_rusak(): void
    {
        $hasil = ParserData::daftarAnggota('Ani - 111; Doni - 222');

        $this->assertSame(['Ani', 'Doni'], array_column($hasil, 'nama'));
        $this->assertSame(['111', '222'], array_column($hasil, 'nim'));
    }

    /**
     * File rekap sering punya beberapa sheet dengan kolom berbeda (mis. sheet
     * "Data 37 Lolos" tanpa kolom Timestamp). Import berkas yang lebih ringkas
     * TIDAK boleh mengosongkan data dari import sebelumnya.
     */
    public function test_import_berkas_ringkas_tidak_menghapus_data_sebelumnya(): void
    {
        $this->actingAs($this->admin);

        // 1) berkas lengkap
        $lengkap = 'Timestamp,Nama CEO,No Kontak/HP (Whatsapp),Nama Usaha,Nama Produk/Jasa,Jurusan/Program Studi,Bidang Usaha
'
            .'46074,Budi,0812345,Tani Maju,Pupuk Organik,Agribisnis,Pangan
';

        $this->post(route('admin.pendaftar.import.store'), [
            'berkas' => UploadedFile::fake()->createWithContent('lengkap.csv', $lengkap),
        ])->assertRedirect();

        $p = Pendaftar::where('nama_tim', 'Tani Maju')->firstOrFail();
        $this->assertSame('2026-02-21', $p->tanggal_daftar->toDateString());
        $this->assertSame('Agribisnis', $p->fakultas_prodi);

        // 2) berkas ringkas: tanpa Timestamp dan tanpa Jurusan
        $ringkas = 'Nama CEO,No Kontak/HP (Whatsapp),Nama Usaha,Nama Produk/Jasa,Bidang Usaha
'
            .'Budi Santoso,0812345,Tani Maju,Pupuk Organik Plus,Pangan
';

        $this->post(route('admin.pendaftar.import.store'), [
            'berkas' => UploadedFile::fake()->createWithContent('ringkas.csv', $ringkas),
        ])->assertRedirect();

        $p->refresh();

        // yang ADA di berkas kedua ikut diperbarui
        $this->assertSame('Budi Santoso', $p->nama_ketua);
        $this->assertSame('Pupuk Organik Plus', $p->judul_inovasi);

        // yang TIDAK ADA di berkas kedua harus bertahan, bukan jadi kosong
        $this->assertSame('2026-02-21', $p->tanggal_daftar->toDateString());
        $this->assertSame('Agribisnis', $p->fakultas_prodi);

        $this->assertSame(1, Pendaftar::where('nama_tim', 'Tani Maju')->count());
    }

    /**
     * Data arsip (mis. Batch 1 yang diimpor belakangan) tidak boleh terbaca
     * sebagai "pendaftar hari ini", dan grafik trennya harus mencakup SELURUH
     * masa pendaftaran — bukan cuma 14 hari terakhir.
     */
    public function test_statistik_tidak_salah_baca_data_arsip(): void
    {
        $this->actingAs($this->admin);

        // rentang 7 minggu, mirip Batch 1 (21 Feb - 9 Apr)
        Pendaftar::factory()->count(4)->create(['tanggal_daftar' => '2026-02-21']);
        Pendaftar::factory()->count(20)->create(['tanggal_daftar' => '2026-03-15']);
        Pendaftar::factory()->count(38)->create(['tanggal_daftar' => '2026-03-31']);
        Pendaftar::factory()->count(1)->create(['tanggal_daftar' => '2026-04-09']);

        $respons = $this->get(route('admin.statistik'))->assertOk();
        $tren = $respons->viewData('tren');

        // dibuat hari ini, tapi tanggal daftarnya bulan Februari-April
        $this->assertSame(0, $respons->viewData('ringkasan')['hari_ini']);

        // seluruh pendaftar harus terhitung di grafik, bukan sebagian
        $this->assertSame(63, array_sum($tren));

        // lonjakan paling awal tetap terlihat, bukan terpotong jendela 14 hari
        $this->assertSame('21 Feb', array_key_first($tren));
        $this->assertSame('09 Apr', array_key_last($tren));
        $this->assertSame(20, $tren['15 Mar']);
        $this->assertSame(38, $tren['31 Mar']);
    }

    /** Rentang panjang dikelompokkan supaya titik grafik tidak meledak. */
    public function test_tren_rentang_panjang_dikelompokkan(): void
    {
        $this->actingAs($this->admin);

        Pendaftar::factory()->create(['tanggal_daftar' => '2025-01-05']);
        Pendaftar::factory()->count(2)->create(['tanggal_daftar' => '2026-03-15']);

        $tren = $this->get(route('admin.statistik'))->assertOk()->viewData('tren');

        // lebih dari setahun -> dikelompokkan, jumlah titik tetap wajar
        $this->assertLessThan(40, count($tren));
        $this->assertSame(3, array_sum($tren));
    }

    /** Belum ada pendaftar: grafik tetap punya kerangka, bukan error. */
    public function test_tren_tanpa_pendaftar_tidak_error(): void
    {
        $tren = $this->actingAs($this->admin)
            ->get(route('admin.statistik'))->assertOk()->viewData('tren');

        $this->assertCount(14, $tren);
        $this->assertSame(0, array_sum($tren));
    }

    /** Kota & provinsi ditebak dari alamat teks bebas (form tidak punya kolomnya). */
    public function test_kota_provinsi_ditebak_dari_alamat(): void
    {
        // Semua alamat di bawah ini KARANGAN. Jangan pernah menyalin alamat
        // peserta sungguhan ke dalam test — repo ini publik.
        $kasus = [
            // provinsi ditulis langsung
            ['Jl. Melati No. 1, Garut, Jawa Barat', 'Garut', 'Jawa Barat'],
            // provinsi TIDAK ditulis, disimpulkan dari nama kota
            ['Jl. Anggrek No. 2, Kelurahan Sukamaju, Kota Depok', 'Depok', 'Jawa Barat'],
            // singkatan
            ['Jl. Kenanga No. 3, tangsel', 'Tangerang Selatan', 'Banten'],
            // nama panjang tidak boleh kalah oleh nama pendek
            ['Perumahan Contoh Blok A, Bandung Barat', 'Bandung Barat', 'Jawa Barat'],
            // alamat tanpa petunjuk wilayah -> dibiarkan kosong, bukan ditebak asal
            ['Jl. Dahlia No. 4', null, null],
        ];

        foreach ($kasus as [$alamat, $kota, $provinsi]) {
            $hasil = WilayahIndonesia::dariAlamat($alamat);

            $this->assertSame($kota, $hasil['kota'], $alamat);
            $this->assertSame($provinsi, $hasil['provinsi'], $alamat);
        }
    }

    /** Kalau form punya kolom Kota/Provinsi sendiri, kolom itu yang menang. */
    public function test_kolom_kota_eksplisit_mengalahkan_tebakan_alamat(): void
    {
        $this->actingAs($this->admin);

        $csv = 'Nama CEO,No Kontak/HP (Whatsapp),Nama Usaha,Alamat Rumah,Kota,Provinsi
'
            .'Budi,0812345,Tani Maju,"Jl Merdeka, Kota Depok",Kota Bogor,Jawa Barat
';

        $this->post(route('admin.pendaftar.import.store'), [
            'berkas' => UploadedFile::fake()->createWithContent('wilayah.csv', $csv),
        ])->assertRedirect();

        $p = Pendaftar::where('nama_tim', 'Tani Maju')->firstOrFail();

        $this->assertSame('Kota Bogor', $p->kota);      // bukan "Depok" dari alamat
        $this->assertSame('Jawa Barat', $p->provinsi);
    }

    public function test_import_csv_membuat_pendaftar(): void
    {
        $this->actingAs($this->admin);

        $csv = "Timestamp,Kategori Peserta,Bidang Kompetisi,Nama Ketua,Email,Nama Tim/Usaha,Judul Inovasi,Kota,Provinsi,Anggota Tim\n"
            ."2026-09-01,Mahasiswa,Food & Beverages (F&B),Budi Santoso,budi@contoh.test,Tim Alpha,Judul Uji Import,Kota Bandung,Jawa Barat,\"Ani - 111; Doni - 222\"\n";

        $file = UploadedFile::fake()->createWithContent('gform.csv', $csv);

        $this->post(route('admin.pendaftar.import.store'), ['berkas' => $file])
            ->assertRedirect(route('admin.pendaftar.import'))
            ->assertSessionHas('sukses');

        $pendaftar = Pendaftar::where('email', 'budi@contoh.test')->firstOrFail();

        $this->assertSame('Tim Alpha', $pendaftar->nama_tim);
        $this->assertSame('Judul Uji Import', $pendaftar->judul_inovasi);
        $this->assertSame('Mahasiswa', $pendaftar->kategoriPeserta->nama);
        $this->assertSame(2, $pendaftar->anggotaTim()->count());
        $this->assertDatabaseHas('import_log', ['jumlah_berhasil' => 1, 'jumlah_gagal' => 0]);
    }

    public function test_kelola_indikator_rubrik(): void
    {
        $this->actingAs($this->admin);
        $kategori = KategoriPenilaian::firstOrFail();

        $this->post(route('admin.rubrik.indikator.store'), [
            'kategori_penilaian_id' => $kategori->id,
            'nama' => 'Indikator Uji',
            'bobot' => 5,
        ])->assertRedirect();

        $indikator = IndikatorPenilaian::where('nama', 'Indikator Uji')->firstOrFail();

        $this->delete(route('admin.rubrik.indikator.destroy', $indikator))->assertRedirect();
        $this->assertNull(IndikatorPenilaian::find($indikator->id));
    }

    /**
     * Sapu semua rute GET panel: jangan sampai ada halaman baru yang lupa diuji.
     * Admin harus bisa membuka semuanya.
     */
    public function test_sapuan_semua_rute_get_panel_untuk_admin(): void
    {
        $this->actingAs($this->admin);
        $pendaftar = Pendaftar::factory()->create();
        $diuji = 0;

        foreach (Route::getRoutes() as $rute) {
            $nama = $rute->getName();

            if (! $nama || ! str_starts_with($nama, 'admin.') || ! in_array('GET', $rute->methods(), true)) {
                continue;
            }

            $param = $rute->parameterNames();

            // hanya rute tanpa parameter atau yang parameternya {pendaftar}
            if ($param !== [] && $param !== ['pendaftar']) {
                continue;
            }

            $this->get(route($nama, $param === [] ? [] : $pendaftar))
                ->assertSuccessful();      // 2xx, termasuk unduhan CSV
            $diuji++;
        }

        $this->assertGreaterThanOrEqual(15, $diuji, 'Sapuan rute tidak menemukan halaman panel.');
    }

    /** Pintu darurat /setup-admin harus mati (404) selama SETUP_TOKEN kosong. */
    public function test_setup_admin_mati_kalau_token_kosong(): void
    {
        config(['app.setup_token' => null]);

        $this->get('/setup-admin/apa-saja')->assertNotFound();
        $this->post('/setup-admin/apa-saja', [])->assertNotFound();
    }

    public function test_setup_admin_menolak_token_salah(): void
    {
        config(['app.setup_token' => 'token-rahasia-yang-benar']);

        $this->get('/setup-admin/token-salah')->assertNotFound();
        $this->get('/setup-admin/token-rahasia-yang-benar')->assertOk();
    }
}
