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
        $terminals = VendistaTerminal::with(['settings.warehouse', 'ingredients'])
            ->orderBy('comment')
            ->get();

        return response()->json(['terminals' => $terminals]);
    }

    /** Одна точка с настройками и ингредиентами */
    public function show(VendistaTerminal $terminal): JsonResponse
    {
        $terminal->load(['settings.warehouse', 'ingredients']);

        return response()->json(['terminal' => $terminal]);
    }

    /** Обновление настроек точки */
    public function update(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'short_name' => ['nullable', 'string', 'max:100'],
            'hidden' => ['required', 'boolean'],
            'uses_water' => ['required', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $terminal->settings()->updateOrCreate(
            ['vendista_terminal_id' => $terminal->id],
            $validated,
        );

        $terminal->load('settings.warehouse');

        return response()->json(['terminal' => $terminal]);
    }

    /** Добавление ингредиента к точке (в конец списка) */
    public function addIngredient(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
        ]);

        $maxOrder = $terminal->ingredients()->max('terminal_ingredients.sort_order') ?? -1;

        $terminal->ingredients()->syncWithoutDetaching([
            $validated['ingredient_id'] => ['sort_order' => $maxOrder + 1],
        ]);

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

    /** Изменение порядка ингредиентов на точке */
    public function reorderIngredients(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'ingredient_ids' => ['required', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:ingredients,id'],
        ]);

        foreach ($validated['ingredient_ids'] as $order => $ingredientId) {
            $terminal->ingredients()->updateExistingPivot($ingredientId, [
                'sort_order' => $order,
            ]);
        }

        $terminal->load('ingredients');

        return response()->json(['ingredients' => $terminal->ingredients]);
    }

    /** Импорт ингредиентов с другой точки (с сохранением порядка) */
    public function importIngredients(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'source_terminal_id' => ['required', 'integer', 'exists:vendista_terminals,id'],
        ]);

        $source = VendistaTerminal::findOrFail($validated['source_terminal_id']);
        $sourceIngredients = $source->ingredients()->get();

        // Заменяем текущие ингредиенты на ингредиенты источника с тем же порядком
        $syncData = [];
        foreach ($sourceIngredients as $ingredient) {
            $syncData[$ingredient->id] = ['sort_order' => $ingredient->pivot->sort_order];
        }

        $terminal->ingredients()->sync($syncData);
        $terminal->load('ingredients');

        return response()->json([
            'ingredients' => $terminal->ingredients,
            'source_name' => $source->comment,
        ]);
    }
}
