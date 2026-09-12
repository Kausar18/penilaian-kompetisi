<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Pendaftar;
use App\Models\PengaturanSitus;
use App\Models\Penilaian;
use App\Models\User;
use App\Models\VerifikasiAdministrasi;
use App\Services\StatistikPengunjung;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublikTest extends TestCase
{
    use RefreshDatabase;

    private PengaturanSitus $situs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->situs = PengaturanSitus::ambil();
    }

    /** Peserta yang lolos verifikasi administrasi. */
    private function timLolos(array $atribut = []): Pendaftar
    {
        $p = Pendaftar::factory()->create($atribut + ['status' => 'verified']);

        VerifikasiAdministrasi::create([
            'pendaftar_id' => $p->id,
            'hasil' => 'lolos',
            'diverifikasi_at' => now(),
        ]);

        return $p;
    }

    /** Peserta yang ditolak di verifikasi administrasi. */
    private function timDitolak(array $atribut = []): Pendaftar
    {
        $p = Pendaftar::factory()->create($atribut + ['status' => 'rejected']);

        VerifikasiAdministrasi::create([
            'pendaftar_id' => $p->id,
            'hasil' => 'tidak_lolos',
            'diverifikasi_at' => now(),
        ]);

        return $p;
    }

    // ==================================================================
    // AKSES DASAR
    // ==================================================================

    public function test_halaman_publik_terbuka_tanpa_login(): void
    {
        $this->get(route('publik.beranda'))->assertOk()->assertSee($this->situs->judul);
        $this->get(route('publik.pengumuman'))->assertOk();
    }

    public function test_situs_dimatikan_menampilkan_halaman_segera_hadir(): void
    {
        $this->situs->update(['situs_aktif' => false]);

        $this->get(route('publik.beranda'))->assertOk()->assertSee('Halaman publik belum dibuka');
        $this->get(route('publik.pengumuman'))->assertOk()->assertSee('Halaman publik belum dibuka');
    }

    // ==================================================================
    // GERBANG PENGUMUMAN — bagian paling penting
    // ==================================================================

    public function test_hasil_tidak_bocor_sebelum_panitia_mengumumkan(): void
    {
        $this->timLolos(['nama_tim' => 'Tim Rahasia Lolos']);

        // default: kedua saklar mati
        $this->assertFalse($this->situs->umumkan_administrasi);

        $this->get(route('publik.pengumuman'))
            ->assertOk()
            ->assertDontSee('Tim Rahasia Lolos')
            ->assertSee('Pengumuman Belum Tersedia');
    }

    public function test_jumlah_lolos_tidak_muncul_di_beranda_sebelum_diumumkan(): void
    {
        $this->timLolos();

        $this->get(route('publik.beranda'))
            ->assertOk()
            ->assertDontSee('Lolos administrasi')
            ->assertDontSee('Finalis');
    }

    public function test_setelah_diumumkan_hanya_yang_lolos_yang_tampil(): void
    {
        $this->situs->update(['umumkan_administrasi' => true]);

        $this->timLolos(['nama_tim' => 'Tim Berhasil']);
        $this->timDitolak(['nama_tim' => 'Tim Gagal']);
        Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Dinilai', 'status' => 'submitted']);

        $this->get(route('publik.pengumuman'))
            ->assertOk()
            ->assertSee('Tim Berhasil')
            ->assertDontSee('Tim Gagal')
            ->assertDontSee('Tim Belum Dinilai');
    }

    public function test_membuka_tab_finalis_sebelum_diumumkan_tidak_membocorkan_data(): void
    {
        $this->situs->update(['umumkan_administrasi' => true, 'umumkan_finalis' => false]);

        $tim = $this->timLolos(['nama_tim' => 'Tim Sudah Pitching']);
        Penilaian::create([
            'pendaftar_id' => $tim->id,
            'rekomendasi' => 'lolos',
            'nilai_final' => 620,
            'dinilai_at' => now(),
        ]);
        $this->timLolos(['nama_tim' => 'Belum Dinilai Pitching']);

        // tab finalis boleh dibuka, tapi isinya keterangan — bukan daftar peserta,
        // dan bukan pula daftar administrasi sebagai gantinya
        $this->get(route('publik.pengumuman', ['tahap' => 'finalis']))
            ->assertOk()
            ->assertSee('Pengumuman Belum Tersedia')
            ->assertDontSee('Tim Sudah Pitching')
            ->assertDontSee('Belum Dinilai Pitching');
    }

    /**
     * Kedua tab selalu bisa dibuka (bukan tombol mati). Tahap yang belum
     * diumumkan menampilkan halaman keterangan, dan jumlahnya disembunyikan.
     */
    public function test_tab_selalu_bisa_dibuka_dan_menjelaskan_kalau_belum_diumumkan(): void
    {
        $tautanFinalis = 'href="'.route('publik.pengumuman', ['tahap' => 'finalis']).'"';
        $this->timLolos(['nama_tim' => 'Tim Belum Waktunya']);

        // walau belum ada pengumuman, kedua tab tetap berupa tautan
        $this->get(route('publik.pengumuman'))
            ->assertOk()
            ->assertSee($tautanFinalis, false)
            ->assertSee('Pengumuman Belum Tersedia')
            ->assertSee('Kembali ke Beranda')
            ->assertDontSee('Tim Belum Waktunya');

        // keterangan menyesuaikan tahap yang dibuka
        $this->get(route('publik.pengumuman', ['tahap' => 'finalis']))
            ->assertOk()
            ->assertSee('Pitching Battle')
            ->assertDontSee('Tim Belum Waktunya');

        // setelah diumumkan, daftarnya baru muncul
        $this->situs->update(['umumkan_administrasi' => true]);
        $this->get(route('publik.pengumuman'))
            ->assertOk()
            ->assertSee('Tim Belum Waktunya')
            ->assertDontSee('Pengumuman Belum Tersedia');
    }

    public function test_finalis_tampil_setelah_diumumkan(): void
    {
        $this->situs->update(['umumkan_administrasi' => true, 'umumkan_finalis' => true]);

        $finalis = $this->timLolos(['nama_tim' => 'Tim Finalis']);
        Penilaian::create([
            'pendaftar_id' => $finalis->id,
            'rekomendasi' => 'lolos',
            'nilai_final' => 620,
            'dinilai_at' => now(),
        ]);

        $gagal = $this->timLolos(['nama_tim' => 'Tim Tidak Lanjut']);
        Penilaian::create([
            'pendaftar_id' => $gagal->id,
            'rekomendasi' => 'tidak_lolos',
            'nilai_final' => 210,
            'dinilai_at' => now(),
        ]);

        $this->get(route('publik.pengumuman', ['tahap' => 'finalis']))
            ->assertOk()
            ->assertSee('Tim Finalis')
            ->assertDontSee('Tim Tidak Lanjut');
    }

    public function test_penilaian_yang_masih_draft_belum_dianggap_finalis(): void
    {
        $this->situs->update(['umumkan_finalis' => true]);

        $tim = $this->timLolos(['nama_tim' => 'Tim Draft']);
        Penilaian::create([
            'pendaftar_id' => $tim->id,
            'rekomendasi' => 'lolos',
            'nilai_final' => 600,
            'dinilai_at' => null,        // belum diselesaikan reviewer
        ]);

        $this->get(route('publik.pengumuman', ['tahap' => 'finalis']))
            ->assertOk()
            ->assertDontSee('Tim Draft');
    }

    // ==================================================================
    // KEBOCORAN DATA PRIBADI
    // ==================================================================

    public function test_data_pribadi_dan_nilai_tidak_pernah_muncul_di_halaman_publik(): void
    {
        $this->situs->update(['umumkan_administrasi' => true, 'umumkan_finalis' => true]);

        $tim = $this->timLolos([
            'nama_tim' => 'Tim Uji Privasi',
            'nama_ketua' => 'Budi Rahasia',
            'email' => 'rahasia@contoh.test',
            'no_wa' => '081234567890',
        ]);

        Penilaian::create([
            'pendaftar_id' => $tim->id,
            'rekomendasi' => 'lolos',
            'nilai_final' => 637.5,
            'catatan_reviewer' => 'Catatan internal juri yang tidak boleh bocor.',
            'dinilai_at' => now(),
        ]);

        foreach ([route('publik.beranda'), route('publik.pengumuman'), route('publik.pengumuman', ['tahap' => 'finalis'])] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('rahasia@contoh.test')
                ->assertDontSee('081234567890')
                ->assertDontSee('Budi Rahasia')
                ->assertDontSee('Catatan internal juri')
                ->assertDontSee('637');
        }
    }

    public function test_pencarian_publik_tidak_bisa_dipakai_menebak_email(): void
    {
        $this->situs->update(['umumkan_administrasi' => true]);
        $this->timLolos(['nama_tim' => 'Tim Kabur', 'email' => 'target@contoh.test']);

        // mencari lewat email tidak boleh membuka datanya
        $this->get(route('publik.pengumuman', ['q' => 'target@contoh.test']))
            ->assertOk()
            ->assertDontSee('Tim Kabur');
    }

    // ==================================================================
    // PENGATURAN SITUS (panel)
    // ==================================================================

    public function test_hanya_admin_yang_bisa_mengatur_halaman_publik(): void
    {
        $reviewer = User::factory()->create(['peran' => 'reviewer']);

        $this->actingAs($reviewer)->get(route('admin.situs.index'))->assertForbidden();
        $this->actingAs($reviewer)
            ->put(route('admin.situs.update'), ['judul' => 'Bajakan'])
            ->assertForbidden();

        $this->assertNotSame('Bajakan', PengaturanSitus::ambil()->fresh()->judul);
    }

    public function test_admin_bisa_menyalakan_pengumuman_dan_mengisi_jadwal(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();
        $this->timLolos(['nama_tim' => 'Tim Diumumkan']);

        $this->actingAs($admin)
            ->put(route('admin.situs.update'), [
                'judul' => 'Kompetisi Batch 3',
                'situs_aktif' => '1',
                'umumkan_administrasi' => '1',
                'timeline' => [
                    ['tahap' => 'Pendaftaran', 'tanggal' => '1-20 Okt', 'selesai' => '1'],
                    ['tahap' => '', 'tanggal' => 'diabaikan'],     // baris kosong harus dibuang
                ],
            ])
            ->assertRedirect();

        $situs = PengaturanSitus::ambil()->fresh();

        $this->assertTrue($situs->umumkan_administrasi);
        $this->assertCount(1, $situs->timelineRapi());

        $this->get(route('publik.pengumuman'))->assertOk()->assertSee('Tim Diumumkan');
    }

    /** Saklar induk memang mematikan situs — dijaga tes supaya perilakunya disadari. */
    public function test_menyimpan_tanpa_mencentang_situs_aktif_mematikan_halaman_publik(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.situs.update'), ['judul' => 'Kompetisi'])   // situs_aktif tidak dikirim
            ->assertRedirect();

        $this->assertFalse(PengaturanSitus::ambil()->fresh()->situs_aktif);
        $this->get(route('publik.beranda'))->assertOk()->assertSee('Halaman publik belum dibuka');
    }

    /** User yang sudah login membuka /login harus diantar ke panelnya, bukan ke halaman publik. */
    public function test_user_login_yang_membuka_login_diantar_ke_panel(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();
        $reviewer = User::factory()->create(['peran' => 'reviewer']);

        $this->actingAs($admin)->get(route('login'))->assertRedirect(route('admin.statistik'));
        $this->actingAs($reviewer)->get(route('login'))->assertRedirect(route('admin.penilaian.index'));
    }

    /** Alamat situs boleh diketik polos tanpa https:// */
    public function test_situs_lembaga_tanpa_skema_diterima(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.situs.update'), [
                'judul' => 'Kompetisi',
                'situs_aktif' => '1',
                'situs_lembaga' => 'ipb.ipb.ac.id',
                'instagram' => '@stpipb',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $situs = PengaturanSitus::ambil()->fresh();

        $this->assertSame('https://ipb.ipb.ac.id', $situs->situs_lembaga);
        $this->assertSame('@stpipb', $situs->instagram);
    }

    /** Instagram boleh diketik bebas; footer selalu jadi tautan yang benar. */
    public function test_instagram_jadi_tautan_apa_pun_cara_menulisnya(): void
    {
        $bentuk = [
            '@stpipb',
            'stpipb',
            'instagram.com/stpipb',
            'www.instagram.com/stpipb/',
            'https://www.instagram.com/stpipb/',
        ];

        foreach ($bentuk as $tulisan) {
            $this->situs->update(['instagram' => $tulisan]);
            $situs = PengaturanSitus::ambil()->fresh();

            $this->assertStringContainsString('instagram.com/stpipb', $situs->instagramUrl(), $tulisan);
            $this->assertSame('@stpipb', $situs->instagramLabel(), $tulisan);

            $this->get(route('publik.beranda'))
                ->assertOk()
                ->assertSee('href="'.$situs->instagramUrl().'"', false);
        }
    }

    public function test_instagram_kosong_tidak_membuat_tautan(): void
    {
        $this->situs->update(['instagram' => null]);

        $this->assertNull(PengaturanSitus::ambil()->fresh()->instagramUrl());
        $this->get(route('publik.beranda'))->assertOk()->assertDontSee('bi-instagram', false);
    }

    /** Tanggal jadwal tersimpan tapi tidak tayang selama saklarnya mati. */
    public function test_tanggal_jadwal_hanya_tayang_kalau_dinyalakan(): void
    {
        $jadwal = [
            ['tahap' => 'Pendaftaran dibuka', 'tanggal' => '1 - 20 Oktober 2026', 'selesai' => true],
            ['tahap' => 'Pitching Battle', 'tanggal' => '5 November 2026', 'selesai' => false],
        ];

        $this->situs->update(['timeline' => $jadwal, 'tampilkan_tanggal_jadwal' => false]);

        // nama tahap tetap tampil, tanggalnya tidak
        $this->get(route('publik.beranda'))
            ->assertOk()
            ->assertSee('Pendaftaran dibuka')
            ->assertSee('Pitching Battle')
            ->assertDontSee('1 - 20 Oktober 2026')
            ->assertDontSee('5 November 2026');

        // tanggalnya tetap tersimpan, bukan terhapus
        $this->assertSame('1 - 20 Oktober 2026', PengaturanSitus::ambil()->fresh()->timelineRapi()[0]['tanggal']);

        $this->situs->update(['tampilkan_tanggal_jadwal' => true]);

        $this->get(route('publik.beranda'))
            ->assertOk()
            ->assertSee('1 - 20 Oktober 2026')
            ->assertSee('5 November 2026');
    }

    // ==================================================================
    // PENCATATAN KUNJUNGAN
    // ==================================================================

    public function test_kunjungan_halaman_publik_tercatat(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone) Mobile Safari'])
            ->get(route('publik.beranda'))
            ->assertOk();

        $this->assertDatabaseCount('kunjungan', 1);

        $k = Kunjungan::firstOrFail();
        $this->assertSame('/', $k->path);
        $this->assertSame('mobile', $k->perangkat);
        $this->assertSame(64, strlen($k->pengunjung));
    }

    public function test_ip_mentah_tidak_pernah_disimpan(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])
            ->get(route('publik.beranda'));

        $this->assertDatabaseMissing('kunjungan', ['pengunjung' => '203.0.113.44']);
        $this->assertStringNotContainsString('203.0.113.44', Kunjungan::firstOrFail()->pengunjung);
    }

    public function test_panel_admin_dan_robot_tidak_ikut_tercatat(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();

        // halaman panel tidak memakai middleware pencatat
        $this->actingAs($admin)->get(route('admin.statistik'))->assertOk();

        // panitia yang sedang login juga dilewati di halaman publik
        $this->actingAs($admin)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get(route('publik.beranda'))->assertOk();

        // robot diabaikan
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->get(route('publik.beranda'))->assertOk();

        $this->assertDatabaseCount('kunjungan', 0);
    }

    public function test_statistik_pengunjung_memakai_data_nyata(): void
    {
        $admin = User::where('peran', 'admin')->firstOrFail();

        foreach (['Mozilla/5.0 (iPhone) Mobile', 'Mozilla/5.0 (Windows NT 10.0)'] as $ua) {
            $this->withHeaders(['User-Agent' => $ua])->get(route('publik.beranda'));
            $this->withHeaders(['User-Agent' => $ua])->get(route('publik.pengumuman'));
        }

        $data = app(StatistikPengunjung::class)->lengkap();

        $this->assertSame(4, $data['total_views']);
        $this->assertSame(2, $data['total_unique']);
        $this->assertSame(100, array_sum($data['perangkat']));

        // halaman Pengunjung di panel ikut menampilkan angka nyata itu
        $this->actingAs($admin)->get(route('admin.pengunjung'))->assertOk()->assertSee('4');
    }
}
