<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VendistaService;
use Illuminate\Http\JsonResponse;

class VendistaTransactionController extends Controller
{
    /** Принудительная синхронизация транзакций из Vendista API */
    public function sync(VendistaService $vendistaService): JsonResponse
    {
        $report = $vendistaService->syncTransactions();

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
