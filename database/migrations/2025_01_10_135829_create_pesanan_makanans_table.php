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
        Schema::create('pesanan_makanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->foreignId('id_penyewa')->constrained('penyewa', 'id_penyewa');
            $table->foreignId('id_makanan')->constrained('katalog_makanan', 'id_makanan');
            $table->integer('jumlah');
            $table->decimal('total_harga', 10, 2);
            $table->enum('status_pesanan', ['pending', 'diproses', 'selesai']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan_makanan');
    }
};
