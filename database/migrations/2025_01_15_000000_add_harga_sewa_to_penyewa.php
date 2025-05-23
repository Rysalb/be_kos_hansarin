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
        Schema::table('penyewa', function (Blueprint $table) {
            $table->decimal('harga_sewa', 12, 2)->nullable()->after('durasi_sewa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penyewa', function (Blueprint $table) {
            $table->dropColumn('harga_sewa');
        });
    }
}; 