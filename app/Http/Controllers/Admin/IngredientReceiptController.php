<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientReceipt;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientReceiptController extends Controller
{
    /** Оприходование ингредиента на склад */
    public function store(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
            'source' => ['required', 'in:box,unit'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Расчёт количества в минимальных единицах
        $quantityUnits = $validated['source'] === 'box'
            ? $validated['quantity'] * $ingredient->quantity_per_box
            : $validated['quantity'];

        return DB::transaction(function () use ($validated, $ingredient, $quantityUnits) {
            // Создание записи оприходования
            $receipt = IngredientReceipt::create([
                'warehouse_id' => $validated['warehouse_id'],
                'ingredient_id' => $ingredient->id,
                'quantity_units' => $quantityUnits,
                'cost_per_unit' => $validated['cost_per_unit'],
                'source' => $validated['source'],
                'note' => $validated['note'] ?? null,
            ]);

            // Обновление остатка на складе
            $stock = WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $validated['warehouse_id'],
                    'ingredient_id' => $ingredient->id,
                ],
                ['quantity' => 0]
            );
            $stock->increment('quantity', $quantityUnits);
            $stock->refresh();

            // Обновление цены ингредиента
            if ($validated['source'] === 'unit') {
                $ingredient->update(['cost_per_unit' => $validated['cost_per_unit']]);
            } else {
                $ingredient->update(['cost_per_unit_in_box' => $validated['cost_per_unit']]);
            }

            return response()->json([
                'receipt' => $receipt,
                'stock' => $stock,
            ]);
        });
    }
}
