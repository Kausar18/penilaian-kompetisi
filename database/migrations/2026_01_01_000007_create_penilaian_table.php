<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris penilaian per pendaftar (Fase 1: satu reviewer per pendaftar).
 * reviewer_id disimpan supaya multi-reviewer bisa diaktifkan nanti tanpa
 * mengubah skema besar. nilai_final / nilai_mentah adalah cache hasil
 * hitung dari detail_penilaian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->unique()->constrained('pendaftar')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('rekomendasi', ['lolos', 'tidak_lolos'])->nullable();
            $table->text('catatan_reviewer')->nullable();

            $table->decimal('nilai_final', 6, 2)->nullable()->comment('total tertimbang /100 (cache)');
            $table->decimal('nilai_mentah', 6, 2)->nullable()->comment('jumlah skor 0-5 seluruh indikator (cache)');
            $table->timestamp('dinilai_at')->nullable()->comment('diisi saat semua indikator + rekomendasi lengkap');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
    }
};
