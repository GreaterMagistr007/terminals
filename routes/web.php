<?php

use Illuminate\Support\Facades\Route;

// API-маршруты подключаются в routes/api.php

// Все остальные запросы — SPA (Vue Router обрабатывает маршрутизацию на клиенте)
// Исключаем api/, sanctum/, up (health check) — они обрабатываются своими маршрутами
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api/).*$');
