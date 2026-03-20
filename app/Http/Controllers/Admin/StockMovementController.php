<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    /** Покупка / оприходование ингредиента на склад */
    public function purchase(Request $request, Ingredient $ingredient): JsonResponse
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
            // Создание записи движения
            $movement = StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => Auth::id(),
                'type' => StockMovement::TYPE_PURCHASE,
                'quantity' => $quantityUnits,
                'to_warehouse_id' => $validated['warehouse_id'],
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

            // Обновление цены ингредиента
            if ($validated['source'] === 'unit') {
                $ingredient->update(['cost_per_unit' => $validated['cost_per_unit']]);
            } else {
                $ingredient->update(['cost_per_unit_in_box' => $validated['cost_per_unit']]);
            }

            $movement->load(['user', 'fromWarehouse', 'toWarehouse']);

            return response()->json(['movement' => $movement]);
        });
    }

    /** Перемещение ингредиента между складами */
    public function transfer(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated, $ingredient) {
            // Проверка достаточности остатка
            $fromStock = WarehouseStock::where('warehouse_id', $validated['from_warehouse_id'])
                ->where('ingredient_id', $ingredient->id)
                ->first();

            if (!$fromStock || $fromStock->quantity < $validated['quantity']) {
                return response()->json(['message' => 'Недостаточно товара на складе'], 422);
            }

            // Создание записи движения
            $movement = StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => Auth::id(),
                'type' => StockMovement::TYPE_TRANSFER,
                'quantity' => $validated['quantity'],
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'note' => $validated['note'] ?? null,
            ]);

            // Уменьшение остатка на складе-источнике
            $fromStock->decrement('quantity', $validated['quantity']);

            // Увеличение остатка на складе-получателе
            $toStock = WarehouseStock::updateOrCreate(
                [
                    'warehouse_id' => $validated['to_warehouse_id'],
                    'ingredient_id' => $ingredient->id,
                ],
                ['quantity' => 0]
            );
            $toStock->increment('quantity', $validated['quantity']);

            $movement->load(['user', 'fromWarehouse', 'toWarehouse']);

            return response()->json(['movement' => $movement]);
        });
    }

    /** Списание ингредиента со склада */
    public function writeOff(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated, $ingredient) {
            // Проверка достаточности остатка
            $stock = WarehouseStock::where('warehouse_id', $validated['warehouse_id'])
                ->where('ingredient_id', $ingredient->id)
                ->first();

            if (!$stock || $stock->quantity < $validated['quantity']) {
                return response()->json(['message' => 'Недостаточно товара на складе'], 422);
            }

            // Создание записи движения
            $movement = StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => Auth::id(),
                'type' => StockMovement::TYPE_WRITE_OFF,
                'quantity' => $validated['quantity'],
                'from_warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
            ]);

            // Уменьшение остатка на складе
            $stock->decrement('quantity', $validated['quantity']);

            $movement->load(['user', 'fromWarehouse', 'toWarehouse']);

            return response()->json(['movement' => $movement]);
        });
    }

    /** История движений ингредиента */
    public function history(Ingredient $ingredient): JsonResponse
    {
        $movements = StockMovement::where('ingredient_id', $ingredient->id)
            ->with(['user', 'fromWarehouse', 'toWarehouse'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['movements' => $movements]);
    }
}
