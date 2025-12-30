<?php

use App\Http\Controllers\Admin\Api\AdminAuthApiController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin/api')->group(function () {
    Route::post('/auth/request-otp', [AdminAuthApiController::class, 'requestOtp']);
    Route::post('/auth/verify-otp', [AdminAuthApiController::class, 'verifyOtp']);

    Route::middleware('admin.auth')->group(function () {
        Route::get('/auth/me', [AdminAuthApiController::class, 'me']);
        Route::post('/auth/logout', [AdminAuthApiController::class, 'logout']);
    });
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginEmailForm'])->name('admin.login');
    Route::get('/otp', [AdminAuthController::class, 'showOtpForm'])->name('admin.otp');

    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::view('/', 'admin.app')->name('admin.dashboard');
        Route::view('/{any}', 'admin.app')->where('any', '.*');
    });
});
