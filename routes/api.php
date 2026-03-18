<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TelegramBotCallbackController;
use App\Http\Controllers\Auth\TelegramWidgetController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Авторизация (гостевые маршруты, rate limited)
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/telegram-widget', [TelegramWidgetController::class, 'callback']);
    Route::post('/telegram-bot/{token}', [TelegramBotCallbackController::class, 'callback']);
});

// Защищённые маршруты (авторизованный + активный пользователь)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Админские маршруты
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/{user}/invite', [UserController::class, 'generateInvite']);
    });
});
