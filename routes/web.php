<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\Circles\CircleController;
use App\Http\Controllers\Admin\Circles\CircleMemberController;
use App\Http\Controllers\Admin\ActivitiesController;
use App\Http\Controllers\Admin\ActivitiesBusinessDealsController;
use App\Http\Controllers\Admin\ActivitiesP2PMeetingsController;
use App\Http\Controllers\Admin\ActivitiesReferralsController;
use App\Http\Controllers\Admin\ActivitiesRequirementsController;
use App\Http\Controllers\Admin\ActivitiesTestimonialsController;
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
        Route::get('/activities/testimonials', [ActivitiesTestimonialsController::class, 'index'])->name('activities.testimonials.index');
        Route::get('/activities/testimonials/export', [ActivitiesTestimonialsController::class, 'export'])->name('activities.testimonials.export');
        Route::get('/activities/requirements', [ActivitiesRequirementsController::class, 'index'])->name('activities.requirements.index');
        Route::get('/activities/requirements/export', [ActivitiesRequirementsController::class, 'export'])->name('activities.requirements.export');
        Route::get('/activities/referrals', [ActivitiesReferralsController::class, 'index'])->name('activities.referrals.index');
        Route::get('/activities/referrals/export', [ActivitiesReferralsController::class, 'export'])->name('activities.referrals.export');
        Route::get('/activities/p2p-meetings', [ActivitiesP2PMeetingsController::class, 'index'])->name('activities.p2p-meetings.index');
        Route::get('/activities/p2p-meetings/export', [ActivitiesP2PMeetingsController::class, 'export'])->name('activities.p2p-meetings.export');
        Route::get('/activities/business-deals', [ActivitiesBusinessDealsController::class, 'index'])->name('activities.business-deals.index');
        Route::get('/activities/business-deals/export', [ActivitiesBusinessDealsController::class, 'export'])->name('activities.business-deals.export');
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
        Route::get('/circles', [CircleController::class, 'index'])->name('circles.index');
        Route::get('/circles/create', [CircleController::class, 'create'])->name('circles.create');
        Route::post('/circles', [CircleController::class, 'store'])->name('circles.store');
        Route::get('/circles/{circle}', [CircleController::class, 'show'])->name('circles.show');
        Route::get('/circles/{circle}/edit', [CircleController::class, 'edit'])->name('circles.edit');
        Route::put('/circles/{circle}', [CircleController::class, 'update'])->name('circles.update');
        Route::post('/circles/{circle}/members', [CircleMemberController::class, 'store'])->name('circles.members.store');
        Route::put('/circles/{circle}/members/{circleMember}', [CircleMemberController::class, 'update'])->name('circles.members.update');
        Route::delete('/circles/{circle}/members/{circleMember}', [CircleMemberController::class, 'destroy'])->name('circles.members.destroy');
    });
});
