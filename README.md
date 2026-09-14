# Sistem Penilaian Seleksi Startup PRIMESTeP

Aplikasi web untuk menjalankan seleksi tenant program **PRIMESTeP** (Science Techno Park IPB):
menggantikan alur lama yang mengisi formulir di kertas lalu direkap ulang ke Excel.

Seleksinya **dua tahap**, dan sistem ini mengikuti keduanya persis seperti formulir aslinya:

```
Pendaftar masuk (impor dari Google Form)
   -> Tahap 1  Verifikasi Administrasi   : checklist 13 butir Juklak
   -> lolos
   -> Tahap 2  Penilaian Substansi       : rubrik berbobot, nilai maksimum 900
   -> Rekap & peringkat
   -> (opsional) Pengumuman ke halaman publik
```

Dibangun dengan **Laravel 13**, **PHP 8.3**, **MySQL**, **Bootstrap 5.3**, dan **Chart.js 4.4**.

## Status proyek

| Bagian | Status |
|---|---|
| Impor data Google Form (CSV/Excel) | Selesai, teruji dengan 150 pendaftar nyata |
| Tahap 1 — Verifikasi Administrasi | Selesai |
| Tahap 2 — Penilaian Substansi | Selesai |
| Rekap & peringkat, export CSV | Selesai |
| Penugasan reviewer | Selesai |
| Halaman publik + statistik pengunjung | Selesai |
| Pembakuan daftar bidang usaha | **Belum** — kolomnya isian bebas di Google Form, menghasilkan 37 nilai berbeda |
| Cetak formulir penilaian ke PDF | **Belum** |

Diuji dengan **91 test otomatis** (`php artisan test`) dan gaya kode dijaga Laravel Pint.

## Modul

| Modul | Isi |
|-------|-----|
| **Statistik** | Kartu ringkasan, tren pendaftaran (mencakup seluruh masa pendaftaran; satuan menyesuaikan harian/mingguan/bulanan), sebaran bidang, status verifikasi, sebaran provinsi, ringkasan penilaian. |
| **Pendaftar** | Tabel + cari/filter (kategori, bidang, status), detail, hapus, **import CSV/Excel** dari Google Form, export CSV. |
| **Pengunjung** | Statistik kunjungan halaman publik dari **data nyata** (tabel `kunjungan`): tren 30 hari, per jam, halaman terpopuler, perangkat, sumber trafik. |
| **Verifikasi Administrasi** | *(tahap 1)* Checklist Sesuai / Tidak Sesuai / N-A + catatan per butir sesuai Juklak, rekomendasi Lolos–Tidak Lolos yang otomatis memasang status pendaftar, export CSV. |
| **Penilaian** | *(tahap 2 — substansi)* Daftar + filter, halaman Edit Nilai dengan rubrik berbobot (default 6 kelompok Form Penilaian Substansi), skor per indikator mengikuti **skala yang bisa diatur** (0–5 / 1–5 / 1-3-5-7 / 9-7-5-3-1), Catatan Verifikasi RAB, kesimpulan, rekomendasi, export CSV. |
| **Rekap Nilai** | Ranking peserta berdasar nilai akhir, filter (kategori/bidang/reviewer/rekomendasi/urutan), export CSV. |
| **Rubrik Penilaian** | Konfigurasi kelompok & indikator penilaian beserta bobotnya (tidak hardcode). |
| **Penugasan Reviewer** | Admin membagi peserta ke reviewer (satu peserta = satu reviewer). |
| **Form Administrasi** | Konfigurasi butir checklist verifikasi (khusus admin). |
| **Halaman Publik** | Situs untuk pengunjung tanpa login (beranda + pengumuman hasil) beserta kendali kapan hasil boleh tayang (khusus admin). |

### Alur penilaian

