<?php

namespace App\Console\Commands;

use App\Models\VendistaTerminal;
use App\Models\VendistaTransaction;
use App\Services\TelegramService;
use App\Services\VendistaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WaterCheck extends Command
{
    protected $signature = 'water:check';
    protected $description = 'Проверка уровня воды в аппаратах и уведомление в Telegram-группу';

    private const BOTTLE_VOLUME_ML = 18900;
    private const WATER_PER_CUP_ML = 340;
    private const LOW_WATER_THRESHOLD = 0.3;

    public function handle(VendistaService $vendistaService, TelegramService $telegramService): int
    {
        // Обновить транзакции из Vendista API
        try {
            $vendistaService->fetchLatestTransactions();
        } catch (\Throwable $e) {
            Log::error('water:check — ошибка загрузки транзакций', ['error' => $e->getMessage()]);
            $this->error("Ошибка загрузки транзакций: {$e->getMessage()}");
        }

        // Терминалы, использующие воду и не скрытые
        $terminals = VendistaTerminal::with(['settings', 'latestVisit'])
            ->withMax('serviceVisits', 'visited_at')
            ->whereHas('settings', fn ($q) => $q->where('uses_water', true)->where('hidden', false))
            ->get();

        $lowWaterTerminals = [];

        foreach ($terminals as $terminal) {
            $waterMain = $this->calculateWaterMain($terminal);

            if ($waterMain <= self::LOW_WATER_THRESHOLD) {
                $name = $terminal->comment ?? "Терминал #{$terminal->id}";
                $lowWaterTerminals[] = "{$name} — {$waterMain}";
                $this->warn("{$name}: вода {$waterMain}");
            }
        }

        if (empty($lowWaterTerminals)) {
            $this->info('Все аппараты в норме.');
            return self::SUCCESS;
        }

        // Отправка уведомления в группу
        $groupChatId = config('services.telegram.group_chat_id');
        if (empty($groupChatId)) {
            $this->warn('TELEGRAM_GROUP_CHAT_ID не задан, уведомление не отправлено.');
            return self::SUCCESS;
        }

        $text = "<b>Мало воды:</b>\n";
        foreach ($lowWaterTerminals as $line) {
            $text .= "\n{$line}";
        }

        $sent = $telegramService->sendMessage($groupChatId, $text);

        if ($sent) {
            $this->info('Уведомление отправлено в группу.');
        } else {
            $this->error('Не удалось отправить уведомление.');
        }

        return self::SUCCESS;
    }

    /** Расчёт уровня воды в основной бутылке (0..1) */
    private function calculateWaterMain(VendistaTerminal $terminal): float
    {
        $latestVisit = $terminal->latestVisit;
        if ($latestVisit === null) {
            return 0;
        }

        $mainMl = ($latestVisit->water_main ?? 0) * self::BOTTLE_VOLUME_ML;
        $spareMl = ($latestVisit->water_spare ?? 0) * self::BOTTLE_VOLUME_ML;

        // Продажи после последнего визита
        $lastVisitedAt = $terminal->service_visits_max_visited_at;
        $query = VendistaTransaction::where('term_id', $terminal->vendista_id)
            ->where('result', 1);

        if ($lastVisitedAt) {
            $query->where('time', '>', $lastVisitedAt);
        }

        $salesCount = $query->count();
        $usedMl = $salesCount * self::WATER_PER_CUP_ML;

        $remainingMain = $mainMl - $usedMl;

        // Если основная закончилась — расход переходит на запасную
        if ($remainingMain < 0) {
            $remainingMain = 0;
        }

        return round(min(1, max(0, $remainingMain / self::BOTTLE_VOLUME_ML)), 1);
    }
}
