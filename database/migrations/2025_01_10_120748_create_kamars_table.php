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
        Schema::create('kamar', function (Blueprint $table) {
            $table->id('id_kamar'); // Primary key
            $table->string('nomor_kamar', 10);
            $table->string('tipe_kamar');
            $table->integer('jumlah_unit');
            $table->enum('status', ['tersedia', 'terisi'])->nullable();
            $table->decimal('harga_sewa', 15, 2);
            $table->decimal('harga_sewa1', 15, 2)->nullable();
            $table->decimal('harga_sewa2', 15, 2)->nullable();
            $table->decimal('harga_sewa3', 15, 2)->nullable();
            $table->decimal('harga_sewa4', 15, 2)->nullable(); 
            $table->timestamps();// created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar');
    }
};