```
Pendaftar masuk
   -> Verifikasi Administrasi (checklist Juklak)  -> Lolos  = status Terverifikasi
                                                  -> Tidak  = status Ditolak
   -> Penilaian substansi / Pitching Battle (hanya peserta Terverifikasi)
   -> Rekap Nilai / ranking
   -> (opsional) Pengumuman ke publik — hanya kalau panitia menyalakannya
```

### Peran akun

- **admin** — akses penuh.
- **reviewer** — semua menu **kecuali Rubrik Penilaian, Form Administrasi & Halaman Publik**
  (disembunyikan & diblokir 403). Reviewer **boleh** mengisi Verifikasi Administrasi. Di halaman
  **Penilaian**, reviewer hanya melihat startup yang ditugaskan kepadanya dan tidak bisa membuka
  Edit Nilai peserta milik reviewer lain.

### Status pendaftar bersifat baca-saja

Kolom `pendaftar.status` adalah **cermin** dari hasil Verifikasi Administrasi, bukan saklar
tersendiri. Satu-satunya cara mengubahnya adalah lewat menu **Verifikasi Adm.** — dropdown
yang dulu ada di panel detail sudah dihapus beserta rutenya (`admin.pendaftar.status`).

Alasannya jejak audit: keputusan lolos/tidak lolos harus selalu disertai butir Juklak mana
yang gagal beserta catatannya. Kalau peserta menyanggah, itulah jawabannya. Status yang bisa
diubah satu klik tanpa alasan tidak meninggalkan apa pun.

Reviewer tetap bisa menentukan lolos/tidak lolos — hanya jalannya yang berubah, lewat
form checklist, bukan dropdown.

Buat akun reviewer: `php artisan admin:buat --peran=reviewer --username=juri1 --name="Nama Lengkap" --password=...`

Belum termasuk: form pendaftaran publik, auto-sync Google Form, halaman cek status peserta,
peran Reviewer Utama/Sharia + indikator per-peran, multi-reviewer per peserta + agregasi.

## Skema basis data

Alur data mengikuti dua tahap seleksi. Tanda panah menunjukkan relasi kunci asing.

```
kategori_peserta ──┐                    users (admin / reviewer)
bidang_kompetisi ──┤                      │
                   ▼                      │ reviewer_id
                pendaftar ◄───────────────┘
                   │
                   ├──► anggota_tim                 (banyak per pendaftar)
                   │
                   ├──► verifikasi_administrasi     TAHAP 1, satu per pendaftar
                   │         └──► detail_verifikasi ──► item_verifikasi (13 butir Juklak)
                   │
                   └──► penilaian                   TAHAP 2, satu per pendaftar
                             └──► detail_penilaian ──► indikator_penilaian
                                                          └──► kategori_penilaian
```

| Tabel | Isi |
|---|---|
| `pendaftar` | Data peserta hasil impor Google Form; kolom `data_asli` menyimpan baris mentahnya sebagai JSON |
| `anggota_tim` | Anggota tim, hasil penguraian satu sel teks bebas |
| `item_verifikasi` | Butir checklist Juklak — bisa diubah admin lewat menu Form Administrasi |
| `verifikasi_administrasi` | Hasil tahap 1 per peserta (`lolos` / `tidak_lolos` / draft) |
| `detail_verifikasi` | Jawaban per butir: `sesuai` / `tidak_sesuai` / `na`, beserta catatan |
| `kategori_penilaian` · `indikator_penilaian` | Rubrik tahap 2 — kelompok dan indikator beserta bobotnya |
| `penilaian` | Hasil tahap 2 per peserta: nilai akhir, rekomendasi, catatan RAB, kesimpulan |
| `detail_penilaian` | Skor per indikator |
| `pengaturan_penilaian` | Satu baris: skala nilai, rumus, dan gerbang "hanya peserta terverifikasi" |
| `pengaturan_situs` | Satu baris: isi halaman publik dan saklar pengumuman |
| `kunjungan` | Statistik pengunjung halaman publik; tidak menyimpan IP |
| `import_log` | Riwayat tiap impor beserta jumlah baris berhasil/gagal |

