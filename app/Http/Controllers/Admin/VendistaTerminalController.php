<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendistaTerminal;
use App\Services\VendistaService;
use Illuminate\Http\JsonResponse;

class VendistaTerminalController extends Controller
{
    /** Список терминалов из локальной БД */
    public function index(): JsonResponse
    {
        $terminals = VendistaTerminal::with(['settings', 'ingredients'])
            ->withMax('serviceVisits', 'visited_at')
            ->orderBy('comment')
            ->get();

        return response()->json(['terminals' => $terminals]);
    }

    /** Один терминал по ID */
    public function show(VendistaTerminal $terminal): JsonResponse
    {
        $terminal->load([
            'settings',
            'ingredients',
            'latestVisit.ingredients' => function ($query) {
                $query->where('needed', '>', 0);
            },
            'latestVisit.ingredients.ingredient',
        ]);
        $terminal->loadMax('serviceVisits', 'visited_at');

        return response()->json(['terminal' => $terminal]);
    }

    /** Принудительная синхронизация терминалов из Vendista API */
    public function sync(VendistaService $vendistaService): JsonResponse
    {
        $report = $vendistaService->syncTerminals();

        if ($report === null) {
            return response()->json([
                'success' => false,
                'error' => 'Не удалось получить данные из Vendista API',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }
}
