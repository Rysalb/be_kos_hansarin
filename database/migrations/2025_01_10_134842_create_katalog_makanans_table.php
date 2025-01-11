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
        Schema::create('katalog_makanan', function (Blueprint $table) {
            $table->id('id_makanan');
            $table->string('nama_makanan', 100);
            $table->decimal('harga', 10, 2);
            $table->string('kategori');
            $table->text('deskripsi')->nullable();
            $table->string('foto_makanan');
            $table->enum('status', ['tersedia', 'tidak_tersedia']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('katalog_makanan');
    }
};
