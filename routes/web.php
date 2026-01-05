<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');

Route::prefix('admin/auth')->group(function (): void {
    Route::post('/request-otp', [AdminAuthController::class, 'requestOtp'])->name('admin.request-otp');
    Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp'])->name('admin.verify-otp');
});

Route::prefix('admin')->middleware('admin.auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
