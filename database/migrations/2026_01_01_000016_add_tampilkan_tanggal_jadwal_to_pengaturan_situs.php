<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saklar untuk menampilkan/menyembunyikan tanggal pada jadwal di halaman publik.
 *
 * Dibuat supaya panitia bisa menyiapkan tanggal lebih dulu di panel tanpa
 * langsung menayangkannya (mis. jadwal masih tentatif). Default MATI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_situs', function (Blueprint $table) {
            $table->boolean('tampilkan_tanggal_jadwal')->default(false)->after('timeline');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_situs', function (Blueprint $table) {
            $table->dropColumn('tampilkan_tanggal_jadwal');
        });
    }
};
