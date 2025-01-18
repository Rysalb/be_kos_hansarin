<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Route::middleware(['auth:sanctum', 'verified'])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');

//     // Route untuk Admin
//     Route::middleware(['role:admin'])->prefix('admin')->group(function () {
//         Route::get('/dashboard', function () {
//             return view('admin.dashboard');
//         })->name('admin.dashboard');
//         // Tambahkan route admin lainnya
//     });

//     // Route untuk User
//     Route::middleware(['role:user'])->prefix('user')->group(function () {
//         Route::get('/dashboard', function () {
//             return view('user.dashboard');
//         })->name('user.dashboard');
//         // Tambahkan route user lainnya
//     });
// });