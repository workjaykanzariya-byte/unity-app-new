<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login/request', [AdminAuthController::class, 'requestOtp'])->name('admin.login.request');
    Route::post('/login/verify', [AdminAuthController::class, 'verifyOtp'])->name('admin.login.verify');

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    });
});
