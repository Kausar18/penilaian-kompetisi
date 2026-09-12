<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Butir checklist Form Seleksi Administrasi. */
class ItemVerifikasi extends Model
{
    protected $table = 'item_verifikasi';

    protected $guarded = ['id'];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function detail(): HasMany
    {
        return $this->hasMany(DetailVerifikasi::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /** Urutan tampil: kelompok dulu, lalu urutan di dalam kelompok. */
    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('kelompok')->orderBy('urutan')->orderBy('id');
    }
}
