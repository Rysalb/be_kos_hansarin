<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan_Makanan extends Model
{
    protected $table = 'pesanan_makanan';
    protected $primaryKey = 'id_pesanan';

    protected $fillable = [
        'id_penyewa',
        'id_makanan',
        'id_pembayaran',
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
        return $this->belongsTo(Katalog_Makanan::class, 'id_makanan', 'id_makanan');
    }
    public function katalogMakanan()
    {
        return $this->belongsTo(Katalog_Makanan::class, 'id_makanan', 'id_makanan');
    }

    public function pembayaran()
    {
        return $this->belongsTo(Pembayaran::class, 'id_pembayaran', 'id_pembayaran');
    }
}
