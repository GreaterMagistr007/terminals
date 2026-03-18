<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class TelegramBotCallbackController extends Controller
{
    /** Авторизация по одноразовому токену от бота */
    public function callback(string $token, AuthService $authService): JsonResponse
    {
        $user = $authService->loginViaBotToken($token);

        if ($user === null) {
            return response()->json(['message' => 'Ссылка недействительна или истекла.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Аккаунт ожидает активации администратором.'], 403);
        }

        return response()->json(['user' => $user]);
    }
}
