<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_user')->constrained('users', 'id_user'); // Foreign key ke users
            $table->foreignId('id_metode')->constrained('metode_pembayaran', 'id_metode'); // Foreign key ke metode_pembayaran
            $table->foreignId('id_penyewa')->constrained('penyewa', 'id_penyewa');
            $table->date('tanggal_pembayaran');
            $table->decimal('jumlah_pembayaran', 10, 2);
            $table->string('bukti_pembayaran');
            $table->enum('status_verifikasi', ['pending', 'verified', 'rejected']);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE pembayaran MODIFY status_verifikasi ENUM('pending', 'proses', 'verified', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE pembayaran MODIFY status_verifikasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending'");
        Schema::dropIfExists('pembayaran');
    }
};
