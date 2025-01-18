<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\PenyewaController;
use App\Http\Controllers\PemasukanPengeluaranController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KatalogMakananController;
use App\Http\Controllers\PesananMakananController;
use App\Http\Controllers\NomorPentingController;
use App\Http\Controllers\KategoriKamarController;
// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Add other protected routes here
});

// route kamar
//done
Route::get('/kamarall', [KamarController::class, 'getAll']);
Route::post('/kamar', [KamarController::class, 'create']);
Route::post('/kamarupdate/{id}', [KamarController::class, 'update']);
Route::delete('/kamardel/{id}', [KamarController::class, 'delete']);

// Route untuk Penyewa
//done
Route::prefix('penyewa')->group(function () {
    Route::get('/all', [PenyewaController::class, 'getAll']);
    Route::get('/{id_penyewa}', [PenyewaController::class, 'getById']);
    Route::get('/kategori-kamar/list', [PenyewaController::class, 'getKategoriKamar']); // Route baru
    Route::get('/unit-tersedia/{id_kamar}', [PenyewaController::class, 'getUnitTersedia']); // Route baru
    Route::post('/create', [PenyewaController::class, 'create']);
    Route::put('/update/{id_penyewa}', [PenyewaController::class, 'update']);
    Route::delete('/delete/{id_penyewa}', [PenyewaController::class, 'delete']);
});

// Route untuk Unit Kamar
Route::prefix('unit-kamar')->group(function () {
    Route::get('/{id_kamar}/available', [UnitKamarController::class, 'getAvailableUnits']);
    Route::put('/{id_unit}/status', [UnitKamarController::class, 'updateStatus']);
});

// Route untuk Pemasukan dan Pengeluaran
// Route untuk Pemasukan dan Pengeluaran
Route::prefix('pemasukan-pengeluaran')->group(function () {
    Route::get('/get/all', [PemasukanPengeluaranController::class, 'getAll']);
    Route::get('/getby/{id}', [PemasukanPengeluaranController::class, 'getById']);
    Route::post('/create', [PemasukanPengeluaranController::class, 'create']);
    Route::put('/update/{id}', [PemasukanPengeluaranController::class, 'update']);
    Route::delete('/delete/{id}', [PemasukanPengeluaranController::class, 'delete']);
    Route::get('/rekap-harian/{tanggal}', [PemasukanPengeluaranController::class, 'getRekapHarian']);
    Route::get('/rekap-bulanan/{bulan}/{tahun}', [PemasukanPengeluaranController::class, 'getRekapBulanan']);
    Route::get('/rekap-tahunan/{tahun}', [PemasukanPengeluaranController::class, 'getRekapTahunan']);
});

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