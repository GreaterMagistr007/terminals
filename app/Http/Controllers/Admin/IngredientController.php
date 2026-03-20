<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /** Список всех ингредиентов */
    public function index(): JsonResponse
    {
        $ingredients = Ingredient::with('warehouseStocks.warehouse')->orderBy('name')->get();

        return response()->json(['ingredients' => $ingredients]);
    }

    /** Создание ингредиента */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'unit' => ['string', 'max:100'],
            'cost_per_unit' => ['numeric', 'min:0'],
            'quantity_per_package' => ['integer', 'min:1'],
            'quantity_per_box' => ['nullable', 'integer', 'min:1'],
            'cost_per_unit_in_box' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json(['ingredient' => $ingredient], 201);
    }

    /** Обновление ингредиента */
    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['string', 'max:255', 'unique:ingredients,name,' . $ingredient->id],
            'short_name' => ['nullable', 'string', 'max:50'],
            'unit' => ['string', 'max:100'],
            'cost_per_unit' => ['numeric', 'min:0'],
            'quantity_per_package' => ['integer', 'min:1'],
            'quantity_per_box' => ['nullable', 'integer', 'min:1'],
            'cost_per_unit_in_box' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $ingredient->update($validated);

        return response()->json(['ingredient' => $ingredient]);
    }

    /** Удаление ингредиента */
    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();

        return response()->json(['message' => 'Ингредиент удалён']);
    }
}
