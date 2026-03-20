<?php

use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\IngredientReceiptController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\PointController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendistaTerminalController;
use App\Http\Controllers\Admin\WarehouseController;
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

    // Терминалы (для всех авторизованных)
    Route::get('/terminals', [VendistaTerminalController::class, 'index']);
    Route::get('/terminals/{terminal}', [VendistaTerminalController::class, 'show']);

    // Админские маршруты
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/{user}/invite', [UserController::class, 'generateInvite']);

        Route::get('/points', [PointController::class, 'index']);
        Route::get('/points/{terminal}', [PointController::class, 'show']);
        Route::put('/points/{terminal}', [PointController::class, 'update']);
        Route::post('/points/{terminal}/ingredients', [PointController::class, 'addIngredient']);
        Route::delete('/points/{terminal}/ingredients/{ingredient}', [PointController::class, 'removeIngredient']);
        Route::put('/points/{terminal}/ingredients/reorder', [PointController::class, 'reorderIngredients']);

        Route::apiResource('ingredients', IngredientController::class)->except(['show']);
        Route::post('/ingredients/{ingredient}/receipt', [IngredientReceiptController::class, 'store']);
        Route::post('/ingredients/{ingredient}/purchase', [StockMovementController::class, 'purchase']);
        Route::post('/ingredients/{ingredient}/transfer', [StockMovementController::class, 'transfer']);
        Route::post('/ingredients/{ingredient}/write-off', [StockMovementController::class, 'writeOff']);
        Route::get('/ingredients/{ingredient}/history', [StockMovementController::class, 'history']);

        Route::apiResource('warehouses', WarehouseController::class)->except(['show']);
        Route::get('/warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks']);

        Route::get('/vendista/terminals', [VendistaTerminalController::class, 'index']);
        Route::post('/vendista/terminals/sync', [VendistaTerminalController::class, 'sync']);
    });
});
