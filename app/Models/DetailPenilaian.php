<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenilaian extends Model
{
    protected $table = 'detail_penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'skor' => 'integer',
    ];

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(IndikatorPenilaian::class, 'indikator_penilaian_id');
    }

    // Perhitungan nilai tertimbang ada di App\Support\PerhitunganNilai
    // (satu sumber kebenaran, mengikuti skala & rumus di PengaturanPenilaian).
}
