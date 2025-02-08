<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class metode_pembayaran extends Model
{
    use HasFactory;

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
