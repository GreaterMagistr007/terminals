<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginCodeController extends Controller
{
    /** Создание сессии логина и ссылки на бота */
    public function createSession(AuthService $authService): JsonResponse
    {
        $session = $authService->createLoginSession();
        $botUsername = config('services.telegram.bot_username');

        return response()->json([
            'token' => $session->token,
            'bot_link' => "https://t.me/{$botUsername}?start=auth_{$session->token}",
        ]);
    }

    /** Проверка кода и авторизация */
    public function verifyCode(Request $request, AuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $authService->loginViaCode($validated['code']);

        if ($user === null) {
            return response()->json(['message' => 'Неверный или истёкший код.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Аккаунт деактивирован.'], 403);
        }

        return response()->json(['user' => $user]);
    }
}
