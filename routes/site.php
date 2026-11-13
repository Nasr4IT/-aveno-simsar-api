<?php

use App\Http\Controllers\Site\AdController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\ExploreController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MyAdsController;
use Illuminate\Support\Facades\Route;

// The public website: a browser version of the Flutter app, offering the
// same features against the same database (so the admin panel governs
// both). Controllers query Eloquent directly, like the admin panel does,
// and share the ad feed / attribute logic with the API via App\Services.
Route::name('site.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    Route::get('/ads/{ad:slug}', [AdController::class, 'show'])->name('ads.show');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
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
    });
});
