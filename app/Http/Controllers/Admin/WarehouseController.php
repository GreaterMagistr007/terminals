<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /** Список всех складов */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::orderBy('name')->get();

        return response()->json(['warehouses' => $warehouses]);
    }

    /** Создание склада */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json(['warehouse' => $warehouse], 201);
    }

    /** Обновление склада */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $warehouse->update($validated);

        return response()->json(['warehouse' => $warehouse]);
    }

    /** Удаление склада */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if ($warehouse->is_default) {
            return response()->json(['message' => 'Нельзя удалить склад по умолчанию'], 422);
        }

        $warehouse->delete();

        return response()->json(['message' => 'Склад удалён']);
    }

    /** Остатки на складе */
    public function stocks(Warehouse $warehouse): JsonResponse
    {
        $stocks = $warehouse->stocks()->with('ingredient')->get();

        return response()->json([
            'stocks' => $stocks,
            'warehouse' => $warehouse,
        ]);
    }
}
