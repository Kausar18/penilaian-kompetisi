<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan halaman publik (satu baris saja, seperti pengaturan_penilaian).
 *
 * Yang paling penting: `umumkan_administrasi` & `umumkan_finalis`. Keduanya
 * default MATI supaya hasil verifikasi tidak bocor ke publik begitu reviewer
 * menekan "Lolos" — panitia yang memutuskan kapan diumumkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_situs', function (Blueprint $table) {
            $table->id();

            $table->boolean('situs_aktif')->default(true)->comment('halaman publik bisa dibuka');
            $table->string('judul', 150)->nullable();
            $table->string('subjudul', 250)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('penyelenggara', 200)->nullable();

            // gerbang pengumuman — default mati
            $table->boolean('umumkan_administrasi')->default(false);
            $table->boolean('umumkan_finalis')->default(false);
            $table->text('catatan_pengumuman')->nullable();
            $table->boolean('tampilkan_statistik')->default(true);

            $table->json('timeline')->nullable()->comment('[{tahap, tanggal, selesai}]');

            $table->string('kontak_email', 150)->nullable();
            $table->string('kontak_wa', 40)->nullable();
            $table->string('instagram', 150)->nullable();
            $table->string('situs_lembaga', 200)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_situs');
    }
};
