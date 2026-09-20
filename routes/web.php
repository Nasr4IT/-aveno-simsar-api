<?php

use Illuminate\Support\Facades\Route;

// This backend is API-only for the Flutter app — see routes/api.php.
// This route just confirms the server is alive when opened in a browser.
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'status' => 'ok',
]));
