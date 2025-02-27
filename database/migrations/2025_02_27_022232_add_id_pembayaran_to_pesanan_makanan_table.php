<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pesanan_makanan', function (Blueprint $table) {
            $table->foreignId('id_pembayaran')
                  ->nullable()
                  ->after('id_penyewa')
                  ->constrained('pembayaran', 'id_pembayaran')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pesanan_makanan', function (Blueprint $table) {
            $table->dropForeign(['id_pembayaran']);
            $table->dropColumn('id_pembayaran');
        });
    }
};
