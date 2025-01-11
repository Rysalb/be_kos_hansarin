<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananMakanan extends Model
{
    protected $table = 'pesanan_makanan';
    protected $primaryKey = 'id_pesanan';

    protected $fillable = [
        'id_penyewa',
        'id_makanan',
        'jumlah',
        'total_harga',
        'status_pesanan'
    ];

    // Relasi dengan model Penyewa
    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa', 'id_penyewa');
    }

    // Relasi dengan model KatalogMakanan
    public function makanan()
    {
        return $this->belongsTo(KatalogMakanan::class, 'id_makanan', 'id_makanan');
    }
}
