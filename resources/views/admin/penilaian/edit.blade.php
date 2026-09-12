@extends('layouts.admin')

@section('judul', 'Edit Nilai')
@section('subjudul', $p->nama_tim . ' — ' . $p->nama_ketua)

@section('aksi')
    <a href="{{ route('admin.penilaian.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke daftar
    </a>
@endsection

@push('gaya')
<style>
    .seksi-ikon {
        width: 34px; height: 34px; flex: 0 0 34px;
        border-radius: 50%;
        display: grid; place-items: center;
        background: var(--biru-muda);
        color: var(--biru);
        font-size: 1rem;
    }
    .seksi-judul {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1rem; font-weight: 800; color: var(--navy);
        letter-spacing: -.01em;
        margin: 0 0 1.1rem;
    }
    .info-list { display: flex; flex-direction: column; gap: .9rem; }
    .info-item { display: flex; flex-direction: column; gap: .12rem; }
    .info-label { font-size: .72rem; color: var(--redup); }
    .info-value { font-size: .92rem; color: #101828; font-weight: 600; line-height: 1.4; }
    .info-teks { font-size: .86rem; line-height: 1.6; white-space: pre-line; margin-top: .1rem; }

    .anggota-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: .55rem 0; font-size: .88rem;
    }
    .anggota-item + .anggota-item { border-top: 1px solid var(--garis); }
    .anggota-nim { color: var(--redup); font-size: .82rem; }

    /* skor */
    .nilai-chip {
        text-align: right; background: #F5F8FD; border: 1px solid var(--garis);
        border-radius: .7rem; padding: .5rem .9rem;
    }
    .nilai-chip .besar { font-weight: 800; color: var(--navy); font-size: 1.15rem; line-height: 1; }
    .nilai-chip .kecil { font-size: .74rem; color: var(--redup); margin-top: .2rem; }

    .kelompok-bar {
        display: flex; justify-content: space-between; align-items: center;
        margin: 1.4rem 0 .4rem; padding-bottom: .45rem;
        border-bottom: 2px solid var(--biru-muda);
    }
    .kelompok-bar .nama { font-weight: 800; color: var(--navy); }
    .kelompok-bar .persen { font-size: .8rem; color: var(--redup); font-weight: 600; }
    .kelompok-bar .subtotal { font-size: .8rem; color: var(--redup); }

    .ind-baris {
        display: grid;
        grid-template-columns: 1fr 132px 66px 62px;
        gap: .7rem; align-items: center;
        padding: .8rem 0;
        border-bottom: 1px solid var(--garis);
    }
    .ind-baris:hover { background: #FBFCFE; }
    .ind-baris .nama { font-weight: 600; font-size: .86rem; }
    .ind-baris .desk { font-size: .78rem; color: var(--redup); line-height: 1.45; margin-top: .1rem; }
    .ind-mini { font-size: .68rem; color: var(--redup); text-transform: uppercase; letter-spacing: .04em; }
    .ind-nilai { font-weight: 800; color: var(--navy); }

    @media (max-width: 575px) {
        .ind-baris { grid-template-columns: 1fr auto; }
        .ind-baris .kolom-bobot { display: none; }
    }
</style>
@endpush

@section('konten')

@php
    $totalBobot = $kategori->flatMap->indikatorAktif->sum('bobot');
    $jmlIndikator = $kategori->flatMap->indikatorAktif->count();

    $skala = $pengaturan->skala ?? [];
    $maksNilai = $pengaturan->maksNilai();
    $rumusMentah = $pengaturan->rumus === 'mentah';
    $nilaiMaks = $pengaturan->nilaiMaksimum((float) $totalBobot);
    $rapi = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

<div class="row g-3">
    {{-- ============ KIRI: INFO PESERTA ============ --}}
    <div class="col-lg-5">
        <div class="panel p-4 mb-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h2 class="h6 mb-0">{{ $p->nama_tim }}</h2>
                <span class="tag">{{ $p->kategoriPeserta?->nama ?? '—' }}</span>
            </div>

            <div class="seksi-judul">
                <span class="seksi-ikon"><i class="bi bi-person"></i></span>
                Informasi Peserta
            </div>

            <div class="info-list">
                @foreach ([
                    'Nama Ketua' => $p->nama_ketua,
                    'Bidang' => $p->bidangKompetisi?->nama,
                    'Kota / Provinsi' => collect([$p->kota, $p->provinsi])->filter()->join(' / '),
                    'Asal Institusi' => $p->asal_institusi,
                    'Fakultas / Prodi' => $p->fakultas_prodi,
                ] as $label => $nilai)
                    <div class="info-item">
                        <span class="info-label">{{ $label }}</span>
                        <span class="info-value">{{ filled($nilai) ? $nilai : '—' }}</span>
                    </div>
                @endforeach
            </div>

            @if ($p->link_pitchdeck || $p->link_logo)
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    @if ($p->link_pitchdeck)
                        <a href="{{ $p->link_pitchdeck }}" target="_blank" rel="noopener" class="chip text-decoration-none"><i class="bi bi-file-earmark-slides"></i> Pitch Deck</a>
                    @endif
                    @if ($p->link_logo)
                        <a href="{{ $p->link_logo }}" target="_blank" rel="noopener" class="chip text-decoration-none"><i class="bi bi-image"></i> Logo</a>
                    @endif
                </div>
            @endif
        </div>

        <div class="panel p-4 mb-3">
            <div class="seksi-judul">
                <span class="seksi-ikon"><i class="bi bi-lightbulb"></i></span>
                Informasi Inovasi
            </div>
            @foreach ([
                'Judul Inovasi' => $p->judul_inovasi,
                'Deskripsi Singkat' => $p->deskripsi_singkat,
                'Permasalahan' => $p->permasalahan,
                'Solusi' => $p->solusi,
            ] as $label => $isi)
                <div class="mb-3">
                    <div class="info-label">{{ $label }}</div>
                    <div class="info-teks">{{ $isi ?: '—' }}</div>
                </div>
            @endforeach
        </div>

        <div class="panel p-4">
            <div class="seksi-judul">
                <span class="seksi-ikon"><i class="bi bi-people"></i></span>
                Anggota Tim
            </div>
            @forelse ($p->anggotaTim as $a)
                <div class="anggota-item">
                    <span>{{ $a->nama }}</span>
                    <span class="anggota-nim">{{ $a->nim ?? '—' }}</span>
                </div>
            @empty
                <p class="small mb-0" style="color: var(--redup);">Tidak ada data anggota tim.</p>
            @endforelse
        </div>
    </div>

    {{-- ============ KANAN: FORM PENILAIAN ============ --}}
    <div class="col-lg-7">
        <form method="POST" action="{{ route('admin.penilaian.update', $p) }}" id="form-nilai">
            @csrf @method('PUT')

            <div class="panel p-4 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-1">
                    <div class="seksi-judul mb-0">
                        <span class="seksi-ikon"><i class="bi bi-clipboard-check"></i></span>
                        <span>
                            Penilaian Berbobot
                            <div class="fw-normal small" style="color: var(--redup);">
                                Total per indikator = {{ $rumusMentah ? 'nilai × bobot' : '(nilai ÷ '.$maksNilai.') × bobot' }}
                            </div>
                        </span>
                    </div>
                    <div class="nilai-chip">
                        <div class="besar">Nilai <span id="nilaiFinal">0</span>/{{ $rapi($nilaiMaks) }}</div>
                        <div class="kecil">Mentah <span id="nilaiMentah">0</span>/{{ $jmlIndikator * $maksNilai }}</div>
                    </div>
                </div>

                @if (abs($totalBobot - 100) > 0.01)
                    <div class="banner-info mb-3">
                        <i class="bi bi-exclamation-triangle mt-1"></i>
                        <span>Total bobot indikator aktif = <strong>{{ rtrim(rtrim(number_format($totalBobot, 2), '0'), '.') }}</strong>, belum 100.
                            Atur di <a href="{{ route('admin.rubrik.index') }}">Rubrik Penilaian</a>.</span>
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="ind-mini">Keterangan nilai:</span>
                    @foreach ($skala as $tingkat)
                        <span class="tag tag-netral">{{ $tingkat['nilai'] }} = {{ $tingkat['label'] }}</span>
                    @endforeach
                </div>

                @forelse ($kategori as $kat)
                    <div class="kelompok-bar">
                        <div>
                            <span class="nama">{{ $kat->nama }}</span>
                            <span class="persen">· {{ $kat->bobot_persen }}%</span>
                        </div>
                        @php $maksKelompok = $rumusMentah ? $maksNilai * $kat->bobot_persen : $kat->bobot_persen; @endphp
                        <div class="subtotal">Subtotal <span class="fw-bold" data-subtotal="{{ $kat->id }}">0</span>/{{ $maksKelompok }}</div>
                    </div>

                    @forelse ($kat->indikatorAktif as $ind)
                        <div class="ind-baris">
                            <div>
                                <div class="nama">{{ $ind->nama }}</div>
                                @if ($ind->peran_khusus)
                                    <span class="tag tag-kuning" style="font-size:.65rem;">{{ $ind->peran_khusus }}</span>
                                @endif
                                <div class="desk">{{ $ind->deskripsi }}</div>
                            </div>
                            <div>
                                @php $tersimpan = $skorTersimpan[$ind->id] ?? null; @endphp
                                <select name="skor[{{ $ind->id }}]" class="form-select form-select-sm input-skor"
                                        data-bobot="{{ $ind->bobot }}" data-kat="{{ $kat->id }}">
                                    <option value="">–</option>
                                    @foreach ($skala as $tingkat)
                                        <option value="{{ $tingkat['nilai'] }}"
                                                @selected($tersimpan !== null && (int) $tersimpan === (int) $tingkat['nilai'])>
                                            {{ $tingkat['nilai'] }} — {{ $tingkat['label'] }}
                                        </option>
                                    @endforeach
                                    {{-- nilai lama di luar skala saat ini tetap ditampilkan supaya tidak hilang diam-diam --}}
                                    @if ($tersimpan !== null && ! in_array((int) $tersimpan, $pengaturan->daftarNilai(), true))
                                        <option value="{{ (int) $tersimpan }}" selected>{{ (int) $tersimpan }} — (skala lama)</option>
                                    @endif
                                </select>
                            </div>
                            <div class="text-center kolom-bobot">
                                <div class="ind-mini">Bobot</div>
                                <div class="fw-semibold small">{{ rtrim(rtrim(number_format($ind->bobot, 2), '0'), '.') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="ind-mini">Total</div>
                                <div class="ind-nilai" data-total="{{ $ind->id }}">0</div>
                            </div>
                        </div>
                    @empty
                        <p class="small" style="color: var(--redup);">Belum ada indikator aktif pada kelompok ini.</p>
                    @endforelse
                @empty
                    <div class="kotak-kosong">Rubrik penilaian belum dibuat. Buat di <a href="{{ route('admin.rubrik.index') }}">Rubrik Penilaian</a>.</div>
                @endforelse
            </div>

            {{-- Bagian "Catatan Verifikasi RAB" pada form penilaian substansi --}}
            <div class="panel p-4 mb-4">
                <div class="seksi-judul">
                    <span class="seksi-ikon"><i class="bi bi-cash-coin"></i></span>
                    Catatan Verifikasi RAB
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="catatan_rab" class="label-filter d-block">Komentar</label>
                        <textarea name="catatan_rab" id="catatan_rab" class="form-control" rows="4"
                                  placeholder="Catatan terhadap Rencana Anggaran Biaya yang diusulkan.">{{ old('catatan_rab', $penilaian->catatan_rab) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label for="rekomendasi_anggaran" class="label-filter d-block">Rekomendasi Anggaran</label>
                        <textarea name="rekomendasi_anggaran" id="rekomendasi_anggaran" class="form-control" rows="3"
                                  placeholder="Besaran / komponen anggaran yang direkomendasikan.">{{ old('rekomendasi_anggaran', $penilaian->rekomendasi_anggaran) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="panel p-4">
                <div class="seksi-judul">
                    <span class="seksi-ikon"><i class="bi bi-flag"></i></span>
                    Keputusan &amp; Kesimpulan
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="rekomendasi" class="label-filter d-block">Rekomendasi</label>
                        <select name="rekomendasi" id="rekomendasi" class="form-select">
                            <option value="">— belum diputuskan —</option>
                            @foreach (\App\Models\Penilaian::REKOMENDASI as $key => $label)
                                <option value="{{ $key }}" @selected($penilaian->rekomendasi === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="label-filter d-block">Ringkasan Bobot</label>
                        <div class="form-control bg-light" style="pointer-events: none;" id="ringkasanBobot">—</div>
                    </div>
                    <div class="col-12">
                        <label for="catatan_reviewer" class="label-filter d-block">Kesimpulan &mdash; Komentar</label>
                        <textarea name="catatan_reviewer" id="catatan_reviewer" class="form-control" rows="4"
                                  placeholder="Kesimpulan penilaian terhadap usulan ini.">{{ old('catatan_reviewer', $penilaian->catatan_reviewer) }}</textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.penilaian.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                        <button type="submit" class="btn btn-utama btn-sm px-4">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('skrip')
<script>
(function () {
    const form = document.getElementById('form-nilai');
    if (!form) return;

    const inputs = form.querySelectorAll('.input-skor');
    const fmt = (n) => (Math.round(n * 100) / 100).toString();
    const maksNilai = {{ $maksNilai }};
    const pakaiMentah = @json($rumusMentah);
    const nilaiMaks = '{{ $rapi($nilaiMaks) }}';

    function hitung() {
        const subtotal = {};
        let final = 0, jumlahSkor = 0;

        inputs.forEach((sel) => {
            const bobot = parseFloat(sel.dataset.bobot) || 0;
            const kat = sel.dataset.kat;
            const val = sel.value === '' ? null : parseInt(sel.value, 10);
            const total = val === null ? 0 : (pakaiMentah ? val * bobot : (val / maksNilai) * bobot);

            const selId = sel.name.match(/\[(\d+)\]/)[1];
            const cell = form.querySelector(`[data-total="${selId}"]`);
            if (cell) cell.textContent = fmt(total);

            subtotal[kat] = (subtotal[kat] || 0) + total;
            if (val !== null) { final += total; jumlahSkor += val; }
        });

        Object.keys(subtotal).forEach((kat) => {
            const cell = form.querySelector(`[data-subtotal="${kat}"]`);
            if (cell) cell.textContent = fmt(subtotal[kat]);
        });

        document.getElementById('nilaiFinal').textContent = fmt(final);
        document.getElementById('nilaiMentah').textContent = jumlahSkor;
        document.getElementById('ringkasanBobot').textContent = 'Nilai akhir ' + fmt(final) + ' / ' + nilaiMaks;
    }

    inputs.forEach((sel) => sel.addEventListener('change', hitung));
    hitung();
})();
</script>
@endpush
