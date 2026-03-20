<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\VendistaTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /** Список всех точек с настройками */
    public function index(): JsonResponse
    {
        $terminals = VendistaTerminal::with('settings')
            ->orderBy('comment')
            ->get();

        return response()->json(['terminals' => $terminals]);
    }

    /** Одна точка с настройками и ингредиентами */
    public function show(VendistaTerminal $terminal): JsonResponse
    {
        $terminal->load(['settings', 'ingredients']);

        return response()->json(['terminal' => $terminal]);
    }

    /** Обновление настроек точки */
    public function update(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'hidden' => ['required', 'boolean'],
            'uses_water' => ['required', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $terminal->settings()->updateOrCreate(
            ['vendista_terminal_id' => $terminal->id],
            $validated,
        );

        $terminal->load('settings');

        return response()->json(['terminal' => $terminal]);
    }

    /** Добавление ингредиента к точке */
    public function addIngredient(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
        ]);

        $terminal->ingredients()->syncWithoutDetaching([$validated['ingredient_id']]);
        $terminal->load('ingredients');

        return response()->json(['ingredients' => $terminal->ingredients]);
    }

    /** Удаление ингредиента с точки */
    public function removeIngredient(VendistaTerminal $terminal, Ingredient $ingredient): JsonResponse
    {
        $terminal->ingredients()->detach($ingredient->id);
        $terminal->load('ingredients');

        return response()->json(['ingredients' => $terminal->ingredients]);
    }
}
