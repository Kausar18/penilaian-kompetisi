<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data peserta kompetisi. Fase 1 diisi lewat import CSV/Excel hasil
 * export Google Form; kolom data_asli menyimpan baris mentah untuk audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 40)->nullable()->comment('nomor pendaftaran kalau ada');
            $table->date('tanggal_daftar')->nullable();

            $table->foreignId('kategori_peserta_id')->nullable()
                ->constrained('kategori_peserta')->nullOnDelete();
            $table->foreignId('bidang_kompetisi_id')->nullable()
                ->constrained('bidang_kompetisi')->nullOnDelete();

            // ---------- Identitas ketua & tim ----------
            $table->string('nama_ketua', 150);
            $table->string('email', 150)->nullable();
            $table->string('no_wa', 40)->nullable();
            $table->string('nama_tim', 200)->comment('nama tim / usaha / startup');

            // ---------- Inovasi / usaha ----------
            $table->text('judul_inovasi')->nullable();
            $table->text('deskripsi_singkat')->nullable();
            $table->text('permasalahan')->nullable();
            $table->text('solusi')->nullable();
            $table->text('target_pengguna')->nullable();
            $table->text('dampak_sosial')->nullable();

            // ---------- Institusi (kategori Mahasiswa) ----------
            $table->string('asal_institusi', 200)->nullable();
            $table->string('perguruan_tinggi', 200)->nullable();
            $table->string('fakultas_prodi', 200)->nullable();
            $table->string('nim_ketua', 40)->nullable();
            $table->string('semester', 20)->nullable();

            // ---------- Wilayah & berkas ----------
            $table->string('kota', 120)->nullable();
            $table->string('provinsi', 120)->nullable();
            $table->string('link_pitchdeck', 500)->nullable();
            $table->string('link_logo', 500)->nullable();

            $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
            $table->json('data_asli')->nullable()->comment('baris mentah dari Google Form');

            $table->timestamps();

            $table->index('kategori_peserta_id');
            $table->index('bidang_kompetisi_id');
            $table->index('status');
            $table->index('provinsi');
            $table->index('tanggal_daftar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
