<?php

namespace App\Console\Commands;

use App\Services\VendistaService;
use Illuminate\Console\Command;

class VendistaSyncTransactions extends Command
{
    protected $signature = 'vendista:sync-transactions
        {--from= : Начало периода (формат: 2026-03-01 или 2026-03-01T00:00:00)}
        {--to= : Конец периода (формат: 2026-03-20 или 2026-03-20T23:59:59)}';

    protected $description = 'Синхронизация транзакций из Vendista API';

    public function handle(VendistaService $vendistaService): int
    {
        $dateFrom = $this->option('from');
        $dateTo = $this->option('to');

        $this->info('Синхронизация транзакций из Vendista API...');

        $report = $vendistaService->syncTransactions($dateFrom, $dateTo, function (string $from, string $to, int $fetched) {
            $this->line("  {$from} — {$to}: {$fetched} транзакций");
        });

        if ($report === null) {
            $this->error('Не удалось получить данные из Vendista API');
            return self::FAILURE;
        }

        $this->info("Готово. Получено: {$report['fetched']}, записано: {$report['upserted']}");

        return self::SUCCESS;
    }
}
