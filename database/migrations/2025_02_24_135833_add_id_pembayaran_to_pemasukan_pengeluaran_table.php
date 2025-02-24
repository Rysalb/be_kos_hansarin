<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pemasukan_pengeluaran', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pembayaran')->nullable()->after('id_penyewa');
            $table->foreign('id_pembayaran')
                  ->references('id_pembayaran')
                  ->on('pembayaran')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pemasukan_pengeluaran', function (Blueprint $table) {
            $table->dropForeign(['id_pembayaran']);
            $table->dropColumn('id_pembayaran');
        });
    }
};
