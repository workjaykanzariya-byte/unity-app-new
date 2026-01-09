<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\CirclesController;
use App\Http\Controllers\Admin\ActivitiesController;
use App\Http\Controllers\Admin\CoinsController;

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
        Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('/users', [UsersController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/roles/{role}/remove', [UsersController::class, 'removeRole'])->name('users.roles.remove');
        Route::get('/activities', [ActivitiesController::class, 'index'])->name('activities.index');
        Route::post('/activities/export', [ActivitiesController::class, 'export'])->name('activities.export');
        Route::get('/activities/{member}/testimonials', [ActivitiesController::class, 'testimonials'])->name('activities.testimonials');
        Route::get('/activities/{member}/referrals', [ActivitiesController::class, 'referrals'])->name('activities.referrals');
        Route::get('/activities/{member}/business-deals', [ActivitiesController::class, 'businessDeals'])->name('activities.business-deals');
        Route::get('/activities/{member}/p2p-meetings', [ActivitiesController::class, 'p2pMeetings'])->name('activities.p2p-meetings');
        Route::get('/activities/{member}/requirements', [ActivitiesController::class, 'requirements'])->name('activities.requirements');
        Route::get('/coins', [CoinsController::class, 'index'])->name('coins.index');
        Route::get('/coins/add', [CoinsController::class, 'create'])->name('coins.create');
        Route::post('/coins/add', [CoinsController::class, 'store'])->name('coins.store');
        Route::get('/coins/{member}/ledger', [CoinsController::class, 'ledger'])->name('coins.ledger');
        Route::get('/coins/{member}/ledger/{type}', [CoinsController::class, 'ledgerByType'])->name('coins.ledger.type');
        Route::post('/files/upload', [\App\Http\Controllers\Admin\AdminFileUploadController::class, 'upload'])->name('files.upload');
        Route::get('/users/import', [UsersController::class, 'importForm'])->name('users.import');
        Route::post('/users/import', [UsersController::class, 'import'])->name('users.import.submit');
        Route::post('/users/export/csv', [UsersController::class, 'exportCsv'])->name('users.export.csv');
        Route::get('/circles', [CirclesController::class, 'index'])->name('circles.index');
    });
});
