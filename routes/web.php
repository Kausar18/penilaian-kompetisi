<?php

use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ItemVerifikasiController;
use App\Http\Controllers\Admin\PendaftarController;
use App\Http\Controllers\Admin\PengaturanSitusController;
use App\Http\Controllers\Admin\PengunjungController;
use App\Http\Controllers\Admin\PenilaianController;
use App\Http\Controllers\Admin\PenugasanController;
use App\Http\Controllers\Admin\RekapController;
use App\Http\Controllers\Admin\RubrikController;
use App\Http\Controllers\Admin\RubrikIndikatorController;
use App\Http\Controllers\Admin\StatistikController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Publik\PublikController;
use App\Http\Controllers\SetupSementaraController;
use Illuminate\Support\Facades\Route;

// ==================================================================
// HALAMAN PUBLIK (tanpa login)
// ==================================================================
// Middleware `catat.kunjungan` sengaja hanya dipasang di sini supaya panel
// admin tidak ikut tercatat sebagai kunjungan.
Route::middleware('catat.kunjungan')->group(function () {
    Route::get('/', [PublikController::class, 'beranda'])->name('publik.beranda');
    Route::get('/pengumuman', [PublikController::class, 'pengumuman'])->name('publik.pengumuman');
});

// ==================================================================
// LOGIN / LOGOUT
// ==================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    // Setup admin pertama (token dari env SETUP_TOKEN) — nonaktif kalau env kosong
    Route::get('/setup-admin/{token}', [SetupSementaraController::class, 'create'])->name('setup.admin.create');
    Route::post('/setup-admin/{token}', [SetupSementaraController::class, 'store'])->name('setup.admin.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ==================================================================
// PANEL
// ==================================================================
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // ---------- Bisa dilihat admin & reviewer ----------
    // (reviewer: halaman Penilaian difilter ke pendaftar yang ditugaskan; dijaga di controller)
    Route::get('/', [StatistikController::class, 'index'])->name('statistik');
    Route::get('/pengunjung', [PengunjungController::class, 'index'])->name('pengunjung');

    Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
    Route::get('/pendaftar/export', [PendaftarController::class, 'export'])->name('pendaftar.export');

    // Import pendaftar (khusus admin) — harus di atas rute /pendaftar/{pendaftar}
    Route::middleware('peran:admin')->group(function () {
        Route::get('/pendaftar/import', [ImportController::class, 'create'])->name('pendaftar.import');
        Route::post('/pendaftar/import', [ImportController::class, 'store'])->name('pendaftar.import.store');
        Route::post('/pendaftar/import/proses', [ImportController::class, 'proses'])->name('pendaftar.import.proses');
        Route::get('/pendaftar/import/template', [ImportController::class, 'template'])->name('pendaftar.import.template');
    });

    Route::get('/pendaftar/{pendaftar}/kartu', [PendaftarController::class, 'kartu'])->name('pendaftar.kartu');
    Route::get('/pendaftar/{pendaftar}', [PendaftarController::class, 'show'])->name('pendaftar.show');

    // Tahap 1 — Verifikasi Administrasi
    Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/export', [VerifikasiController::class, 'export'])->name('verifikasi.export');
    Route::get('/verifikasi/{pendaftar}/edit', [VerifikasiController::class, 'edit'])->name('verifikasi.edit');
    Route::put('/verifikasi/{pendaftar}', [VerifikasiController::class, 'update'])->name('verifikasi.update');

    // Tahap 2 — Penilaian substansi
    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/export', [PenilaianController::class, 'export'])->name('penilaian.export');
    Route::get('/penilaian/{pendaftar}/edit', [PenilaianController::class, 'edit'])->name('penilaian.edit');
    Route::put('/penilaian/{pendaftar}', [PenilaianController::class, 'update'])->name('penilaian.update');

    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');

    Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan.index');

    // ---------- Khusus admin: aksi berbahaya + konfigurasi ----------
    Route::middleware('peran:admin')->group(function () {

        // Hapus pendaftar & ubah penugasan reviewer
        Route::delete('/pendaftar/{pendaftar}', [PendaftarController::class, 'destroy'])->name('pendaftar.destroy');
        Route::put('/penugasan/{pendaftar}', [PenugasanController::class, 'update'])->name('penugasan.update');

        // Form Verifikasi Administrasi (butir checklist)
        Route::get('/form-administrasi', [ItemVerifikasiController::class, 'index'])->name('formadm.index');
        Route::post('/form-administrasi', [ItemVerifikasiController::class, 'store'])->name('formadm.store');
        Route::put('/form-administrasi/{itemVerifikasi}', [ItemVerifikasiController::class, 'update'])->name('formadm.update');
        Route::delete('/form-administrasi/{itemVerifikasi}', [ItemVerifikasiController::class, 'destroy'])->name('formadm.destroy');

        // Halaman Publik (beranda + gerbang pengumuman)
        Route::get('/halaman-publik', [PengaturanSitusController::class, 'index'])->name('situs.index');
        Route::put('/halaman-publik', [PengaturanSitusController::class, 'update'])->name('situs.update');

        // Rubrik Penilaian
        Route::get('/rubrik', [RubrikController::class, 'index'])->name('rubrik.index');
        Route::put('/rubrik/pengaturan', [RubrikController::class, 'simpanPengaturan'])->name('rubrik.pengaturan');
        Route::post('/rubrik/kategori', [RubrikController::class, 'store'])->name('rubrik.store');
        Route::put('/rubrik/kategori/{kategoriPenilaian}', [RubrikController::class, 'update'])->name('rubrik.update');
        Route::delete('/rubrik/kategori/{kategoriPenilaian}', [RubrikController::class, 'destroy'])->name('rubrik.destroy');

        Route::post('/rubrik/indikator', [RubrikIndikatorController::class, 'store'])->name('rubrik.indikator.store');
        Route::put('/rubrik/indikator/{indikatorPenilaian}', [RubrikIndikatorController::class, 'update'])->name('rubrik.indikator.update');
        Route::delete('/rubrik/indikator/{indikatorPenilaian}', [RubrikIndikatorController::class, 'destroy'])->name('rubrik.indikator.destroy');
    });
});
