<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendistaTerminal;
use App\Models\VendistaTransaction;
use App\Services\VendistaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VendistaTerminalController extends Controller
{
    /** Список терминалов из локальной БД */
    public function index(): JsonResponse
    {
        $terminals = VendistaTerminal::with([
            'settings',
            'ingredients',
            'latestVisit.ingredients.ingredient',
        ])
            ->withMax('serviceVisits', 'visited_at')
            ->orderBy('comment')
            ->get();

        // Подсчёт продаж с последнего обслуживания для каждого терминала
        $this->loadSalesSinceLastVisit($terminals);

        return response()->json(['terminals' => $terminals]);
    }

    /**
     * Подсчёт успешных транзакций после последнего визита для каждого терминала.
     */
    private function loadSalesSinceLastVisit($terminals): void
    {
        // Собираем vendista_id терминалов, у которых есть визиты
        $terminalDates = $terminals
            ->filter(fn ($t) => $t->service_visits_max_visited_at !== null)
            ->mapWithKeys(fn ($t) => [$t->vendista_id => $t->service_visits_max_visited_at]);

        if ($terminalDates->isEmpty()) {
            // Нет визитов — для всех считаем все транзакции
            $allCounts = VendistaTransaction::poured()
                ->select('term_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('term_id')
                ->pluck('cnt', 'term_id');

            foreach ($terminals as $terminal) {
                $terminal->setAttribute('sales_since_last_visit', $allCounts->get($terminal->vendista_id, 0));
            }

            return;
        }

        // Один запрос: все транзакции для нужных терминалов после самой ранней даты визита
        $earliestVisit = $terminalDates->min();
        $transactions = VendistaTransaction::poured()
            ->after($earliestVisit)
            ->select('term_id', 'time')
            ->get();

        // Подсчёт в PHP: для каждого терминала — транзакции после его visited_at
        $countsByTermId = [];
        foreach ($transactions as $tx) {
            $visitedAt = $terminalDates->get($tx->term_id);
            if ($visitedAt === null || $tx->time->gt($visitedAt)) {
                $countsByTermId[$tx->term_id] = ($countsByTermId[$tx->term_id] ?? 0) + 1;
            }
        }

        // Для терминалов без визитов — считаем все транзакции
        $noVisitTermIds = $terminals
            ->filter(fn ($t) => $t->service_visits_max_visited_at === null)
            ->pluck('vendista_id')
            ->toArray();

        $noVisitCounts = [];
        if (! empty($noVisitTermIds)) {
            $noVisitCounts = VendistaTransaction::poured()
                ->whereIn('term_id', $noVisitTermIds)
                ->select('term_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('term_id')
                ->pluck('cnt', 'term_id')
                ->toArray();
        }

        foreach ($terminals as $terminal) {
            $count = $terminal->service_visits_max_visited_at !== null
                ? ($countsByTermId[$terminal->vendista_id] ?? 0)
                : ($noVisitCounts[$terminal->vendista_id] ?? 0);
            $terminal->setAttribute('sales_since_last_visit', $count);
        }
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

        // Подсчёт продаж с последнего визита
        $lastVisitedAt = $terminal->service_visits_max_visited_at;
        $query = VendistaTransaction::poured()->forTerminal($terminal->vendista_id);

        if ($lastVisitedAt) {
            $query->after($lastVisitedAt);
        }

        $terminal->setAttribute('sales_since_last_visit', $query->count());

        return response()->json(['terminal' => $terminal]);
    }

    /** Обновление транзакций из Vendista + возврат данных терминалов */
    public function refresh(VendistaService $vendistaService): JsonResponse
    {
        // Принудительное обновление транзакций, игнорируя ошибку
        $vendistaService->fetchLatestTransactions();

        return $this->index();
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
