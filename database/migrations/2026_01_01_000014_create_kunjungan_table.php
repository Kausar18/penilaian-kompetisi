<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan kunjungan halaman publik (menggantikan data dummy Fase 1).
 *
 * Privasi: IP pengunjung TIDAK disimpan. Kolom `pengunjung` berisi hash
 * SHA-256 dari IP + user agent + APP_KEY + tanggal, jadi hash-nya berganti
 * tiap hari dan tidak bisa dibalik jadi identitas. Konsekuensinya, "unique
 * visitor" dihitung per hari (pengunjung yang datang 3 hari = 3 unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan', function (Blueprint $table) {
            $table->id();
            $table->string('path', 190)->comment('path halaman publik, tanpa query string');
            $table->char('pengunjung', 64)->comment('hash harian, bukan IP mentah');
            $table->string('perangkat', 10)->default('desktop')->comment('mobile / tablet / desktop');
            $table->string('referrer', 190)->nullable()->comment('host perujuk saja');
            $table->date('tanggal');
            $table->unsignedTinyInteger('jam');
            $table->timestamps();

            $table->index('path');
            $table->index('tanggal');
            $table->index(['tanggal', 'pengunjung']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan');
    }
};
