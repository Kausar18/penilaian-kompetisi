<?php

namespace App\Http\Requests\Admin;

use App\Models\PengaturanPenilaian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Skor yang boleh diisi mengikuti skala di menu Rubrik -> Pengaturan Penilaian.
        $daftarNilai = PengaturanPenilaian::aktif()->daftarNilai();

        return [
            'skor' => ['array'],
            'skor.*' => ['nullable', 'integer', Rule::in($daftarNilai)],
            'rekomendasi' => ['nullable', Rule::in(['lolos', 'tidak_lolos'])],
            'catatan_reviewer' => ['nullable', 'string', 'max:5000'],

            // bagian "Catatan Verifikasi RAB" pada form penilaian substansi
            'catatan_rab' => ['nullable', 'string', 'max:5000'],
            'rekomendasi_anggaran' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'skor.*' => 'skor indikator',
            'catatan_reviewer' => 'kesimpulan',
            'catatan_rab' => 'komentar verifikasi RAB',
            'rekomendasi_anggaran' => 'rekomendasi anggaran',
        ];
    }
}
