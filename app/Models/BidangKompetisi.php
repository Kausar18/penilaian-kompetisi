<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidangKompetisi extends Model
{
    protected $table = 'bidang_kompetisi';

    protected $guarded = ['id'];

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Pendaftar::class);
    }
}
