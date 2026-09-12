<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan cara menilai (satu baris saja, id = 1).
 *
 * skala  : daftar nilai yang boleh dipilih reviewer beserta labelnya, mis.
 *          [{"nilai":1,"label":"Kurang"},{"nilai":3,"label":"Sedang"}, ...]
 * rumus  : 'normalisasi' -> (skor / maks skala) x bobot, hasil berskala total bobot (/100)
 *          'mentah'      -> skor x bobot, seperti Form Pitching Battle
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_penilaian', function (Blueprint $table) {
            $table->id();
            $table->json('skala');
            $table->string('rumus', 20)->default('normalisasi');
            $table->boolean('hanya_terverifikasi')->default(true)
                ->comment('hanya pendaftar berstatus verified yang bisa dinilai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_penilaian');
    }
};
