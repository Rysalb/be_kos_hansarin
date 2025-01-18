<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori_Kamar extends Model
{
    use HasFactory;
    
    protected $table = 'kategori_kamar';
    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'id_kamar',
        'jumlah_unit',
        'unit_tersedia',
        'unit_terisi'
    ];

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'id_kamar', 'id_kamar');
    }
}
