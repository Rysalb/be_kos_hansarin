<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\PenyewaController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KatalogMakananController;
use App\Http\Controllers\PesananMakananController;
use App\Http\Controllers\NomorPentingController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Add other protected routes here
});
Route::get('/kamarall', [KamarController::class, 'getAll']);
Route::post('/kamar', [KamarController::class, 'create']);
Route::post('/kamarupdate/{id}', [KamarController::class, 'update']);
Route::delete('/kamardel/{id}', [KamarController::class, 'delete']);

// Route::prefix('kelola')->group(function () {
    Route::get('/penyewa/all', [PenyewaController::class, 'getAll']);
    Route::get('/penyewa/{id_penyewa}', [PenyewaController::class, 'getById']);
    Route::post('/penyewa/create', [PenyewaController::class, 'create']);
    Route::put('/penyewa/{id_penyewa}', [PenyewaController::class, 'update']);
    Route::delete('/penyewa/{id_penyewa}', [PenyewaController::class, 'delete']);
// });

Route::prefix('pembayaran')->group(function () {
    Route::get('/', [PembayaranController::class, 'getAll']);
    Route::get('/{id_pembayaran}', [PembayaranController::class, 'getById']);
    Route::post('/', [PembayaranController::class, 'create']);
    Route::put('/{id_pembayaran}', [PembayaranController::class, 'update']);
    Route::delete('/{id_pembayaran}', [PembayaranController::class, 'delete']);
    Route::put('/{id_pembayaran}/verifikasi', [PembayaranController::class, 'verifikasi']);
});


Route::prefix('katalog-makanan')->group(function () {
    Route::get('/', [KatalogMakananController::class, 'getAll']);
    Route::get('/{id_makanan}', [KatalogMakananController::class, 'getById']);
    Route::post('/', [KatalogMakananController::class, 'create']);
    Route::put('/{id_makanan}', [KatalogMakananController::class, 'update']);
    Route::delete('/{id_makanan}', [KatalogMakananController::class, 'delete']);
    Route::put('/{id_makanan}/status', [KatalogMakananController::class, 'updateStatus']);
});

Route::prefix('pesanan-makanan')->group(function () {
    Route::get('/', [PesananMakananController::class, 'getAll']);
    Route::get('/{id_pesanan}', [PesananMakananController::class, 'getById']);
    Route::post('/', [PesananMakananController::class, 'create']);
    Route::put('/{id_pesanan}', [PesananMakananController::class, 'update']);
    Route::delete('/{id_pesanan}', [PesananMakananController::class, 'delete']);
    Route::put('/{id_pesanan}/status', [PesananMakananController::class, 'updateStatus']);
    Route::get('/penyewa/{id_penyewa}', [PesananMakananController::class, 'getByPenyewa']);
});

Route::prefix('nomor-penting')->group(function () {
    Route::get('/', [NomorPentingController::class, 'getAll']);
    Route::get('/{id_nomor}', [NomorPentingController::class, 'getById']);
    Route::post('/', [NomorPentingController::class, 'create']);
    Route::put('/{id_nomor}', [NomorPentingController::class, 'update']);
    Route::delete('/{id_nomor}', [NomorPentingController::class, 'delete']);
    Route::get('/kategori/{kategori}', [NomorPentingController::class, 'getByKategori']);
});