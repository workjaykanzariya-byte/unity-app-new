<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\CirclesController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login/send-otp', [AdminAuthController::class, 'requestOtp'])->name('login.send-otp');
    Route::post('/login/verify', [AdminAuthController::class, 'verifyOtp'])->name('login.verify');

    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        })->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/roles/{role}/remove', [UsersController::class, 'removeRole'])->name('users.roles.remove');
        Route::post('/files/upload', [\App\Http\Controllers\Admin\AdminFileUploadController::class, 'upload'])->name('files.upload');
        Route::get('/users/import', [UsersController::class, 'importForm'])->name('users.import');
        Route::post('/users/import', [UsersController::class, 'import'])->name('users.import.submit');
        Route::post('/users/export/pdf', [UsersController::class, 'exportPdf'])->name('users.export.pdf');
        Route::get('/circles', [CirclesController::class, 'index'])->name('circles.index');
    });
});
