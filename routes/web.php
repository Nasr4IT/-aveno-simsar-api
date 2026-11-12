<?php

use App\Http\Controllers\AdminPanel\AdminAuthController;
use App\Http\Controllers\AdminPanel\AdModerationController;
use App\Http\Controllers\AdminPanel\BannerManagementController;
use App\Http\Controllers\AdminPanel\CategoryManagementController;
use App\Http\Controllers\AdminPanel\DashboardController;
use App\Http\Controllers\AdminPanel\ReportManagementController;
use App\Http\Controllers\AdminPanel\UserManagementController;
use Illuminate\Support\Facades\Route;

// Two server-rendered surfaces share this file's session-based 'web'
// guard (entirely separate from the API's Sanctum bearer tokens):
//   - the public website (routes/site.php) — the same features as the
//     Flutter app, for a browser;
//   - the admin panel below, role-gated by the 'admin' middleware.
Route::get('/healthz', fn () => response()->json([
    'app' => config('app.name'),
    'status' => 'ok',
]));

require __DIR__.'/site.php';

Route::prefix('admin-panel')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/ads', [AdModerationController::class, 'index'])->name('ads.index');
        Route::post('/ads/{ad}/approve', [AdModerationController::class, 'approve'])->name('ads.approve');
        Route::post('/ads/{ad}/reject', [AdModerationController::class, 'reject'])->name('ads.reject');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/ban', [UserManagementController::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [UserManagementController::class, 'unban'])->name('users.unban');

        Route::get('/categories', [CategoryManagementController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryManagementController::class, 'store'])->name('categories.store');
        Route::post('/categories/{category}/update', [CategoryManagementController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/attributes', [CategoryManagementController::class, 'storeAttribute'])->name('categories.attributes.store');
        Route::post('/categories/{category}/delete', [CategoryManagementController::class, 'destroy'])->name('categories.destroy');

        Route::get('/banners', [BannerManagementController::class, 'index'])->name('banners.index');
        Route::post('/banners', [BannerManagementController::class, 'store'])->name('banners.store');
        Route::post('/banners/{banner}/update', [BannerManagementController::class, 'update'])->name('banners.update');
        Route::post('/banners/{banner}/toggle', [BannerManagementController::class, 'toggle'])->name('banners.toggle');
        Route::post('/banners/{banner}/delete', [BannerManagementController::class, 'destroy'])->name('banners.destroy');

        Route::get('/reports', [ReportManagementController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/resolve', [ReportManagementController::class, 'resolve'])->name('reports.resolve');
        Route::post('/reports/{report}/dismiss', [ReportManagementController::class, 'dismiss'])->name('reports.dismiss');
    });
});
