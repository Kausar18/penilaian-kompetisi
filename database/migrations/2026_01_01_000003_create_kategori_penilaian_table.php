<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelompok penilaian berbobot (mis. ADM 40%, Substansi 60%).
 * Bobot dijumlahkan indikator di dalamnya; subtotal maksimum kelompok
 * = bobot_persen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_penilaian', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('mis. ADM / SUBSTANSI - dipakai internal');
            $table->string('nama', 100);
            $table->unsignedSmallInteger('bobot_persen')->comment('kontribusi kelompok ini ke nilai akhir /100');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_penilaian');
    }
};
