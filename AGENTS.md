# Panduan untuk asisten AI yang mengerjakan repo ini

Berkas ini dibaca otomatis oleh asisten AI (Claude Code, Claude di web, dan sejenisnya).
Tujuannya memberi konteks yang tidak terbaca dari kode saja.

## Apa ini

Aplikasi web seleksi tenant program **PRIMESTeP** (Science Techno Park IPB), menggantikan
alur lama: isi formulir di kertas lalu direkap ulang ke Excel.

Penjelasan lengkap — modul, skema basis data, rubrik, cara impor, panduan kloning — ada di
**`README.md`**. Baca itu lebih dulu sebelum mengubah apa pun.

## Aturan yang tidak boleh dilanggar

**1. Gerbang antar tahap memakai hasil verifikasi, bukan kolom status.**
Menu Penilaian, Penugasan, Rekap, dan Statistik menyaring lewat
`verifikasi_administrasi.hasil = 'lolos'`. Kolom `pendaftar.status` hanya cermin agar mudah
dibaca. Pernah terjadi bug ketika keduanya bisa berbeda, sehingga peserta yang belum
diverifikasi ikut muncul di daftar penilaian. Jangan membuat gerbang baru dari `status`.

**2. Status pendaftar bersifat baca-saja.**
Satu-satunya cara mengubahnya lewat menu Verifikasi Administrasi, supaya setiap keputusan
lolos/tidak lolos punya jejak butir Juklak mana yang gagal. Rute `admin.pendaftar.status`
sudah dihapus — jangan dihidupkan lagi.

**3. Hasil seleksi tidak pernah tayang otomatis.**
Halaman publik hanya menampilkan peserta yang lolos, dan hanya setelah panitia menyalakan
saklarnya di menu Halaman Publik. Nilai, peringkat, email, dan nomor WhatsApp **tidak pernah**
ditampilkan ke publik — pembatasnya konstanta `KOLOM_AMAN` di `PublikController`.

**4. Jangan pernah menaruh data peserta sungguhan ke dalam repo.**
Repo ini publik. Nama, nomor telepon, dan alamat asli pernah tidak sengaja tersalin ke berkas
test dan harus dibersihkan. Gunakan data karangan. Berkas data asli disimpan di
`storage/app/private/` yang sudah diabaikan git.

**5. Nilai tersimpan dihitung ulang saat rubrik berubah.**
Mengubah bobot, indikator, skala, atau rumus memicu `HitungUlangPenilaian::semua()` supaya
`nilai_final` tidak basi. Kalau menambah jalur yang mengubah rubrik, panggil juga fungsi itu.

## Sebelum menyatakan pekerjaan selesai

```bash
php artisan test        # 91 test harus hijau
./vendor/bin/pint       # gaya kode
```

## Bahasa

Kode, komentar, nama variabel, pesan antarmuka, dan dokumentasi ditulis dalam **Bahasa
Indonesia**. Ikuti gaya yang sudah ada.

## Yang belum dikerjakan

- Pembakuan daftar bidang usaha — kolomnya isian bebas di Google Form sehingga menghasilkan
  37 nilai berbeda; menunggu daftar resmi dari lembaga
- Cetak formulir penilaian ke PDF untuk arsip / tanda tangan basah
- Form pendaftaran publik, sinkronisasi otomatis Google Form, halaman cek status peserta,
  dan multi-reviewer per peserta
