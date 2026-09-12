<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $guarded = ['id'];

    protected $casts = [
        'nilai_final' => 'decimal:2',
        'nilai_mentah' => 'decimal:2',
        'dinilai_at' => 'datetime',
    ];

    public const REKOMENDASI = [
        'lolos' => 'Lolos',
        'tidak_lolos' => 'Tidak Lolos',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPenilaian::class);
    }

    /**
     * Status ringkas: 'selesai' | 'sebagian' | 'belum'.
     * 'selesai' bersandar pada dinilai_at yang di-set controller saat
     * semua indikator aktif + rekomendasi lengkap.
     */
    public function getStatusPenilaianAttribute(): string
    {
        if ($this->dinilai_at) {
            return 'selesai';
        }

        $adaSkor = $this->relationLoaded('detail')
            ? $this->detail->whereNotNull('skor')->isNotEmpty()
            : $this->detail()->whereNotNull('skor')->exists();

        return $adaSkor ? 'sebagian' : 'belum';
    }

    public function getRekomendasiLabelAttribute(): ?string
    {
        return $this->rekomendasi ? (self::REKOMENDASI[$this->rekomendasi] ?? $this->rekomendasi) : null;
    }
}
