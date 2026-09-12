<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_id')->constrained('penilaian')->cascadeOnDelete();
            $table->foreignId('indikator_penilaian_id')->constrained('indikator_penilaian')->cascadeOnDelete();
            $table->unsignedTinyInteger('skor')->nullable()->comment('0-5, null = belum dinilai');
            $table->timestamps();

            $table->unique(['penilaian_id', 'indikator_penilaian_id'], 'detail_penilaian_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penilaian');
    }
};
