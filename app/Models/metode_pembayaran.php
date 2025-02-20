<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class metode_pembayaran extends Model
{
    use HasFactory;
    protected $table = 'metode_pembayaran';
    protected $primaryKey = 'id_metode';
    protected $fillable = [
        'kategori',
        'nama',
        'nomor_rekening',
        'qr_code',
        'logo'
    ];

    protected $casts = [
        'kategori' => 'string',
    ];
}
