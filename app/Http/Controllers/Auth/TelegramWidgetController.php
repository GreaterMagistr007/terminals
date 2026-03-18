<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWidgetController extends Controller
{
    /** Авторизация через Telegram Login Widget */
    public function callback(Request $request, AuthService $authService, TelegramService $telegramService): JsonResponse
    {
        $request->validate([
            'id' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'hash' => 'required|string',
            'auth_date' => 'required|integer',
        ]);

        $user = $authService->loginViaWidget(
            $request->only(['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash']),
            $telegramService,
        );

        if ($user === null) {
            return response()->json(['message' => 'Авторизация не удалась.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Аккаунт ожидает активации администратором.'], 403);
        }

        return response()->json(['user' => $user]);
    }
}
