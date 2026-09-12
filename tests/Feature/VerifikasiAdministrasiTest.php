<?php

namespace Tests\Feature;

use App\Models\ItemVerifikasi;
use App\Models\Pendaftar;
use App\Models\User;
use App\Models\VerifikasiAdministrasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiAdministrasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('peran', 'admin')->firstOrFail();
        $this->reviewer = User::factory()->create(['peran' => 'reviewer']);
    }

    /** Isi checklist penuh dengan status tertentu + rekomendasi. */
    private function isiForm(string $statusItem, ?string $hasil): array
    {
        $status = ItemVerifikasi::aktif()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $statusItem])
            ->all();

        return [
            'status' => $status,
            'catatan_item' => [],
            'hasil' => $hasil,
            'catatan' => 'Catatan verifikator uji.',
        ];
    }

    public function test_butir_checklist_juklak_terpasang(): void
    {
        $this->assertSame(13, ItemVerifikasi::count());
        $this->assertSame(2, ItemVerifikasi::distinct('kelompok')->count('kelompok'));
    }

    public function test_admin_dan_reviewer_bisa_buka_form_verifikasi(): void
    {
        $pendaftar = Pendaftar::factory()->create();

        foreach ([$this->admin, $this->reviewer] as $user) {
            $this->actingAs($user)->get(route('admin.verifikasi.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.verifikasi.edit', $pendaftar))->assertOk();
        }
    }

    public function test_konfigurasi_butir_hanya_untuk_admin(): void
    {
        $this->actingAs($this->admin)->get(route('admin.formadm.index'))->assertOk();
        $this->actingAs($this->reviewer)->get(route('admin.formadm.index'))->assertForbidden();
    }

    public function test_hasil_lolos_membuat_pendaftar_terverifikasi(): void
    {
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->reviewer)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('sesuai', 'lolos'))
            ->assertRedirect(route('admin.verifikasi.index'));

        $this->assertSame('verified', $pendaftar->fresh()->status);

        $v = VerifikasiAdministrasi::where('pendaftar_id', $pendaftar->id)->firstOrFail();
        $this->assertSame('lolos', $v->hasil);
        $this->assertSame($this->reviewer->id, $v->verifikator_id);
        $this->assertNotNull($v->diverifikasi_at);
        $this->assertSame(ItemVerifikasi::aktif()->count(), $v->detail()->where('status', 'sesuai')->count());
    }

    public function test_hasil_tidak_lolos_membuat_pendaftar_ditolak(): void
    {
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('tidak_sesuai', 'tidak_lolos'))
            ->assertRedirect();

        $this->assertSame('rejected', $pendaftar->fresh()->status);
    }

    public function test_draft_tidak_mengubah_status_pendaftar(): void
    {
        $pendaftar = Pendaftar::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('sesuai', null))
            ->assertRedirect();

        $this->assertSame('submitted', $pendaftar->fresh()->status);

        $v = VerifikasiAdministrasi::where('pendaftar_id', $pendaftar->id)->firstOrFail();
        $this->assertNull($v->hasil);
        $this->assertNull($v->diverifikasi_at);
    }

    public function test_status_butir_di_luar_pilihan_ditolak(): void
    {
        $pendaftar = Pendaftar::factory()->create();
        $item = ItemVerifikasi::aktif()->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), ['status' => [$item->id => 'ngasal']])
            ->assertSessionHasErrors('status.'.$item->id);
    }

    public function test_lolos_administrasi_membuat_peserta_masuk_daftar_penilaian(): void
    {
        // gating aktif secara default: hanya peserta Terverifikasi yang bisa dinilai
        $pendaftar = Pendaftar::factory()->create([
            'nama_tim' => 'Tim Lolos Administrasi',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertDontSee('Tim Lolos Administrasi');

        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('sesuai', 'lolos'));

        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertSee('Tim Lolos Administrasi');
    }

    /** Regresi: status 'verified' tanpa hasil verifikasi tidak boleh lolos gerbang. */
    public function test_status_verified_tanpa_verifikasi_tidak_muncul_di_penilaian(): void
    {
        Pendaftar::factory()->create([
            'nama_tim' => 'Tim Verified Palsu',
            'status' => 'verified',      // dipasang langsung, tanpa lewat form verifikasi
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertOk()
            ->assertDontSee('Tim Verified Palsu');
    }

    /**
     * Sejak dropdown status dijadikan baca-saja, satu-satunya jalan mengubah
     * status adalah lewat checklist — dan hasilnya tetap satu sumber kebenaran:
     * status pendaftar dan hasil verifikasi selalu sejalan.
     */
    public function test_checklist_satu_satunya_jalan_mengubah_status(): void
    {
        $pendaftar = Pendaftar::factory()->create(['nama_tim' => 'Tim Manual', 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('sesuai', 'lolos'))
            ->assertRedirect();

        $pendaftar->refresh();
        $this->assertSame('verified', $pendaftar->status);
        $this->assertSame('lolos', $pendaftar->verifikasi->hasil);

        // dan peserta itu langsung masuk daftar penilaian
        $this->actingAs($this->admin)
            ->get(route('admin.penilaian.index'))
            ->assertSee('Tim Manual');

        // dikembalikan ke draft -> hasil verifikasi ikut dikosongkan, status turun lagi
        $this->actingAs($this->admin)
            ->put(route('admin.verifikasi.update', $pendaftar), $this->isiForm('sesuai', null))
            ->assertRedirect();

        $this->assertNull($pendaftar->fresh()->verifikasi->hasil);
    }

    public function test_admin_bisa_kelola_butir_checklist(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.formadm.store'), [
            'kelompok' => 'C. Kelompok Uji',
            'nama' => 'Butir uji coba',
        ])->assertRedirect();

        $item = ItemVerifikasi::where('nama', 'Butir uji coba')->firstOrFail();
        $this->assertTrue($item->aktif);

        $this->put(route('admin.formadm.update', $item), [
            'kelompok' => 'C. Kelompok Uji',
            'nama' => 'Butir uji coba (diubah)',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame('Butir uji coba (diubah)', $item->nama);
        $this->assertFalse($item->aktif);   // checkbox tidak dikirim = nonaktif

        $this->delete(route('admin.formadm.destroy', $item))->assertRedirect();
        $this->assertDatabaseMissing('item_verifikasi', ['id' => $item->id]);
    }

    public function test_reviewer_tidak_bisa_kelola_butir_checklist(): void
    {
        $item = ItemVerifikasi::aktif()->firstOrFail();

        $this->actingAs($this->reviewer);
        $this->post(route('admin.formadm.store'), ['kelompok' => 'X', 'nama' => 'Y'])->assertForbidden();
        $this->delete(route('admin.formadm.destroy', $item))->assertForbidden();
        $this->assertDatabaseHas('item_verifikasi', ['id' => $item->id]);
    }
}
