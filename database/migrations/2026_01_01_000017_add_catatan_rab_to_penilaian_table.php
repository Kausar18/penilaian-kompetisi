<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian "Catatan Verifikasi RAB" pada form penilaian substansi.
 *
 * Kolom `catatan_reviewer` yang sudah ada dipakai untuk bagian "Kesimpulan",
 * jadi yang ditambahkan di sini hanya dua isian khusus RAB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->text('catatan_rab')->nullable()->after('catatan_reviewer')
                ->comment('Catatan Verifikasi RAB - Komentar');
            $table->text('rekomendasi_anggaran')->nullable()->after('catatan_rab')
                ->comment('Catatan Verifikasi RAB - Rekomendasi Anggaran');
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropColumn(['catatan_rab', 'rekomendasi_anggaran']);
        });
    }
};
