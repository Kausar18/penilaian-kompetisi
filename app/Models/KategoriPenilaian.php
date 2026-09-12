<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPenilaian extends Model
{
    protected $table = 'kategori_penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'bobot_persen' => 'integer',
        'urutan' => 'integer',
    ];

    public function indikator(): HasMany
    {
        return $this->hasMany(IndikatorPenilaian::class)->orderBy('urutan')->orderBy('id');
    }

    public function indikatorAktif(): HasMany
    {
        return $this->indikator()->where('aktif', true);
    }

    /** Total bobot indikator aktif dalam kelompok ini. */
    public function getTotalBobotAttribute(): float
    {
        return (float) $this->indikatorAktif()->sum('bobot');
    }
}
