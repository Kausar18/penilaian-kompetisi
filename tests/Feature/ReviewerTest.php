<?php

namespace Tests\Feature;

use App\Models\ItemVerifikasi;
use App\Models\Pendaftar;
use App\Models\PengaturanPenilaian;
use App\Models\User;
use App\Models\VerifikasiAdministrasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReviewerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reviewer;

    private User $reviewerLain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('peran', 'admin')->firstOrFail();
        $this->reviewer = User::factory()->create(['peran' => 'reviewer']);
        $this->reviewerLain = User::factory()->create(['peran' => 'reviewer']);
    }

    public function test_reviewer_login_diarahkan_ke_penilaian(): void
    {
        $this->reviewer->update(['username' => 'juri', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login.store'), ['username' => 'juri', 'password' => 'rahasia123'])
            ->assertRedirect(route('admin.penilaian.index'));
    }

    public function test_reviewer_bisa_lihat_semua_menu_kecuali_rubrik(): void
    {
        $this->actingAs($this->reviewer);

        $this->get(route('admin.rubrik.index'))->assertForbidden();

        $this->get(route('admin.statistik'))->assertOk();
        $this->get(route('admin.pendaftar.index'))->assertOk();
        $this->get(route('admin.pengunjung'))->assertOk();
        $this->get(route('admin.penilaian.index'))->assertOk();
        $this->get(route('admin.rekap.index'))->assertOk();
        $this->get(route('admin.penugasan.index'))->assertOk();
    }

    /**
     * Reviewer tetap boleh menentukan lolos/tidak lolos — hanya jalannya yang
     * berubah: lewat form Verifikasi Administrasi, bukan dropdown status.
     */
    public function test_reviewer_boleh_ubah_status_lewat_verifikasi_administrasi(): void
    {
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted']);

        $status = ItemVerifikasi::aktif()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => 'sesuai'])->all();

        $this->actingAs($this->reviewer)
            ->put(route('admin.verifikasi.update', $pendaftar), [
                'status' => $status,
                'hasil' => 'lolos',
            ])
            ->assertRedirect();

        $this->assertSame('verified', $pendaftar->fresh()->status);
        $this->assertSame('lolos', $pendaftar->fresh()->verifikasi->hasil);
    }

    public function test_reviewer_tidak_bisa_aksi_berbahaya(): void
    {
        $this->actingAs($this->reviewer);
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted', 'reviewer_id' => null]);

        // hapus pendaftar
        $this->delete(route('admin.pendaftar.destroy', $pendaftar))->assertForbidden();
        $this->assertDatabaseHas('pendaftar', ['id' => $pendaftar->id]);

        // import
        $this->get(route('admin.pendaftar.import'))->assertForbidden();

        // ubah penugasan
        $this->put(route('admin.penugasan.update', $pendaftar), ['reviewer_id' => $this->reviewerLain->id])
            ->assertForbidden();
        $this->assertNull($pendaftar->fresh()->reviewer_id);
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

    public function test_penilaian_reviewer_hanya_menampilkan_yang_ditugaskan(): void
    {
        $this->loloskan(Pendaftar::factory()->create(['nama_tim' => 'Tim Punya Dia', 'reviewer_id' => $this->reviewer->id]));
        $this->loloskan(Pendaftar::factory()->create(['nama_tim' => 'Tim Orang Lain', 'reviewer_id' => $this->reviewerLain->id]));
        $this->loloskan(Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Ditugaskan', 'reviewer_id' => null]));

        $this->actingAs($this->reviewer)
            ->get(route('admin.penilaian.index'))
            ->assertOk()
            ->assertSee('Tim Punya Dia')
            ->assertDontSee('Tim Orang Lain')
            ->assertDontSee('Tim Belum Ditugaskan');
    }

    public function test_reviewer_403_menilai_pendaftar_yang_bukan_tugasnya(): void
    {
        $bukanTugasnya = Pendaftar::factory()->create(['reviewer_id' => $this->reviewerLain->id]);

        $this->actingAs($this->reviewer)
            ->get(route('admin.penilaian.edit', $bukanTugasnya))
            ->assertForbidden();
    }

    public function test_reviewer_bisa_menilai_pendaftar_tugasnya(): void
    {
        $tugasnya = Pendaftar::factory()->create(['reviewer_id' => $this->reviewer->id]);

        $this->actingAs($this->reviewer)
            ->get(route('admin.penilaian.edit', $tugasnya))
            ->assertOk();
    }

    public function test_admin_bisa_menugaskan_reviewer(): void
    {
        $pendaftar = $this->loloskan(Pendaftar::factory()->create(['reviewer_id' => null]));

        $this->actingAs($this->admin)
            ->put(route('admin.penugasan.update', $pendaftar), ['reviewer_id' => $this->reviewer->id])
            ->assertRedirect();

        $this->assertSame($this->reviewer->id, $pendaftar->fresh()->reviewer_id);
    }

    public function test_penugasan_tolak_user_bukan_reviewer(): void
    {
        $pendaftar = $this->loloskan(Pendaftar::factory()->create());

        $this->actingAs($this->admin)
            ->put(route('admin.penugasan.update', $pendaftar), ['reviewer_id' => $this->admin->id])
            ->assertSessionHasErrors('reviewer_id');
    }

    /**
     * Penugasan harus memakai populasi yang SAMA dengan menu Penilaian.
     * Peserta yang belum lolos administrasi tidak boleh muncul, apalagi
     * ditugaskan lewat request langsung.
     */
    public function test_penugasan_hanya_untuk_yang_lolos_administrasi(): void
    {
        $lolos = $this->loloskan(Pendaftar::factory()->create(['nama_tim' => 'Tim Lolos Adm']));
        $belum = Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Adm', 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->get(route('admin.penugasan.index'))
            ->assertOk()
            ->assertSee('Tim Lolos Adm')
            ->assertDontSee('Tim Belum Adm');

        // memaksa lewat request langsung tetap ditolak
        $this->actingAs($this->admin)
            ->put(route('admin.penugasan.update', $belum), ['reviewer_id' => $this->reviewer->id])
            ->assertForbidden();

        $this->assertNull($belum->fresh()->reviewer_id);
    }

    /** Kalau gerbang dimatikan, semua peserta boleh ditugaskan lagi. */
    public function test_penugasan_terbuka_saat_gating_dimatikan(): void
    {
        PengaturanPenilaian::aktif()->update(['hanya_terverifikasi' => false]);

        $belum = Pendaftar::factory()->create(['nama_tim' => 'Tim Belum Adm', 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->get(route('admin.penugasan.index'))
            ->assertOk()
            ->assertSee('Tim Belum Adm');

        $this->actingAs($this->admin)
            ->put(route('admin.penugasan.update', $belum), ['reviewer_id' => $this->reviewer->id])
            ->assertRedirect();

        $this->assertSame($this->reviewer->id, $belum->fresh()->reviewer_id);
    }

    public function test_menu_rubrik_tidak_muncul_untuk_reviewer(): void
    {
        $this->actingAs($this->reviewer)
            ->get(route('admin.penilaian.index'))
            ->assertOk()
            ->assertDontSee('Rubrik Penilaian')  // menu Rubrik disembunyikan
            ->assertSee('Penugasan')             // menu lain tetap ada
            ->assertSee('Statistik');
    }

    /**
     * Sapuan rute GET untuk reviewer: hanya konfigurasi (rubrik, form administrasi,
     * import) yang boleh 403 — sisanya wajib terbuka. Menjaga supaya penambahan
     * rute baru tidak diam-diam mengunci reviewer atau membocorkan menu admin.
     */
    public function test_sapuan_semua_rute_get_panel_untuk_reviewer(): void
    {
        $hanyaAdmin = ['admin.rubrik.index', 'admin.formadm.index', 'admin.situs.index',
            'admin.pendaftar.import', 'admin.pendaftar.import.template'];

        $this->actingAs($this->reviewer);
        $pendaftar = $this->loloskan(Pendaftar::factory()->create(['reviewer_id' => $this->reviewer->id]));

        foreach (Route::getRoutes() as $rute) {
            $nama = $rute->getName();

            if (! $nama || ! str_starts_with($nama, 'admin.') || ! in_array('GET', $rute->methods(), true)) {
                continue;
            }

            $param = $rute->parameterNames();

            if ($param !== [] && $param !== ['pendaftar']) {
                continue;
            }

            $respons = $this->get(route($nama, $param === [] ? [] : $pendaftar));

            if (in_array($nama, $hanyaAdmin, true)) {
                $respons->assertForbidden();
            } else {
                $respons->assertSuccessful();
            }
        }
    }
}
