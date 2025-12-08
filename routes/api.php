<?php

use App\Http\Controllers\Api\Activities\RequirementController as ActivitiesRequirementController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AdsController;
use App\Http\Controllers\Api\AdminActivityController;
use App\Http\Controllers\Api\Auth\LoginOtpController;
use App\Http\Controllers\Api\Auth\PasswordResetOtpController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessDealController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\P2pMeetingController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RequirementController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\V1\CoinsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('login/request-otp', [LoginOtpController::class, 'requestOtp']);
        Route::post('login/verify-otp', [LoginOtpController::class, 'verifyOtp']);
        Route::post('forgot-password', [PasswordResetOtpController::class, 'sendOtp']);
        Route::post('reset-password', [PasswordResetOtpController::class, 'resetWithOtp']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::patch('/profile', [ProfileController::class, 'update']);

        // Members & connections
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::get('/members/public/{slug}', [MemberController::class, 'publicProfileBySlug']);

        Route::post('/members/{id}/connections', [MemberController::class, 'sendConnectionRequest']);
        Route::post('/members/{id}/connections/accept', [MemberController::class, 'acceptConnection']);
        Route::delete('/members/{id}/connections', [MemberController::class, 'deleteConnection']);

        Route::get('/me/connections', [MemberController::class, 'myConnections']);
        Route::get('/me/connection-requests', [MemberController::class, 'myConnectionRequests']);

        // Circles
        Route::get('/circles', [CircleController::class, 'index']);
        Route::get('/circles/{id}', [CircleController::class, 'show']);
        Route::post('/circles', [CircleController::class, 'store']);
        Route::put('/circles/{id}', [CircleController::class, 'update']);
        Route::patch('/circles/{id}', [CircleController::class, 'update']);
        Route::post('/circles/{id}/join', [CircleController::class, 'join']);
        Route::get('/my/circles', [CircleController::class, 'myCircles']);
        Route::get('/circles/{id}/members', [CircleController::class, 'members']);
        Route::put('/circles/{circleId}/members/{memberId}', [CircleController::class, 'updateMember']);
        Route::patch('/circles/{circleId}/members/{memberId}', [CircleController::class, 'updateMember']);

        // Posts & feed
        Route::get('/posts/feed', [PostController::class, 'feed']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::get('/posts/{id}', [PostController::class, 'show']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);

        Route::post('/posts/{id}/like', [PostController::class, 'like']);
        Route::delete('/posts/{id}/like', [PostController::class, 'unlike']);

        Route::post('/posts/{id}/comments', [PostController::class, 'storeComment']);
        Route::get('/posts/{id}/comments', [PostController::class, 'listComments']);

        // Events
        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/{id}', [EventController::class, 'show']);
        Route::post('/events', [EventController::class, 'store']);
        Route::post('/events/{id}/rsvp', [EventController::class, 'rsvp']);
        Route::post('/events/{id}/checkin', [EventController::class, 'checkin']);

        // User Activities & Coins
        Route::post('/activities', [ActivityController::class, 'store']);
        Route::get('/activities/my', [ActivityController::class, 'myActivities']);
        Route::get('/activities/my/coins-summary', [ActivityController::class, 'myCoinsSummary']);
        Route::get('/activities/my/coins-ledger', [ActivityController::class, 'myCoinsLedger']);
        Route::get('/me/coins', [CoinsController::class, 'balance']);
        Route::get('/me/coins/ledger', [CoinsController::class, 'ledger']);

        Route::prefix('activities')->group(function () {
            // P2P Meetings
            Route::get('p2p-meetings', [P2pMeetingController::class, 'index']);
            Route::post('p2p-meetings', [P2pMeetingController::class, 'store']);
            Route::get('p2p-meetings/{id}', [P2pMeetingController::class, 'show']);

            // Requirements
            Route::get('requirements', [ActivitiesRequirementController::class, 'index']);
            Route::post('requirements', [ActivitiesRequirementController::class, 'store']);
            Route::get('requirements/{id}', [ActivitiesRequirementController::class, 'show']);

            // Referrals
            Route::get('referrals', [ReferralController::class, 'index']);
            Route::post('referrals', [ReferralController::class, 'store']);
            Route::get('referrals/{id}', [ReferralController::class, 'show']);

            // Business Deals
            Route::get('business-deals', [BusinessDealController::class, 'index']);
            Route::post('business-deals', [BusinessDealController::class, 'store']);
            Route::get('business-deals/{id}', [BusinessDealController::class, 'show']);

            // Testimonials
            Route::get('testimonials', [TestimonialController::class, 'index']);
            Route::post('testimonials', [TestimonialController::class, 'store']);
            Route::get('testimonials/{id}', [TestimonialController::class, 'show']);
        });

        // Admin Activities
        Route::get('/admin/activities', [AdminActivityController::class, 'index']);
        Route::get('/admin/activities/{activity}', [AdminActivityController::class, 'show']);
        Route::patch('/admin/activities/{id}', [AdminActivityController::class, 'updateStatus']);
        Route::patch('/admin/activities/{activity}/approve', [AdminActivityController::class, 'approve']);
        Route::patch('/admin/activities/{activity}/reject', [AdminActivityController::class, 'reject']);

        // Wallet
        Route::get('/wallet/transactions', [WalletController::class, 'myTransactions']);
        Route::post('/wallet/topup', [WalletController::class, 'topup']);

        // Requirements
        Route::post('/requirements', [RequirementController::class, 'store']);
        Route::get('/requirements', [RequirementController::class, 'index']);
        Route::get('/requirements/{id}', [RequirementController::class, 'show']);
        Route::put('/requirements/{id}', [RequirementController::class, 'update']);
        Route::patch('/requirements/{id}', [RequirementController::class, 'update']);

        // Support - user-facing
        Route::post('/support', [SupportController::class, 'store']);
        Route::get('/support/my', [SupportController::class, 'mySupportRequests']);

        // Support - admin-facing
        Route::get('/support/admin', [SupportController::class, 'adminIndex']);
        Route::patch('/support/admin/{id}', [SupportController::class, 'adminUpdate']);

        // Chats & Messages
        Route::get('/chats', [ChatController::class, 'listChats']);
        Route::post('/chats', [ChatController::class, 'storeChat']);
        Route::get('/chats/{id}', [ChatController::class, 'showChat']);
        Route::get('/chats/{id}/messages', [ChatController::class, 'listMessages']);
        Route::post('/chats/{id}/messages', [ChatController::class, 'storeMessage']);
        Route::post('/chats/{id}/mark-read', [ChatController::class, 'markRead']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        // Referrals & Visitors
        Route::post('/referrals/links', [ReferralController::class, 'storeLink']);
        Route::get('/referrals/links', [ReferralController::class, 'listLinks']);

        Route::get('/referrals/visitors', [ReferralController::class, 'listVisitors']);
        Route::patch('/referrals/visitors/{id}', [ReferralController::class, 'updateVisitor']);

        // Files
        Route::post('/files/upload', [FileController::class, 'upload']);
    });

    // Wallet payment webhook (called by payment gateway)
    Route::post('/wallet/webhook', [WalletController::class, 'paymentWebhook']);

    // Feedback (public, user optional)
    Route::post('/feedback', [FeedbackController::class, 'store']);

    // Ads banners (public)
    Route::get('/ads/banners', [AdsController::class, 'index']);

    // Other module routes (members, circles, posts, etc.) will be added here later.
});
