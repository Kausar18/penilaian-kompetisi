@extends('layouts.admin')

@section('judul', 'Halaman Publik')
@section('subjudul', 'Isi beranda, jadwal, dan kendali kapan hasil seleksi boleh dilihat umum')

@section('aksi')
    <a href="{{ route('publik.beranda') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-box-arrow-up-right me-1"></i> Lihat halaman publik
    </a>
@endsection

@push('gaya')
<style>
    .saklar-kartu {
        border: 1px solid var(--garis); border-radius: .8rem; padding: 1.15rem 1.25rem;
        height: 100%; background: #fff; transition: border-color .16s ease, background .16s ease;
    }
    .saklar-kartu.menyala { border-color: #86C7A6; background: #F2FBF6; }
    .saklar-kartu .kepala { display: flex; align-items: flex-start; gap: .75rem; }
    .saklar-kartu .judul { font-weight: 700; font-size: .98rem; margin: 0; }
    .saklar-kartu .ket { color: var(--redup); font-size: .84rem; margin: .35rem 0 0; line-height: 1.55; }
    .saklar-kartu .hitung {
        display: inline-flex; align-items: center; gap: .4rem; margin-top: .85rem;
        font-size: .82rem; font-weight: 600; padding: .3rem .7rem; border-radius: 1rem;
        background: var(--biru-muda); color: var(--biru);
    }
    .form-switch .form-check-input { width: 2.6rem; height: 1.35rem; cursor: pointer; }
    .form-switch .form-check-input:checked { background-color: #1E9E63; border-color: #1E9E63; }

    .baris-jadwal {
        display: grid; grid-template-columns: 1.6fr 1fr auto auto; gap: .5rem;
        align-items: center; margin-bottom: .5rem;
    }
    .baris-jadwal .cek { display: flex; align-items: center; gap: .35rem; font-size: .8rem; color: var(--redup); white-space: nowrap; }
    @media (max-width: 768px) {
        .baris-jadwal { grid-template-columns: 1fr; padding: .75rem; border: 1px solid var(--garis); border-radius: .6rem; }
    }
    /* .btn-ikon didefinisikan lokal di tiap halaman yang memakainya (bukan kelas global) */
    .btn-ikon {
        border: 1px solid var(--garis); background: #fff; color: var(--redup);
        width: 32px; height: 32px; border-radius: .5rem; display: inline-grid; place-items: center;
        transition: all .14s ease;
    }
    .btn-ikon:hover { color: var(--merah); border-color: #F1C7C2; background: var(--merah-muda); }

    .peringatan-bocor {
        background: #FFF7ED; border: 1px solid #FDBA74; color: #9A3412;
        border-radius: .6rem; padding: .8rem 1rem; font-size: .86rem; line-height: 1.6;
    }
</style>
@endpush

@section('konten')

<form method="POST" action="{{ route('admin.situs.update') }}">
    @csrf @method('PUT')

    {{-- ============ GERBANG PENGUMUMAN ============ --}}
    <div class="panel p-4 mb-4">
        <div class="kartu-judul"><i class="bi bi-megaphone"></i> Gerbang Pengumuman</div>

        <div class="peringatan-bocor mb-3">
            <i class="bi bi-shield-exclamation me-1"></i>
            Hasil verifikasi <strong>tidak</strong> tayang otomatis. Selama saklar di bawah masih mati,
            publik tidak bisa melihat siapa pun yang lolos — walaupun reviewer sudah menekan "Lolos" di panel.
            Nyalakan hanya saat panitia benar-benar siap mengumumkan.
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="saklar-kartu {{ $situs->umumkan_administrasi ? 'menyala' : '' }}" id="kartuAdm">
                    <div class="kepala">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="umumkan_administrasi" name="umumkan_administrasi" value="1"
                                   @checked($situs->umumkan_administrasi)
                                   onchange="document.getElementById('kartuAdm').classList.toggle('menyala', this.checked)">
                        </div>
                        <div>
                            <label class="judul" for="umumkan_administrasi">Umumkan hasil administrasi</label>
                            <p class="ket">
                                Menampilkan daftar peserta yang lolos verifikasi administrasi di halaman publik.
                                Peserta yang <em>tidak</em> lolos tidak pernah ditampilkan.
                            </p>
                            <span class="hitung">
                                <i class="bi bi-people"></i>
                                {{ $pratinjau['lolos_administrasi'] }} tim akan terlihat publik
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="saklar-kartu {{ $situs->umumkan_finalis ? 'menyala' : '' }}" id="kartuFin">
                    <div class="kepala">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="umumkan_finalis" name="umumkan_finalis" value="1"
                                   @checked($situs->umumkan_finalis)
                                   onchange="document.getElementById('kartuFin').classList.toggle('menyala', this.checked)">
                        </div>
                        <div>
                            <label class="judul" for="umumkan_finalis">Umumkan finalis</label>
                            <p class="ket">
                                Menampilkan peserta yang penilaian pitching-nya selesai dengan rekomendasi Lolos.
                                <strong>Nilai dan catatan juri tetap tidak ditampilkan.</strong>
                            </p>
                            <span class="hitung">
                                <i class="bi bi-trophy"></i>
                                {{ $pratinjau['finalis'] }} tim akan terlihat publik
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <label class="label-filter d-block" for="catatan_pengumuman">Catatan di halaman pengumuman (opsional)</label>
            <textarea class="form-control form-control-sm" id="catatan_pengumuman" name="catatan_pengumuman" rows="2"
                      placeholder="Contoh: Peserta yang lolos wajib konfirmasi kehadiran paling lambat 20 Oktober.">{{ old('catatan_pengumuman', $situs->catatan_pengumuman) }}</textarea>
        </div>
    </div>

    {{-- ============ IDENTITAS SITUS ============ --}}
    <div class="panel p-4 mb-4">
        <div class="kartu-judul"><i class="bi bi-window"></i> Identitas Halaman</div>

        {{-- saklar induk: sengaja dibuat menonjol karena mematikannya membuat
             SELURUH halaman publik berubah jadi "segera hadir" --}}
        <div class="saklar-kartu mb-3 {{ $situs->situs_aktif ? 'menyala' : '' }}" id="kartuSitus">
            <div class="kepala">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="situs_aktif" name="situs_aktif" value="1"
                           @checked($situs->situs_aktif)
                           onchange="document.getElementById('kartuSitus').classList.toggle('menyala', this.checked)">
                </div>
                <div>
                    <label class="judul" for="situs_aktif">Halaman publik aktif</label>
                    <p class="ket mb-0">
                        Kalau dimatikan, <strong>seluruh</strong> halaman publik (beranda &amp; pengumuman)
                        diganti halaman "segera hadir". Panel panitia tidak terpengaruh.
                    </p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="label-filter d-block" for="judul">Judul kompetisi</label>
                <input type="text" class="form-control form-control-sm @error('judul') is-invalid @enderror"
                       id="judul" name="judul" value="{{ old('judul', $situs->judul) }}" required>
                @error('judul') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="label-filter d-block" for="subjudul">Subjudul (kalimat pembuka di hero)</label>
                <input type="text" class="form-control form-control-sm" id="subjudul" name="subjudul"
                       value="{{ old('subjudul', $situs->subjudul) }}">
            </div>

            <div class="col-12">
                <label class="label-filter d-block" for="penyelenggara">Penyelenggara</label>
                <input type="text" class="form-control form-control-sm" id="penyelenggara" name="penyelenggara"
                       value="{{ old('penyelenggara', $situs->penyelenggara) }}">
            </div>

            <div class="col-12">
                <label class="label-filter d-block" for="deskripsi">Deskripsi program (tampil di bagian Alur Seleksi)</label>
                <textarea class="form-control form-control-sm" id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $situs->deskripsi) }}</textarea>
            </div>

            <div class="col-12">
                <label class="d-flex align-items-center gap-2 small mb-0">
                    <input type="checkbox" name="tampilkan_statistik" value="1" @checked($situs->tampilkan_statistik)>
                    Tampilkan kartu angka di beranda (jumlah pendaftar &amp; bidang)
                </label>
            </div>
        </div>
    </div>

    {{-- ============ JADWAL ============ --}}
    <div class="panel p-4 mb-4">
        <div class="kartu-judul"><i class="bi bi-calendar3"></i> Jadwal Kegiatan</div>
        <p class="small mb-3" style="color: var(--redup);">
            Baris yang tahapnya dikosongkan akan dihapus saat disimpan. Tanggal ditulis bebas
            (contoh: <em>1–20 Oktober 2026</em>). Centang "selesai" untuk menandai tahap yang sudah lewat.
        </p>

        <div id="daftarJadwal">
            @php $barisJadwal = old('timeline', $timeline ?: [['tahap' => '', 'tanggal' => '', 'selesai' => false]]); @endphp

            @foreach ($barisJadwal as $i => $t)
                <div class="baris-jadwal">
                    <input type="text" class="form-control form-control-sm" name="timeline[{{ $i }}][tahap]"
                           value="{{ $t['tahap'] ?? '' }}" placeholder="Nama tahap">
                    <input type="text" class="form-control form-control-sm" name="timeline[{{ $i }}][tanggal]"
                           value="{{ $t['tanggal'] ?? '' }}" placeholder="Tanggal">
                    <label class="cek">
                        <input type="checkbox" name="timeline[{{ $i }}][selesai]" value="1" @checked($t['selesai'] ?? false)>
                        selesai
                    </label>
                    <button type="button" class="btn-ikon" title="Hapus baris"
                            onclick="this.closest('.baris-jadwal').remove()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="tambahJadwal">
            <i class="bi bi-plus-lg me-1"></i> Tambah baris
        </button>

        <hr class="my-3">

        <label class="d-flex align-items-start gap-2 small mb-0">
            <input type="checkbox" name="tampilkan_tanggal_jadwal" value="1" class="mt-1"
                   @checked($situs->tampilkan_tanggal_jadwal)>
            <span>
                <strong>Tampilkan tanggal di halaman publik</strong><br>
                <span style="color: var(--redup);">
                    Kalau dimatikan, pengunjung hanya melihat urutan nama tahap &mdash; tanggal di atas
                    tetap tersimpan dan bisa ditayangkan kapan saja. Berguna selagi jadwalnya masih tentatif.
                </span>
            </span>
        </label>
    </div>

    {{-- ============ KONTAK ============ --}}
    <div class="panel p-4 mb-4">
        <div class="kartu-judul"><i class="bi bi-telephone"></i> Kontak (tampil di footer)</div>

        <div class="row g-3">
            <div class="col-lg-6">
                <label class="label-filter d-block" for="kontak_email">Email panitia</label>
                <input type="email" class="form-control form-control-sm @error('kontak_email') is-invalid @enderror"
                       id="kontak_email" name="kontak_email" value="{{ old('kontak_email', $situs->kontak_email) }}">
                @error('kontak_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-lg-6">
                <label class="label-filter d-block" for="kontak_wa">Nomor WhatsApp</label>
                <input type="text" class="form-control form-control-sm" id="kontak_wa" name="kontak_wa"
                       value="{{ old('kontak_wa', $situs->kontak_wa) }}" placeholder="08xx-xxxx-xxxx">
            </div>
            <div class="col-lg-6">
                <label class="label-filter d-block" for="instagram">Instagram</label>
                <input type="text" class="form-control form-control-sm" id="instagram" name="instagram"
                       value="{{ old('instagram', $situs->instagram) }}" placeholder="@nama_akun">
            </div>
            <div class="col-lg-6">
                <label class="label-filter d-block" for="situs_lembaga">Situs lembaga</label>
                <input type="url" class="form-control form-control-sm @error('situs_lembaga') is-invalid @enderror"
                       id="situs_lembaga" name="situs_lembaga" value="{{ old('situs_lembaga', $situs->situs_lembaga) }}"
                       placeholder="https://...">
                @error('situs_lembaga') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button class="btn btn-utama"><i class="bi bi-check-lg me-1"></i> Simpan Pengaturan</button>
    </div>
</form>

@endsection

@push('skrip')
<script>
    // tambah baris jadwal; indeks lanjut dari baris terakhir supaya tidak bentrok
    let indeksJadwal = {{ count($barisJadwal) }};

    document.getElementById('tambahJadwal').addEventListener('click', () => {
        const wadah = document.getElementById('daftarJadwal');

        if (wadah.children.length >= 12) {
            return;     // batas sama dengan validasi di server
        }

        const baris = document.createElement('div');
        baris.className = 'baris-jadwal';
        baris.innerHTML = `
            <input type="text" class="form-control form-control-sm" name="timeline[${indeksJadwal}][tahap]" placeholder="Nama tahap">
            <input type="text" class="form-control form-control-sm" name="timeline[${indeksJadwal}][tanggal]" placeholder="Tanggal">
            <label class="cek"><input type="checkbox" name="timeline[${indeksJadwal}][selesai]" value="1"> selesai</label>
            <button type="button" class="btn-ikon" title="Hapus baris"><i class="bi bi-x-lg"></i></button>`;

        baris.querySelector('button').addEventListener('click', () => baris.remove());
        wadah.appendChild(baris);
        indeksJadwal++;
    });
</script>
@endpush
