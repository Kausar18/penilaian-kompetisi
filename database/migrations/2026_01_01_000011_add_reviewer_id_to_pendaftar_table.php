<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan reviewer: satu pendaftar ditugaskan ke satu reviewer (Opsi A).
 * Null = belum ditugaskan. Reviewer hanya bisa menilai pendaftar miliknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('bidang_kompetisi_id')
                ->constrained('users')->nullOnDelete();
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
            $table->dropColumn('reviewer_id');
        });
    }
};
