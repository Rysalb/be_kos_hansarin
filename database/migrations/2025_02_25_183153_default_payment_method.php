<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add default payment method
        DB::table('metode_pembayaran')->insert([
            'id_metode' => 1,
            'kategori' => 'otomatis',
            'nama' => 'Dibayar',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        DB::table('metode_pembayaran')->where('id_metode', 1)->delete();
    }
};
