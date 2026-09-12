<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndikatorPenilaian extends Model
{
    protected $table = 'indikator_penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'bobot' => 'decimal:2',
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function kategoriPenilaian(): BelongsTo
    {
        return $this->belongsTo(KategoriPenilaian::class);
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPenilaian::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
