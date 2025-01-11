<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomorPenting extends Model
{
    protected $table = 'nomor_penting';
    protected $primaryKey = 'id_nomor';

    protected $fillable = [
        'nama_kontak',
        'nomor_telepon',
        'kategori',
        'keterangan'
    ];
}
