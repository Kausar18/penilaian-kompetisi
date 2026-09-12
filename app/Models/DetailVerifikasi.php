<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Centang + catatan satu butir checklist administrasi. */
class DetailVerifikasi extends Model
{
    protected $table = 'detail_verifikasi';

    protected $guarded = ['id'];

    public const STATUS = [
        'sesuai' => 'Sesuai',
        'tidak_sesuai' => 'Tidak Sesuai',
        'na' => 'N/A',
    ];

    public function verifikasi(): BelongsTo
    {
        return $this->belongsTo(VerifikasiAdministrasi::class, 'verifikasi_administrasi_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemVerifikasi::class, 'item_verifikasi_id');
    }
}
