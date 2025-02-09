<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peraturan_Kos extends Model
{
    use HasFactory;

    protected $table = 'peraturan_kos';
    protected $primaryKey = 'id_peraturan';

    protected $fillable = [
        'isi_peraturan',
        'tanggal_dibuat'
    ];

    protected $casts = [
        'tanggal_dibuat' => 'date'
    ];
}