**Gerbang antar tahap.** Menu Penilaian, Penugasan, Rekap, dan Statistik menyaring peserta
lewat `verifikasi_administrasi.hasil = 'lolos'` — **bukan** lewat kolom `pendaftar.status`.
Kolom status hanya cermin agar mudah dibaca. Ini disengaja: pernah terjadi bug ketika keduanya
bisa berbeda, sehingga peserta yang belum diverifikasi ikut muncul di daftar penilaian.

## Setup lokal (Laragon)

```bash
composer install
cp .env.example .env
php artisan key:generate

# buat database MySQL: penilaian_kompetisi (root / tanpa password di Laragon)
php artisan migrate --seed
```

Jalankan: `php artisan serve` lalu buka `http://127.0.0.1:8000`, atau lewat vhost Laragon
`http://penilaian-kompetisi.test`.

**Login admin default** (dari `.env`, dibuat saat `db:seed`):
username `admin` / `password` — ganti `ADMIN_USERNAME` & `ADMIN_PASSWORD` sebelum seed untuk kredensial lain.

Saat `APP_ENV=local` dan tabel `pendaftar` kosong, seeder juga mengisi ±28 pendaftar contoh
+ sebagian penilaian, supaya dashboard & rekap tidak kosong.

### Buat / ganti admin lewat CLI

```bash
php artisan admin:buat                                             # interaktif
php artisan admin:buat --name="Admin" --username=admin --password=rahasiakuat123
php artisan admin:buat --username=admin --password=sandibaru123     # username sudah ada -> ganti sandi
```

### Buat admin di server tanpa SSH

Isi `SETUP_TOKEN` di `.env` dengan string acak, lalu buka `/setup-admin/{token}`.
Kosongkan lagi `SETUP_TOKEN` setelah akun dibuat (route otomatis nonaktif kalau env kosong).

## Import data pendaftar (Google Form)

1. Di Google Sheets hasil form: **File → Download → CSV** (atau `.xlsx`).
2. Menu **Pendaftar → Import** → unggah berkas. Klik **Unduh template** untuk contoh format kolom.
3. Kolom dicocokkan berdasarkan **kata kunci pada judul kolom** (urutan bebas). Satu tim dianggap
   sama bila `email + nama tim` sama — import ulang memperbarui, bukan menggandakan. Kategori & bidang
   baru dibuat otomatis. Ringkasan tiap import tersimpan di tabel `import_log`.

## Kota & provinsi ditebak dari alamat

Google Form PRIMESTeP tidak punya kolom Kota/Provinsi terpisah — hanya "Alamat Rumah" dan
"Alamat Usaha/Pabrik" berupa teks bebas. Tanpa penanganan khusus, kolom kota/provinsi dan
grafik **Provinsi Asal Peserta** di Statistik selalu kosong.

`App\Support\WilayahIndonesia::dariAlamat()` menebaknya dengan empat langkah berurutan:

1. nama provinsi yang ditulis apa adanya ("… Garut, **Jawa Barat**")
2. singkatan lazim (`jabar`, `jatim`, `tangsel`, `DIY`, `NTB`, …)
3. tabel **kota/kabupaten → provinsi** — ini yang paling berpengaruh, karena banyak alamat
   hanya menyebut kotanya ("… Kota **Depok**" → Jawa Barat)
4. pola "Kota/Kab. X" untuk kota di luar tabel

Hasil uji terhadap 154 alamat asli Batch 1: **provinsi 94%, kota 90%**. Alamat yang hanya
memuat nama jalan dibiarkan kosong — tidak ditebak asal — dan bisa dikoreksi manual.

**Kalau form punya kolom Kota/Provinsi sendiri, kolom itu yang dipakai;** penebakan alamat
hanya berlaku saat kolomnya tidak ada atau kosong.

## Rubrik penilaian

Menu **Rubrik Penilaian** (sidebar, bagian Konfigurasi). Total bobot indikator aktif harus **100**.

