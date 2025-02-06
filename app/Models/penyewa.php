<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewa extends Model
{
    protected $table = 'penyewa';
    protected $primaryKey = 'id_penyewa';

    protected $fillable = [
        'id_user',
        'id_unit',
        'nik',
        'foto_ktp',
        'alamat_asal',
        'tanggal_masuk',
        'durasi_sewa',
        'tanggal_keluar',
        'status_penyewa',
        'nomor_wa',
        'harga_sewa' // Tambahkan field ini
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function unit_kamar()
    {
        return $this->belongsTo(Unit_Kamar::class, 'id_unit', 'id_unit');
    }
}
