<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\CirclesController;
use App\Http\Controllers\Admin\MemberActivityController;

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
        Route::get('/members/{member}/details', [UsersController::class, 'details'])->name('members.details');
        Route::get('/members/{member}/activities', [MemberActivityController::class, 'index'])->name('members.activities.index');
        Route::get('/members/{member}/activities/p2p-meetings', [MemberActivityController::class, 'p2pMeetingsIndex'])->name('members.activities.p2p-meetings');
        Route::get('/members/{member}/activities/referrals', [MemberActivityController::class, 'referralsIndex'])->name('members.activities.referrals');
        Route::get('/members/{member}/activities/business-deals', [MemberActivityController::class, 'businessDealsIndex'])->name('members.activities.business-deals');
        Route::get('/members/{member}/activities/requirements', [MemberActivityController::class, 'requirementsIndex'])->name('members.activities.requirements');
        Route::get('/members/{member}/activities/testimonials', [MemberActivityController::class, 'testimonialsIndex'])->name('members.activities.testimonials');
        Route::get('/members/{member}/activities/{type}/create', [MemberActivityController::class, 'create'])->name('members.activities.create');
        Route::post('/members/{member}/activities/{type}', [MemberActivityController::class, 'store'])->name('members.activities.store');
        Route::post('/files/upload', [\App\Http\Controllers\Admin\AdminFileUploadController::class, 'upload'])->name('files.upload');
        Route::get('/users/import', [UsersController::class, 'importForm'])->name('users.import');
        Route::post('/users/import', [UsersController::class, 'import'])->name('users.import.submit');
        Route::post('/users/export/csv', [UsersController::class, 'exportCsv'])->name('users.export.csv');
        Route::get('/circles', [CirclesController::class, 'index'])->name('circles.index');
    });
});
