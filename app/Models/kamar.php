<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    use HasFactory;

    // Nama tabel yang digunakan oleh model
    protected $table = 'kamar';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'nomor_kamar',
        'tipe_kamar',
        'status',
        'harga_sewa',
        'harga_sewa1',
        'harga_sewa2',
        'harga_sewa3',
        'harga_sewa4',
        'jumlah_unit',
        'fasilitas',
        'deskripsi',
    ];

    // Jika Anda ingin menambahkan relasi, Anda bisa menambahkannya di sini
    // Contoh relasi dengan model Penyewa
    // public function penyewa()
    // {
    //     return $this->hasOne(Penyewa::class, 'id_kamar', 'id_kamar');
    // }
}