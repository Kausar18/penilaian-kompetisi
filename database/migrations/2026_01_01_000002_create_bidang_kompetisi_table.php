<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_kompetisi', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique()->comment('mis. Food & Beverages (F&B)');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang_kompetisi');
    }
};
