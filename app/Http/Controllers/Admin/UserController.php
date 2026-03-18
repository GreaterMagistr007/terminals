<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Список пользователей */
    public function index(): JsonResponse
    {
        $users = User::orderBy('name')->get();

        return response()->json(['users' => $users]);
    }

    /** Создание пользователя (ручное добавление) */
    public function store(Request $request, AuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        $invite = $authService->createInvite($user);

        return response()->json([
            'user' => $user,
            'invite_url' => $this->buildInviteUrl($invite->token),
        ], 201);
    }

    /** Обновление пользователя (роль, статус, имя) */
    public function update(Request $request, User $user): JsonResponse
    {
        // Запрет изменения роли/статуса самому себе
        if ($user->id === $request->user()->id && ($request->has('role') || $request->has('is_active'))) {
            return response()->json(['message' => 'Нельзя изменить роль или статус самому себе.'], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json(['user' => $user->fresh()]);
    }

    /** Генерация нового инвайт-токена для пользователя */
    public function generateInvite(User $user, AuthService $authService): JsonResponse
    {
        if ($user->telegram_id !== null) {
            return response()->json([
                'message' => 'Пользователь уже привязал Telegram.',
            ], 422);
        }

        $invite = $authService->createInvite($user);

        return response()->json([
            'invite_url' => $this->buildInviteUrl($invite->token),
        ]);
    }

    private function buildInviteUrl(string $token): string
    {
        $botUsername = config('services.telegram.bot_username');

        return "https://t.me/{$botUsername}?start=invite_{$token}";
    }
}
