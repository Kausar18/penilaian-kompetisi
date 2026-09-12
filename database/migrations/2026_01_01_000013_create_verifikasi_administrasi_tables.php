<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 1: Verifikasi Administrasi (Form Seleksi Administrasi sesuai Juklak).
 * Checklist Sesuai / Tidak Sesuai / N-A + catatan per baris, ditutup
 * rekomendasi lolos-tidak yang mengisi kolom `pendaftar.status`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Butir checklist — dikelompokkan lewat kolom teks `kelompok` (mis. "A. Kesesuaian Kriteria ...")
        Schema::create('item_verifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kelompok', 150);
            $table->string('nama', 400);
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['kelompok', 'urutan']);
        });

        // Satu baris hasil verifikasi per pendaftar
        Schema::create('verifikasi_administrasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->unique()->constrained('pendaftar')->cascadeOnDelete();
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('hasil', ['lolos', 'tidak_lolos'])->nullable();
            $table->text('catatan')->nullable()->comment('rekomendasi & catatan verifikator');
            $table->timestamp('diverifikasi_at')->nullable()->comment('diisi saat hasil diputuskan');

            $table->timestamps();
        });

        // Centang + catatan per butir
        Schema::create('detail_verifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verifikasi_administrasi_id')->constrained('verifikasi_administrasi')->cascadeOnDelete();
            $table->foreignId('item_verifikasi_id')->constrained('item_verifikasi')->cascadeOnDelete();
            $table->enum('status', ['sesuai', 'tidak_sesuai', 'na'])->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['verifikasi_administrasi_id', 'item_verifikasi_id'], 'detail_verifikasi_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_verifikasi');
        Schema::dropIfExists('verifikasi_administrasi');
        Schema::dropIfExists('item_verifikasi');
    }
};
