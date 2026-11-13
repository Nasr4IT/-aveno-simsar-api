<?php

use App\Http\Controllers\Site\AdController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\ChatController;
use App\Http\Controllers\Site\ExploreController;
use App\Http\Controllers\Site\FavoriteController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocaleController;
use App\Http\Controllers\Site\MyAdsController;
use App\Http\Controllers\Site\NotificationController;
use App\Http\Controllers\Site\ProfileController;
use App\Http\Controllers\Site\RatingController;
use App\Http\Controllers\Site\ReportController;
use App\Http\Controllers\Site\UserController;
use Illuminate\Support\Facades\Route;

// The public website: a browser version of the Flutter app, offering the
// same features against the same database (so the admin panel governs
// both). Controllers query Eloquent directly, like the admin panel does,
// and share the ad feed / attribute logic with the API via App\Services.
Route::name('site.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    Route::get('/ads/{ad:slug}', [AdController::class, 'show'])->name('ads.show');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/lang/{locale}', LocaleController::class)->name('locale');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.attempt');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register.attempt');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/sell', [AdController::class, 'create'])->name('sell');
        Route::post('/sell', [AdController::class, 'store'])->name('sell.store');

        // The seller's own ads — any status. Plain POST forms (no PUT/DELETE
        // spoofing), like the admin panel.
        Route::prefix('my')->name('my.')->group(function () {
            Route::get('/ads', [MyAdsController::class, 'index'])->name('ads');
            Route::get('/ads/{ad}/edit', [MyAdsController::class, 'edit'])->name('ads.edit');
            Route::post('/ads/{ad}/edit', [MyAdsController::class, 'update'])->name('ads.update');
            Route::post('/ads/{ad}/mark-sold', [MyAdsController::class, 'markSold'])->name('ads.mark-sold');
            Route::post('/ads/{ad}/relist', [MyAdsController::class, 'relist'])->name('ads.relist');
            Route::post('/ads/{ad}/delete', [MyAdsController::class, 'destroy'])->name('ads.destroy');
            Route::post('/ads/{ad}/images', [MyAdsController::class, 'addImages'])->name('ads.images.store');
            Route::post('/ads/{ad}/images/{image}/delete', [MyAdsController::class, 'removeImage'])->name('ads.images.destroy');
        });

        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
        Route::post('/ads/{ad}/favorite', [FavoriteController::class, 'store'])->name('ads.favorite');
        Route::post('/ads/{ad}/unfavorite', [FavoriteController::class, 'destroy'])->name('ads.unfavorite');

        Route::get('/messages', [ChatController::class, 'index'])->name('messages');
        Route::post('/ads/{ad}/message', [ChatController::class, 'start'])->name('ads.message');
        Route::get('/messages/{conversation}', [ChatController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [ChatController::class, 'send'])->name('messages.send');
        Route::get('/messages/{conversation}/poll', [ChatController::class, 'poll'])->name('messages.poll');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('/users/{user}/rate', [RatingController::class, 'store'])->name('users.rate');
        Route::post('/users/{user}/report', [ReportController::class, 'user'])->name('users.report');
        Route::post('/ads/{ad}/report', [ReportController::class, 'ad'])->name('ads.report');
    });
});
