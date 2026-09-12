<?php

namespace App\Models;

use Database\Factories\PendaftarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftar extends Model
{
    /** @use HasFactory<PendaftarFactory> */
    use HasFactory;

    protected $table = 'pendaftar';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_daftar' => 'date',
        'data_asli' => 'array',
    ];

    public const STATUS = [
        'submitted' => 'Submitted',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];

    // ==================================================================
    // RELASI
    // ==================================================================

    public function kategoriPeserta(): BelongsTo
    {
        return $this->belongsTo(KategoriPeserta::class);
    }

    public function bidangKompetisi(): BelongsTo
    {
        return $this->belongsTo(BidangKompetisi::class);
    }

    /** Reviewer yang ditugaskan menilai pendaftar ini (Opsi A: satu reviewer). */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function anggotaTim(): HasMany
    {
        return $this->hasMany(AnggotaTim::class);
    }

    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class);
    }

    /** Hasil verifikasi administrasi (tahap 1). */
    public function verifikasi(): HasOne
    {
        return $this->hasOne(VerifikasiAdministrasi::class);
    }

    // ==================================================================
    // SCOPE PENCARIAN & FILTER
    // ==================================================================

    /** Pencarian bebas: nama ketua, email, nama tim, atau judul inovasi. */
    public function scopeCari(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('nama_ketua', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('nama_tim', 'like', "%{$keyword}%")
                ->orWhere('judul_inovasi', 'like', "%{$keyword}%");
        });
    }

    public function scopeKategori(Builder $query, $kategoriPesertaId): Builder
    {
        return blank($kategoriPesertaId) ? $query : $query->where('kategori_peserta_id', $kategoriPesertaId);
    }

    public function scopeBidang(Builder $query, $bidangKompetisiId): Builder
    {
        return blank($bidangKompetisiId) ? $query : $query->where('bidang_kompetisi_id', $bidangKompetisiId);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    /** Filter pendaftar yang ditugaskan ke reviewer tertentu. */
    public function scopeDitugaskanKe(Builder $query, $userId): Builder
    {
        return blank($userId) ? $query : $query->where('reviewer_id', $userId);
    }

    public function scopeProvinsi(Builder $query, ?string $provinsi): Builder
    {
        return blank($provinsi) ? $query : $query->where('provinsi', $provinsi);
    }
}
