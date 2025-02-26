<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable(false)->change();
        });
    }
};
