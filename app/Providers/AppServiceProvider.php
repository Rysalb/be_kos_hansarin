<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // Add this
use Illuminate\Support\Facades\Config; // Add this
use Illuminate\Support\Facades\DB;     // Add this

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        // Set timezone for database
        Config::set('app.timezone', 'Asia/Jakarta');
        date_default_timezone_set('Asia/Jakarta');
        DB::statement("SET time_zone='+07:00'");
    }
}