Rubrik bawaan mengikuti **Form Penilaian Substansi** (PRIMESTeP / LPAAI IPB) — 6 kelompok /
17 indikator, total bobot 100:

| # | Kelompok | Bobot | Indikator (bobot) |
|---|---|---|---|
| 1 | Tim | 20 | Karakter & Komitmen Tim Pendiri (6), Komposisi & Kualifikasi Tim Pendiri (10), Pengalaman Wirausaha (4) |
| 2 | Produk dan Model Bisnis | 25 | Permasalahan yang Dipecahkan (5), Kualitas Produk (10), Model Bisnis (5), Dampak Sosial dan Lingkungan (5) |
| 3 | Pasar dan Kompetisi | 15 | Market Size (7), Keunggulan Kompetitif (8) |
| 4 | Strategi | 20 | Strategi Pemasaran (8), Roadmap Produk dan Bisnis (7), Action Plan (5) |
| 5 | Pengelolaan Bisnis | 5 | Pengelolaan Keuangan dan Operasional (5) |
| 6 | Traction / Perkembangan Usaha | 15 | Riset Pengguna (4), Kesiapan Produk (4), Jumlah Pengguna/Pelanggan (3), Tingkat Retensi Pengguna/Pelanggan (4) |

Skala nilainya **9 (ideal) / 7 / 5 / 3 / 1 (kurang)** dengan rumus *mentah*, sehingga
**nilai maksimum = 9 × 100 = 900**. Form aslinya hanya memberi nama pada ujung skala; label
tengah (3 = Cukup, 5 = Baik, 7 = Sangat Baik) ditambahkan supaya terbaca di layar dan bisa
diubah lewat menu Rubrik.

Selain skor, form penilaian substansi juga memuat isian bebas:

| Bagian | Kolom |
|---|---|
| **Catatan Verifikasi RAB** | Komentar, Rekomendasi Anggaran |
| **Kesimpulan** | Komentar (tersimpan di kolom `catatan_reviewer`) |

Ketiganya ikut terbawa ke export Rekap Nilai.

Semuanya bisa diubah dari menu Rubrik (kelompok, indikator, bobot).

### Memasang ulang rubrik substansi di database yang sudah jalan

Seeder memakai `firstOrCreate` sehingga **tidak pernah menimpa** rubrik yang sudah ada.
Untuk mengganti rubrik pada instalasi yang sudah berjalan:

```bash
php artisan rubrik:substansi          # konfirmasi dulu
php artisan rubrik:substansi --force  # tanpa konfirmasi
```

> Perintah ini **menghapus skor per indikator** yang sudah tersimpan, karena indikator lamanya
> ikut terhapus (`cascadeOnDelete`). Perintahnya menyebutkan berapa penilaian yang terdampak
> sebelum meminta konfirmasi.

### Pengaturan Penilaian

Di halaman yang sama ada panel **Pengaturan Penilaian**:

| Pengaturan | Pilihan |
|---|---|
| **Skala nilai** | `0–5`, `1–5`, `1 / 3 / 5 / 7` (Form Pitching Battle), atau **`1 / 3 / 5 / 7 / 9`** (Form Penilaian Substansi: 9=Ideal … 1=Kurang) |
| **Rumus nilai akhir** | *Dinormalkan* → `(skor ÷ nilai tertinggi) × bobot` (nilai akhir /100) &middot; *Mentah* → `nilai × bobot` (sesuai kolom "Total (Nilai x Bobot)" di form, maksimum 9 × 100 = 900) |
| **Hanya nilai peserta Terverifikasi** | kalau aktif, hanya pendaftar berstatus **Terverifikasi** (lolos administrasi) yang muncul di menu Penilaian |

Label skala tampil sebagai keterangan di halaman Edit Nilai.

