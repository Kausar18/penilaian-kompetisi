<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Hasil verifikasi administrasi satu pendaftar (tahap 1). */
class VerifikasiAdministrasi extends Model
{
    protected $table = 'verifikasi_administrasi';

    protected $guarded = ['id'];

    protected $casts = [
        'diverifikasi_at' => 'datetime',
    ];

    public const HASIL = [
        'lolos' => 'Lolos',
        'tidak_lolos' => 'Tidak Lolos',
    ];

    /** Status pendaftar yang dipasang saat hasil diputuskan. */
    public const STATUS_PENDAFTAR = [
        'lolos' => 'verified',
        'tidak_lolos' => 'rejected',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailVerifikasi::class);
    }

    public function getHasilLabelAttribute(): ?string
    {
        return $this->hasil ? (self::HASIL[$this->hasil] ?? $this->hasil) : null;
    }
}
