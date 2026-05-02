<?php

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::get('/news', [NewsController::class, 'index']);
Route::post('/news', [NewsController::class, 'store']);
Route::put('/news/{id}', [NewsController::class, 'update']);
Route::delete('/news/{id}', [NewsController::class, 'destroy']);

Route::get('/debug', function () {
    return response()->json([
        'supabase_url' => config('services.supabase.url'),
        'supabase_key_set' => !empty(config('services.supabase.service_role_key')),
        'pgsql_loaded' => extension_loaded('pdo_pgsql'),
        'php_version' => PHP_VERSION,
    ]);
});