**Ganti rubrik / pengaturan setelah ada nilai masuk?** Aman. Setiap perubahan bobot, indikator,
skala, atau rumus otomatis memicu `App\Support\HitungUlangPenilaian::semua()` — semua `nilai_final`
yang sudah tersimpan dihitung ulang memakai aturan baru, jadi ranking di Rekap tidak pernah basi.
Kalau ada **indikator baru** yang membuat penilaian lama jadi belum lengkap, penilaian itu turun
lagi jadi **draft** supaya reviewer melengkapinya. Skor mentah per indikator tidak dihapus; nilai
yang di luar skala baru tetap ditampilkan sebagai "(skala lama)".

## Halaman publik (tanpa login)

Selain panel panitia, sistem menyajikan situs untuk pengunjung umum. Tidak perlu akun.

| URL | Isi |
|---|---|
| `/` | Beranda: hero, kartu angka, alur seleksi 2 tahap, bidang fokus, jadwal kegiatan, ajakan ke pengumuman. |
| `/pengumuman` | Daftar peserta yang **lolos** — tab *Lolos Administrasi* dan *Finalis*, dengan pencarian & filter bidang. |

Panel panitia pindah ke `/admin` (login tetap di `/login`).

### Aturan data yang dipegang halaman publik

Tiga hal ini dijaga di kode **dan** ada test regresinya di `tests/Feature/PublikTest.php`:

1. **Hanya yang lolos yang tampil.** Peserta yang ditolak atau belum diverifikasi tidak pernah
   muncul di halaman publik — supaya tidak mempermalukan peserta.
2. **Nilai tidak pernah publik.** Skor, peringkat, dan catatan reviewer bersifat internal.
   Yang tayang cuma nama tim, judul inovasi, bidang, kategori, dan kota/provinsi. Kolom yang boleh
   keluar dibatasi eksplisit lewat konstanta `KOLOM_AMAN` di `PublikController` — email dan nomor WA
   tidak ikut ter-query sama sekali, termasuk lewat kotak pencarian.
3. **Tidak ada yang tayang otomatis.** Menandai peserta "Lolos" di panel **tidak** membuatnya langsung
   terlihat publik. Panitia harus menyalakan saklarnya sendiri di menu **Halaman Publik**.

### Menu Halaman Publik (khusus admin)

Sidebar → Konfigurasi → **Halaman Publik**. Isinya:

| Bagian | Fungsi |
|---|---|
| **Gerbang Pengumuman** | Dua saklar: *Umumkan hasil administrasi* dan *Umumkan finalis*. Keduanya **default mati**. Tiap saklar menunjukkan berapa tim yang akan terlihat publik kalau dinyalakan. |
| **Identitas Halaman** | Saklar induk *Halaman publik aktif* (kalau mati, semua halaman publik jadi "segera hadir"), judul, subjudul, penyelenggara, deskripsi. |
| **Jadwal Kegiatan** | Baris tahap + tanggal (teks bebas) + tanda "selesai". Baris yang tahapnya dikosongkan otomatis dibuang saat disimpan. |
| **Kontak** | Email, WhatsApp, Instagram, situs lembaga — tampil di footer halaman publik. |

> Saklar induk *Halaman publik aktif* adalah checkbox: menyimpan form tanpa mencentangnya akan
> **mematikan** situs publik. Perilaku ini disengaja dan dikunci test.

## Statistik pengunjung

Kunjungan halaman publik dicatat oleh middleware `catat.kunjungan` (alias di `bootstrap/app.php`),
yang sengaja **hanya** dipasang di grup rute publik — panel panitia tidak ikut tercatat.

Tidak dihitung sebagai kunjungan: panitia yang sedang login, robot mesin pencari (dicocokkan dari
user agent), request non-GET/AJAX, dan respons selain 200.

**Privasi:** alamat IP tidak pernah disimpan. Kolom `kunjungan.pengunjung` berisi hash SHA-256 dari
IP + user agent + `APP_KEY` + tanggal. Karena tanggal ikut di-hash, identitasnya berganti tiap hari
dan tidak bisa dibalik. Konsekuensi yang perlu diketahui: **"unique visitor" dihitung per hari** —
orang yang datang di 3 hari berbeda terhitung 3, bukan 1. Ini pilihan sadar (pola yang sama dipakai
Plausible) supaya database tetap aman diserahkan ke instansi.

