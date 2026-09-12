<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran pengguna. Fase 1 hanya "admin" yang dipakai; "reviewer" disiapkan
 * untuk fase berikutnya (pembagian tugas reviewer utama / sharia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('peran', 30)->default('admin')->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('peran');
        });
    }
};
