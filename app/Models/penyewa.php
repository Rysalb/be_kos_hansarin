<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    protected $table = 'penyewa';
    protected $primaryKey = 'id_penyewa';

    protected $fillable = [
        'id_user',
        'id_kamar',
        'nik',
        'foto_ktp',
        'alamat_asal',
        'tanggal_masuk',
        'durasi_sewa',
        'tanggal_keluar',
        'status_penyewa',
        'nomor_wa'
    ];

    // Relasi dengan model User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    // // Relasi dengan model Kamar
    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'id_kamar', 'id_kamar');
    }
}