Statistik gagal mencatat tidak pernah menjatuhkan halaman publik — pencatatan dibungkus `try/catch`.

**Kunjungan saat pengembangan lokal ikut tercatat.** Membuka `/` atau `/pengumuman` di
`127.0.0.1` sambil logout tetap dihitung sebagai pengunjung — yang tidak dihitung hanya
panitia yang sedang login dan robot. Karena itu angkanya perlu dinolkan sebelum situs
benar-benar dibuka:

```bash
php artisan kunjungan:kosongkan          # konfirmasi dulu
php artisan kunjungan:kosongkan --force  # tanpa konfirmasi
```

Perintah ini hanya menghapus tabel `kunjungan`; data pendaftar, penilaian, dan verifikasi
tidak tersentuh.

## Kloning: dua instalasi terpisah (arsip batch lama & instansi)

Bisa, dan tidak perlu ubah kode sama sekali — semua yang membedakan satu instalasi dari yang lain
ada di `.env` + database, bukan di dalam aplikasi. Tidak ada berkas unggahan permanen
(`storage/app` cuma menampung CSV import sementara), jadi yang perlu dipindahkan hanya
**kode + database**.

Targetnya:

| Instalasi | Isi | Dipakai untuk |
|---|---|---|
| **Punya kamu** | data batch sebelumnya | praktik lapang / arsip |
| **Punya instansi** | kosong, siap Batch 3 | operasional ke depan |

### 1. Siapkan salinan kode

```bash
# salin folder project (tanpa vendor & node_modules — nanti di-install ulang)
# atau, kalau sudah pakai git: git clone <repo> penilaian-kompetisi-instansi
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate      # WAJIB: setiap instalasi punya APP_KEY sendiri
```

> `APP_KEY` jangan disamakan antar instalasi — kunci itu yang mengenkripsi session & cookie.

### 2. Beri masing-masing database sendiri

`.env` instalasi instansi:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://penilaian.domain-instansi.ac.id
DB_DATABASE=penilaian_kompetisi          # database terpisah, bukan yang kamu pakai
SETUP_TOKEN=                             # biarkan kosong
ADMIN_USERNAME=admin
ADMIN_PASSWORD=<sandi kuat, ganti>
```

`.env` instalasi arsip kamu boleh tetap `APP_ENV=local` dengan `DB_DATABASE=penilaian_kompetisi_arsip`.

> `APP_ENV=production` juga mematikan seeder data contoh — instansi tidak akan kebagian 28 pendaftar dummy.

### 3. Isi database

**Instalasi instansi (mulai bersih untuk Batch 3):**

```bash
php artisan migrate --force      # tabel + master data
php artisan db:seed --force      # rubrik Pitching Battle, 13 butir Juklak, pengaturan, akun admin
php artisan admin:buat --peran=reviewer --username=juri1 --name="Nama Reviewer" --password=...
```

Yang ikut ter-seed cuma **master data** (rubrik, butir administrasi, kategori, bidang, pengaturan) —
tabel `pendaftar` dan `penilaian` tetap kosong.

**Instalasi arsip kamu (bawa data batch lama):**

```bash
# di instalasi sumber
mysqldump -u root penilaian_kompetisi > batch-lama.sql
# di instalasi arsip
mysql -u root penilaian_kompetisi_arsip < batch-lama.sql
php artisan migrate --force      # jaga-jaga kalau ada migrasi baru
```

### 4. Sebelum menyerahkan ke instansi

- [ ] `APP_DEBUG=false` dan `APP_ENV=production` (jangan sampai halaman error membocorkan isi `.env`)
- [ ] `APP_KEY` sudah di-generate ulang, **beda** dari punyamu
- [ ] `SETUP_TOKEN` kosong → `/setup-admin/{token}` otomatis 404
- [ ] sandi `admin` default **diganti** (`php artisan admin:buat --username=admin --password=...`)
- [ ] hapus akun reviewer contoh (`reviewer1`–`reviewer4`) kalau terbawa
- [ ] statistik pengunjung dinolkan: `php artisan kunjungan:kosongkan` (angka dari uji coba lokal ikut tercatat)
- [ ] menu **Halaman Publik**: kedua saklar pengumuman **mati**, lalu isi judul, jadwal, dan kontak instansi
- [ ] `.env` **tidak** ikut dikirim di dalam arsip kode — buat baru di server tujuan
- [ ] document root web server diarahkan ke folder `public/`, bukan root project
- [ ] `php artisan config:cache route:cache view:cache` setelah `.env` final

### Menaikkan versi belakangan

Kalau nanti kode diperbaiki, instalasi instansi cukup ditimpa kodenya lalu:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan config:cache
```

