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
use App\Http\Controllers\UnitKamarController;
use App\Http\Controllers\MetodePembayaranController;
use App\Http\Controllers\PeraturanKosController;
// Public routes

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-admin', [AuthController::class, 'registerAdmin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/verifikasi-user/{userId}', [AuthController::class, 'verifikasiUser'])
        ->middleware('role:admin');
    
    Route::delete('/user/{userId}', [AuthController::class, 'deleteUser'])
    ->middleware('role:admin'); // Pastikan hanya admin yang bisa mengakses

    Route::get('/users/role/{role}', [AuthController::class, 'getUsersByRole'])
    ->middleware('role:admin'); // Pastikan hanya admin yang bisa mengakses
    
    Route::get('/user/{userId}', [AuthController::class, 'getUserById'])
    ->middleware('role:admin'); // Pastikan hanya admin yang bisa mengakses
    // Add other protected routes here

    Route::prefix('kamar')->group(function () {
        Route::get('/stats', [KamarController::class, 'getStats']);
        Route::get('/expiring', [KamarController::class, 'getExpiringRooms']);
    });

    // Tambahkan route untuk mendapatkan list admin
    Route::get('/admin-list', [AuthController::class, 'getAdminList']);
});

// route kamar
//done

Route::get('/stats', [KamarController::class, 'getStats']);
Route::get('/expiring', [KamarController::class, 'getExpiringRooms']);
Route::get('/kamarall', [KamarController::class, 'getAll']);
Route::get('/kamarall/user', [KamarController::class, 'getAllKamar']);
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
    Route::post('/update/{id_penyewa}', [PenyewaController::class, 'update']);
    Route::delete('/delete/{id_penyewa}', [PenyewaController::class, 'delete']);
    Route::get('/unit-kamar/all', [PenyewaController::class, 'getAllUnits']);
    Route::post('/pindah-kamar', [PenyewaController::class, 'pindahKamar']);
});

// Route untuk Unit Kamar
Route::prefix('unit-kamar')->group(function () {
    Route::get('/available', [UnitKamarController::class, 'getAvailableUnits']);
    Route::put('/{id_unit}/status', [UnitKamarController::class, 'updateStatus']);
    Route::get('/detail/{id_unit}', [UnitKamarController::class, 'getDetail']);
});

// Route untuk Pemasukan dan Pengeluaran
// Route untuk Pemasukan dan Pengeluaran
Route::prefix('pemasukan-pengeluaran')->group(function () {
    Route::get('/get/all', [PemasukanPengeluaranController::class, 'getAll']);
    Route::get('/getby/{id}', [PemasukanPengeluaranController::class, 'getById']);
    Route::post('/create', [PemasukanPengeluaranController::class, 'create']);
    Route::post('/update/{id}', [PemasukanPengeluaranController::class, 'update']);
    Route::delete('/delete/{id}', [PemasukanPengeluaranController::class, 'delete']);
    Route::get('/rekap-harian/{tanggal}', [PemasukanPengeluaranController::class, 'getRekapHarian']);
    Route::get('/rekap-bulanan/{bulan}/{tahun}', [PemasukanPengeluaranController::class, 'getRekapBulanan']);
    Route::get('/rekap-tahunan/{tahun}', [PemasukanPengeluaranController::class, 'getRekapTahunan']);
    Route::get('/penyewa/{idPenyewa}', [PemasukanPengeluaranController::class, 'getRiwayatTransaksiPenyewa']);
    Route::get('/by-date/{date}', [PemasukanPengeluaranController::class, 'getTransaksiByDate']);
    Route::get('/by-jenis/{jenis}', [PemasukanPengeluaranController::class, 'getTransaksiByJenis']);
});

Route::prefix('pembayaran')->group(function () {
    Route::get('/get/all', [PembayaranController::class, 'getAll']);
    Route::get('/{id_pembayaran}', [PembayaranController::class, 'getById']);
    Route::post('/create', [PembayaranController::class, 'create']);
    Route::post('/update/{id_pembayaran}', [PembayaranController::class, 'update']);
    Route::delete('/delete/{id_pembayaran}', [PembayaranController::class, 'delete']);
    Route::post('/verifikasi/{id_pembayaran}', [PembayaranController::class, 'verifikasi']);
});


Route::prefix('katalog-makanan')->group(function () {
    Route::get('/get/all', [KatalogMakananController::class, 'getAll']);
    Route::get('/get/{id_makanan}', [KatalogMakananController::class, 'getById']);
    Route::post('/create', [KatalogMakananController::class, 'create']);
    Route::post('/update/{id_makanan}', [KatalogMakananController::class, 'update']);
    Route::delete('/delete/{id_makanan}', [KatalogMakananController::class, 'delete']);
    // Route::put('/{id_makanan}/status', [KatalogMakananController::class, 'updateStatus']);
    Route::get('/kategori/{kategori}', [KatalogMakananController::class, 'getByKategori']);
});

Route::prefix('pesanan-makanan')->group(function () {
    Route::get('/get/all', [PesananMakananController::class, 'getAll']);
    Route::get('/get/{id_pesanan}', [PesananMakananController::class, 'getById']);
    Route::post('/create', [PesananMakananController::class, 'create']);
    Route::post('/update/{id_pesanan}', [PesananMakananController::class, 'update']);
    Route::delete('/delete/{id_pesanan}', [PesananMakananController::class, 'delete']);
    Route::post('/{id_pesanan}/status', [PesananMakananController::class, 'updateStatus']);
    Route::get('/penyewa/{id_penyewa}', [PesananMakananController::class, 'getByPenyewa']);
});

Route::prefix('nomor-penting')->group(function () {
    Route::get('/get/all', [NomorPentingController::class, 'getAll']);
    Route::get('/get/{id_nomor}', [NomorPentingController::class, 'getById']);
    Route::post('/create', [NomorPentingController::class, 'create']);
    Route::post('/update/{id_nomor}', [NomorPentingController::class, 'update']);
    Route::delete('/delete/{id_nomor}', [NomorPentingController::class, 'delete']);
    // Route::get('/kategori/{kategori}', [NomorPentingController::class, 'getByKategori']);
});
Route::prefix('kamar')->group(function () {
    Route::get('/stats', [KamarController::class, 'getStats']);
    Route::get('/expiring', [KamarController::class, 'getExpiringRooms']);
});

Route::prefix('metode-pembayaran')->group(function () {
    Route::get('/', [MetodePembayaranController::class, 'index']);
    Route::post('/create', [MetodePembayaranController::class, 'store']);
    Route::delete('/delete/{id}', [MetodePembayaranController::class, 'destroy']);
    Route::post('/update/{id}', [MetodePembayaranController::class, 'update']);
});

Route::prefix('peraturan-kos')->group(function () {
    Route::get('/', [PeraturanKosController::class, 'getAll']);
    Route::post('/create', [PeraturanKosController::class, 'create']);
    Route::put('/update/{id}', [PeraturanKosController::class, 'update']);
    Route::delete('/delete/{id}', [PeraturanKosController::class, 'delete']);
});
