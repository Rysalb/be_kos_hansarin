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
        Schema::create('penyewa', function (Blueprint $table) {
            $table->id('id_penyewa');
            $table->foreignId('id_user')->constrained('users', 'id_user');
            $table->foreignId('id_unit')->constrained('unit_kamar', 'id_unit');
            $table->string('nik', 16);
            $table->string('foto_ktp');
            $table->text('alamat_asal');
            $table->date('tanggal_masuk');
            $table->integer('durasi_sewa');
            $table->string('nomor_wa');
            $table->date('tanggal_keluar');
            $table->enum('status_penyewa', ['aktif', 'tidak_aktif']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyewa');
    }
};
