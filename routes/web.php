<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'));

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login/send-otp', [AdminAuthController::class, 'sendOtp'])->name('login.send_otp');
    Route::get('/login/verify', [AdminAuthController::class, 'showVerifyForm'])->name('login.verify_form');
    Route::post('/login/verify', [AdminAuthController::class, 'verifyOtp'])->name('login.verify');

    Route::middleware('auth:admin')->group(function (): void {
        Route::get('/', [AdminUsersController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [AdminUsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUsersController::class, 'update'])->name('users.update');
    });
});
