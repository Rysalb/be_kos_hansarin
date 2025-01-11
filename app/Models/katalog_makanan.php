<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KatalogMakanan extends Model
{
    protected $table = 'katalog_makanan';
    protected $primaryKey = 'id_makanan';

    protected $fillable = [
        'nama_makanan',
        'harga',
        'kategori',
        'deskripsi',
        'foto_makanan',
        'status'
    ];

    // Jika diperlukan, tambahkan relasi dengan tabel pesanan
    public function pesanan()
    {
        return $this->hasMany(PesananMakanan::class, 'id_makanan', 'id_makanan');
    }
}
