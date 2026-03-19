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
        $terminals = VendistaTerminal::orderBy('comment')->get();

        return response()->json(['terminals' => $terminals]);
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
