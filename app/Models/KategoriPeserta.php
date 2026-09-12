<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPeserta extends Model
{
    protected $table = 'kategori_peserta';

    protected $guarded = ['id'];

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Pendaftar::class);
    }
}
