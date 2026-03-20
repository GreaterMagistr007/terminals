<?php

namespace App\Console\Commands;

use App\Services\VendistaService;
use Illuminate\Console\Command;

class VendistaFetchTransactions extends Command
{
    protected $signature = 'vendista:fetch-transactions';

    protected $description = 'Получение последних транзакций из Vendista API (инкрементальная синхронизация)';

    public function handle(VendistaService $vendistaService): int
    {
        $this->info('Получение последних транзакций из Vendista API...');

        $report = $vendistaService->fetchLatestTransactions(force: true);

        if ($report === null) {
            $this->error('Не удалось получить данные из Vendista API');
            return self::FAILURE;
        }

        $this->info(
            "Готово. Получено: {$report['fetched']}, "
            . "новых: {$report['inserted']}, "
            . "обновлено: {$report['updated']}, "
            . "без изменений: {$report['skipped']}"
        );

        return self::SUCCESS;
    }
}