Data mereka aman — migrasi hanya menambah struktur, tidak menghapus isi. Skema keduanya tetap sama
karena sumber kebenarannya satu: folder `database/migrations`.

## Test

```bash
php artisan test
```

- `tests/Feature/AdminSmokeTest.php` — redirect auth, login pakai username (benar & sandi salah),
  **sapuan otomatis semua rute GET panel** (halaman baru ikut teruji tanpa menambah test), export CSV,
  perhitungan nilai (lengkap & draft), validasi skor, alur import CSV, CRUD indikator rubrik,
  ubah status pendaftar, dan pintu darurat `/setup-admin` (404 saat token kosong / salah).
- `tests/Feature/ReviewerTest.php` — peran reviewer: menu & rute yang boleh/dilarang (termasuk
  **sapuan semua rute GET** — hanya menu konfigurasi yang boleh 403), daftar penilaian hanya berisi
  tugasnya, penugasan oleh admin.
- `tests/Feature/PengaturanPenilaianTest.php` — skala nilai, rumus normalisasi vs mentah, validasi skor
  di luar skala, gating "hanya peserta Terverifikasi", serta **hitung ulang nilai tersimpan** saat rumus
  diganti / indikator baru ditambah.
- `tests/Feature/PublikTest.php` — halaman publik: gerbang pengumuman (hasil tidak bocor sebelum
  panitia menyalakannya), hanya peserta lolos yang tampil, email/WA/nilai/catatan juri tidak pernah
  keluar (termasuk lewat pencarian), saklar situs, hak akses pengaturan, dan pencatatan kunjungan
  (robot & panitia yang login tidak dihitung, IP mentah tidak disimpan).
- `tests/Feature/VerifikasiAdministrasiTest.php` — butir Juklak, hak akses admin/reviewer, hasil Lolos/Tidak
  Lolos memasang status pendaftar, draft, kelola butir checklist, dan alur lolos administrasi -> masuk Penilaian.

## Struktur singkat

- `app/Http/Controllers/Admin/*` — controller tiap modul
- `app/Services/PendaftarImporter.php` — pemetaan kolom Google Form → tabel
- `app/Http/Controllers/Publik/PublikController.php` — halaman publik + batas kolom aman
- `app/Http/Middleware/CatatKunjungan.php` — pencatat kunjungan halaman publik
- `app/Models/PengaturanSitus.php` — isi & gerbang pengumuman halaman publik (satu baris)
- `app/Services/StatistikPengunjung.php` — statistik pengunjung dari tabel `kunjungan`
- `app/Support/PerhitunganNilai.php` — perhitungan nilai berbobot (skala & rumus dari pengaturan)
- `app/Models/PengaturanPenilaian.php` — skala nilai, rumus, gating (satu baris di tabel `pengaturan_penilaian`)
- `app/Support/Csv.php` — helper export CSV
- `database/seeders/DatabaseSeeder.php` — master data + rubrik default + data contoh
- `resources/views/layouts/admin.blade.php` — kerangka sidebar
