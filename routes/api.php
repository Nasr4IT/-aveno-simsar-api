<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\Admin\AdminAdController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — full contract in docs/API_CONTRACT.md
|--------------------------------------------------------------------------
| Every route here is what the Flutter app is built against. Changing a
| URL or a response shape is a breaking change for the frontend — update
| docs/API_CONTRACT.md and tell your friend in the same commit.
*/

// --- Public -----------------------------------------------------------
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/ads', [AdController::class, 'index']);
Route::get('/ads/{ad}', [AdController::class, 'show']);

Route::get('/ad-packages', [PaymentController::class, 'packages']);
Route::post('/payments/shamcash/webhook', [PaymentController::class, 'webhook']);

Route::get('/users/{user}/ratings', [RatingController::class, 'index']);

// --- Authenticated (Sanctum bearer token) ------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/my/ads', [AdController::class, 'myAds']);
    Route::post('/ads', [AdController::class, 'store']);
    Route::match(['put', 'patch'], '/ads/{ad}', [AdController::class, 'update']);
    Route::delete('/ads/{ad}', [AdController::class, 'destroy']);
    Route::post('/ads/{ad}/images', [AdController::class, 'addImages']);
    Route::delete('/ads/{ad}/images/{image}', [AdController::class, 'removeImage']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/ads/{ad}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/ads/{ad}/favorite', [FavoriteController::class, 'destroy']);

    Route::get('/conversations', [ChatController::class, 'index']);
    Route::post('/ads/{ad}/conversations', [ChatController::class, 'start']);
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);

    Route::post('/users/{user}/ratings', [RatingController::class, 'store']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::post('/ads/{ad}/checkout', [PaymentController::class, 'checkout']);

    // --- Admin (auth:sanctum + admin role) -----------------------------
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::apiResource('categories', AdminCategoryController::class)->except(['show']);
        Route::post('categories/{category}/attributes', [AdminCategoryController::class, 'storeAttribute']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users/{user}/ban', [AdminUserController::class, 'ban']);
        Route::post('users/{user}/unban', [AdminUserController::class, 'unban']);

        Route::get('ads', [AdminAdController::class, 'index']);
        Route::post('ads/{ad}/approve', [AdminAdController::class, 'approve']);
        Route::post('ads/{ad}/reject', [AdminAdController::class, 'reject']);
    });
});
