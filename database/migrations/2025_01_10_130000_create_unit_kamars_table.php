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
        Schema::create('unit_kamar', function (Blueprint $table) {
            $table->id('id_unit');
            $table->foreignId('id_kamar')->constrained('kamar', 'id_kamar')->onDelete('cascade');
            $table->string('nomor_kamar');
            $table->enum('status', ['tersedia', 'dihuni'])->default('tersedia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_kamar');
    }
};
