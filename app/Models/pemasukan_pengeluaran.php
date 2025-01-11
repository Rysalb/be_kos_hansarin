<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemasukanPengeluaran extends Model
{
    protected $table = 'pemasukan_pengeluaran';
    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'jenis_transaksi',
        'kategori',
        'tanggal',
        'jumlah',
        'keterangan',
        'bukti_transaksi',
        'bulan',
        'tahun',
        'saldo'
    ];

    // Menghitung saldo setelah transaksi
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaksi) {
            $lastTransaction = static::latest()->first();
            $lastSaldo = $lastTransaction ? $lastTransaction->saldo : 0;

            if ($transaksi->jenis_transaksi === 'pemasukan') {
                $transaksi->saldo = $lastSaldo + $transaksi->jumlah;
            } else {
                $transaksi->saldo = $lastSaldo - $transaksi->jumlah;
            }

            $transaksi->bulan = date('n', strtotime($transaksi->tanggal));
            $transaksi->tahun = date('Y', strtotime($transaksi->tanggal));
        });
    }
}
