<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pemasukan_pengeluaran', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->enum('jenis_transaksi', ['pemasukan', 'pengeluaran']);
            $table->string('kategori');
            $table->date('tanggal');
            $table->decimal('jumlah', 10, 2);
            $table->text('keterangan')->nullable();
            $table->string('bukti_transaksi')->nullable();
            $table->integer('bulan');
            $table->integer('tahun');
            $table->decimal('saldo', 10, 2);
            $table->timestamps();
        });

        Schema::table('pemasukan_pengeluaran', function (Blueprint $table) {
            $table->unsignedBigInteger('id_penyewa')->nullable();
            $table->foreign('id_penyewa')->references('id_penyewa')->on('penyewa')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemasukan_pengeluarans');

        Schema::table('pemasukan_pengeluaran', function (Blueprint $table) {
            $table->dropForeign(['id_penyewa']);
            $table->dropColumn('id_penyewa');
        });
    }
};
