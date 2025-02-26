<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Katalog_Makanan extends Model
{
    protected $table = 'katalog_makanan';
    protected $primaryKey = 'id_makanan';

    protected $fillable = [
        'nama_makanan',
        'harga',
        'kategori',
        'deskripsi',
        'foto_makanan',
        'stock',
        'status'
    ];

    protected static function boot()
{
    parent::boot();

    static::saving(function ($model) {
        if ($model->stock <= 0) {
            $model->status = 'tidak_tersedia';
        }
    });
}

    // Jika diperlukan, tambahkan relasi dengan tabel pesanan
    public function pesanan()
    {
        return $this->hasMany(PesananMakanan::class, 'id_makanan', 'id_makanan');
    }
}
