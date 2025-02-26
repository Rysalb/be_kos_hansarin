<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_penyewa',
        'id_user',
        'id_metode',
        'tanggal_pembayaran',
        'jumlah_pembayaran',
        'bukti_pembayaran',
        'status_verifikasi',
        'keterangan'
    ];

    protected $attributes = [
        'bukti_pembayaran' => 'manual_entry.jpg' // Set default value
    ];

    // Relasi dengan model Penyewa
    public function penyewa()
    {
        return $this->belongsTo(Penyewa::class, 'id_penyewa', 'id_penyewa');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function metodePembayaran()
    {
        return $this->belongsTo(metode_pembayaran::class, 'id_metode', 'id_metode');
    }
}