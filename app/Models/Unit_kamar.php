<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit_Kamar extends Model
{
    use HasFactory;

    protected $table = 'unit_kamar';
    protected $primaryKey = 'id_unit';

    protected $fillable = [
        'id_kamar',      // Tambahkan id_kamar ke fillable
        'nomor_kamar',
        'status'
    ];

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'id_kamar', 'id_kamar');
    }

    public function penyewa()
    {
        return $this->hasOne(Penyewa::class, 'id_unit', 'id_unit');
    }
}