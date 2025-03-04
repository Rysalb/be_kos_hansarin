<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\PasswordResetController;
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

// Password Reset Routes
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

// This route is for displaying the reset password form
Route::get('/reset-password/{token}', function ($token, Request $request) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->email
    ]);
})->middleware('guest')->name('password.reset');

// This route is for handling the reset password form submission
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

// Add success route for after password reset
Route::get('/reset-success', function (Request $request) {
    return view('auth.reset-success', [
        'email' => $request->email
    ]);
})->middleware('guest')->name('password.reset.success');

// Catch-all route to explain GET to /reset-password is not supported
Route::get('/reset-password', function() {
    return redirect()->route('password.request')
        ->with('error', 'Invalid password reset link. Please request a new one.');
})->middleware('guest');

// Homepage redirect to login
Route::get('/', function () {
    return redirect('/login');
});