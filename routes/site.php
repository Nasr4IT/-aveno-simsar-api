<?php

use App\Http\Controllers\Site\AdController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\ExploreController;
use App\Http\Controllers\Site\HomeController;
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
});
