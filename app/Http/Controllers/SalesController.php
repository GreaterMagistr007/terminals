<?php

namespace App\Http\Controllers;

use App\Models\VendistaTerminal;
use App\Models\VendistaTransaction;
use App\Services\VendistaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    /** Обновление транзакций из Vendista + возврат продаж за сегодня */
    public function refresh(VendistaService $vendistaService): JsonResponse
    {
        // Получаем последние транзакции, игнорируя ошибку
        $vendistaService->fetchLatestTransactions();

        return $this->today();
    }

    /** Продажи за сегодня по точкам */
    public function today(): JsonResponse
    {
        $todayStart = Carbon::now()->startOfDay();

        // Агрегация успешных транзакций за сегодня по терминалам
        $salesData = VendistaTransaction::where('result', 1)
            ->where('time', '>=', $todayStart)
            ->select(
                'term_id',
                DB::raw('SUM(sum) as total_sum'),
                DB::raw('COUNT(*) as total_count'),
            )
            ->groupBy('term_id')
            ->orderByDesc('total_sum')
            ->get();

        // Загрузка названий терминалов
        $terminalNames = VendistaTerminal::pluck('comment', 'vendista_id');

        $sales = $salesData->map(fn($row) => [
            'term_id' => $row->term_id,
            'terminal_name' => $terminalNames->get($row->term_id) ?? "Терминал #{$row->term_id}",
            'total_sum' => (int) $row->total_sum,
            'total_count' => (int) $row->total_count,
        ])->values();

        // Итого по всем точкам
        $totals = [
            'total_sum' => $sales->sum('total_sum'),
            'total_count' => $sales->sum('total_count'),
        ];

        return response()->json([
            'sales' => $sales,
            'totals' => $totals,
            'date' => $todayStart->toDateString(),
        ]);
    }
}
