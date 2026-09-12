<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indikator di dalam sebuah kelompok penilaian. Skor per indikator 0-5,
 * dikonversi jadi (skor / 5) * bobot. Jumlah bobot seluruh indikator
 * idealnya = 100 (dibagi sesuai bobot_persen tiap kelompok).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_penilaian_id')->constrained('kategori_penilaian')->cascadeOnDelete();
            $table->string('nama', 200);
            $table->text('deskripsi')->nullable();
            $table->decimal('bobot', 6, 2)->comment('bobot indikator terhadap nilai akhir /100');
            $table->string('peran_khusus', 30)->nullable()
                ->comment('kalau diisi, hanya reviewer dengan peran ini yang menilai (mis. reviewer_sharia). null = semua reviewer');
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['kategori_penilaian_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_penilaian');
    }
};
