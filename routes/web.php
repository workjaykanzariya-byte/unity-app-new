<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('admin.guest')->group(function (): void {
        Route::get('/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showEmailForm'])->name('login.form');
        Route::post('/login/send-otp', [\App\Http\Controllers\Admin\AdminAuthController::class, 'sendOtp'])->name('login.sendOtp');
        Route::get('/login/verify', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showVerifyForm'])->name('login.verify.form');
        Route::post('/login/verify', [\App\Http\Controllers\Admin\AdminAuthController::class, 'verifyOtp'])->name('login.verify');
    });

    Route::middleware(['admin.auth', 'admin.role'])->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [\App\Http\Controllers\Admin\AdminUsersController::class, 'index'])->name('users.index');
        Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\AdminUsersController::class, 'edit'])->name('users.edit');
        Route::post('/users/{id}', [\App\Http\Controllers\Admin\AdminUsersController::class, 'update'])->name('users.update');
        Route::post('/logout', [\App\Http\Controllers\Admin\AdminAuthController::class, 'logout'])->name('logout');
    });
});
