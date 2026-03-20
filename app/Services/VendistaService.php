<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendistaTerminal;
use App\Models\VendistaTransaction;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VendistaService
{
    private const CACHE_TOKEN_KEY = 'vendista_api_token';
    private const CACHE_TERMINALS_SYNC_KEY = 'vendista_terminals_last_sync';
    private const TERMINALS_SYNC_INTERVAL_HOURS = 24;
    private const DEFAULT_TRANSACTIONS_DAYS = 30;

    private string $baseUrl;
    private string $login;
    private string $password;
    private bool $isSyncing = false;

    public function __construct(private TelegramService $telegramService)
    {
        $this->baseUrl = config('services.vendista.base_url');
        $this->login = config('services.vendista.api_login');
        $this->password = config('services.vendista.api_password');
    }

    /**
     * Авторизация в Vendista API.
     * При ошибке уведомляет главного админа в Telegram.
     */
    public function authenticate(): ?string
    {
        $response = Http::get("{$this->baseUrl}/token", [
            'Login' => $this->login,
            'Password' => $this->password,
        ]);

        if (!$response->successful()) {
            $this->handleAuthError("HTTP {$response->status()}: {$response->body()}");
            return null;
        }

        $data = $response->json();

        // 2FA — в текущей реализации не поддерживается
        if (!empty($data['verification_code_sended'])) {
            $this->handleAuthError('Vendista API требует 2FA, что не поддерживается');
            return null;
        }

        $token = $data['token'] ?? null;

        if ($token === null) {
            $error = $data['error'] ?? 'токен отсутствует в ответе';
            $this->handleAuthError($error);
            return null;
        }

        Cache::put(self::CACHE_TOKEN_KEY, $token);
        Log::info('Vendista API: токен получен');

        return $token;
    }

    /**
     * Выполнение запроса к Vendista API с автоматической авторизацией.
     * При 401 — переавторизация и повторная попытка.
     */
    public function request(string $method, string $path, array $params = []): ?array
    {
        // Автосинхронизация терминалов раз в сутки при любом запросе
        if (!$this->isSyncing) {
            $this->autoSyncTerminalsIfNeeded();
        }

        $token = Cache::get(self::CACHE_TOKEN_KEY);

        // Нет токена — авторизуемся
        if ($token === null) {
            $token = $this->authenticate();
            if ($token === null) {
                return null;
            }
        }

        $response = $this->sendRequest($method, $path, $params, $token);

        // 401 — токен истёк, переавторизация
        if ($response->status() === 401) {
            Log::info('Vendista API: токен истёк, переавторизация');
            Cache::forget(self::CACHE_TOKEN_KEY);

            $token = $this->authenticate();
            if ($token === null) {
                return null;
            }

            $response = $this->sendRequest($method, $path, $params, $token);
        }

        if (!$response->successful()) {
            Log::error('Vendista API: ошибка запроса', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    /** GET-запрос к Vendista API */
    public function get(string $path, array $params = []): ?array
    {
        return $this->request('GET', $path, $params);
    }

    /** POST-запрос к Vendista API */
    public function post(string $path, array $params = []): ?array
    {
        return $this->request('POST', $path, $params);
    }

    // -- Бизнес-методы --

    /**
     * Транзакции за период.
     * Без $termId — все терминалы. С $termId — конкретный.
     */
    public function getTransactions(
        string $dateFrom,
        string $dateTo,
        ?int $termId = null,
        int $page = 1,
        int $perPage = 50,
    ): ?array {
        $params = [
            'DateFrom' => $dateFrom,
            'DateTo' => $dateTo,
            'PageNumber' => $page,
            'ItemsOnPage' => $perPage,
        ];

        if ($termId !== null) {
            $params['TermId'] = $termId;
        }

        return $this->get('/transactions', $params);
    }

    /** Итоги продаж по автоматам */
    public function getSalesTotals(
        string $dateFrom,
        string $dateTo,
        ?int $machineId = null,
        int $page = 1,
        int $perPage = 50,
    ): ?array {
        $params = [
            'DateFrom' => $dateFrom,
            'DateTo' => $dateTo,
            'PageNumber' => $page,
            'ItemsOnPage' => $perPage,
        ];

        if ($machineId !== null) {
            $params['MachineId'] = $machineId;
        }

        return $this->get('/sales/totals', $params);
    }

    /** Детальный список продаж */
    public function getSalesList(
        string $dateFrom,
        string $dateTo,
        ?int $machineId = null,
        int $page = 1,
        int $perPage = 50,
    ): ?array {
        $params = [
            'DateFrom' => $dateFrom,
            'DateTo' => $dateTo,
            'PageNumber' => $page,
            'ItemsOnPage' => $perPage,
        ];

        if ($machineId !== null) {
            $params['MachineId'] = $machineId;
        }

        return $this->get('/sales/list', $params);
    }

    /** Сводный отчёт по терминалам */
    public function getCommonReport(
        string $dateFrom,
        string $dateTo,
        ?int $termId = null,
        int $page = 1,
        int $perPage = 50,
    ): ?array {
        $params = [
            'DateFrom' => $dateFrom,
            'DateTo' => $dateTo,
            'PageNumber' => $page,
            'ItemsOnPage' => $perPage,
        ];

        if ($termId !== null) {
            $params['TermId'] = $termId;
        }

        return $this->get('/reports/common', $params);
    }

    /**
     * Получение всех терминалов из Vendista API (с пагинацией).
     * @return array|null Массив терминалов или null при ошибке
     */
    public function fetchAllTerminals(): ?array
    {
        $allItems = [];
        $page = 1;
        $perPage = 100;

        do {
            $data = $this->get('/terminals', [
                'PageNumber' => $page,
                'ItemsOnPage' => $perPage,
            ]);

            if ($data === null || !($data['success'] ?? false)) {
                Log::error('Vendista API: ошибка получения терминалов', ['page' => $page]);
                return null;
            }

            $items = $data['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $totalCount = $data['items_count'] ?? 0;
            $page++;
        } while (count($allItems) < $totalCount && count($items) > 0);

        return $allItems;
    }

    /**
     * Синхронизация терминалов из Vendista API в локальную БД.
     * @return array{added: int, updated: int, deleted: int}|null Отчёт или null при ошибке
     */
    public function syncTerminals(): ?array
    {
        $this->isSyncing = true;

        try {
            $remoteTerminals = $this->fetchAllTerminals();
        } finally {
            $this->isSyncing = false;
        }

        if ($remoteTerminals === null) {
            return null;
        }

        $remoteById = collect($remoteTerminals)->keyBy('id');
        $localTerminals = VendistaTerminal::all()->keyBy('vendista_id');

        $added = 0;
        $updated = 0;

        foreach ($remoteById as $vendistaId => $remote) {
            $attributes = [
                'comment' => $remote['comment'] ?? null,
                'vendista_machine_id' => $remote['machine_id'] ?? null,
                'tid' => $remote['tid'] ?? null,
                'serial_number' => $remote['serial_number'] ?? null,
                'latitude' => $remote['latitude'] ?? null,
                'longitude' => $remote['longitude'] ?? null,
                'last_online_at' => $remote['last_online_time'] ?? null,
                'state' => $remote['state'] ?? 0,
            ];

            $local = $localTerminals->get($vendistaId);

            if ($local === null) {
                VendistaTerminal::create(['vendista_id' => $vendistaId, ...$attributes]);
                $added++;
            } else {
                // Проверяем, изменились ли данные
                $changed = false;
                foreach ($attributes as $key => $value) {
                    if ($key === 'last_online_at') {
                        $localValue = $local->last_online_at?->toIso8601String();
                        $remoteValue = $value;
                        if ($localValue !== $remoteValue) {
                            $changed = true;
                            break;
                        }
                    } elseif ((string) $local->$key !== (string) $value) {
                        $changed = true;
                        break;
                    }
                }

                if ($changed) {
                    $local->update($attributes);
                    $updated++;
                }
            }
        }

        // Удаление терминалов, которых больше нет в Vendista
        $remoteIds = $remoteById->keys()->toArray();
        $deleted = VendistaTerminal::whereNotIn('vendista_id', $remoteIds)->delete();

        Cache::put(self::CACHE_TERMINALS_SYNC_KEY, now());

        Log::info('Vendista: синхронизация терминалов', [
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
        ]);

        return compact('added', 'updated', 'deleted');
    }

    /**
     * Получение всех транзакций за период из Vendista API (с пагинацией).
     * @return array|null Массив транзакций или null при ошибке
     */
    public function fetchAllTransactions(string $dateFrom, string $dateTo): ?array
    {
        $allItems = [];
        $page = 1;
        $perPage = 100;

        do {
            $data = $this->getTransactions($dateFrom, $dateTo, page: $page, perPage: $perPage);

            if ($data === null || !($data['success'] ?? false)) {
                Log::error('Vendista API: ошибка получения транзакций', [
                    'page' => $page,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                ]);
                return null;
            }

            $items = $data['items'] ?? [];
            $allItems = array_merge($allItems, $items);
            $totalCount = $data['items_count'] ?? 0;
            $page++;
        } while (count($allItems) < $totalCount && count($items) > 0);

        return $allItems;
    }

    /**
     * Синхронизация транзакций из Vendista API в локальную БД.
     * При первом запуске загружает за последние N дней.
     * При последующих — от последней сохранённой транзакции.
     *
     * @return array{fetched: int, upserted: int}|null Отчёт или null при ошибке
     */
    public function syncTransactions(?string $dateFrom = null, ?string $dateTo = null): ?array
    {
        $this->isSyncing = true;

        try {
            if ($dateTo === null) {
                $dateTo = now()->format('Y-m-d\TH:i:s');
            }

            if ($dateFrom === null) {
                $lastTime = VendistaTransaction::max('time');
                $dateFrom = $lastTime
                    ? Carbon::parse($lastTime)->format('Y-m-d\TH:i:s')
                    : now()->subDays(self::DEFAULT_TRANSACTIONS_DAYS)->startOfDay()->format('Y-m-d\TH:i:s');
            }

            $remoteTransactions = $this->fetchAllTransactions($dateFrom, $dateTo);
        } finally {
            $this->isSyncing = false;
        }

        if ($remoteTransactions === null) {
            return null;
        }

        $fetched = count($remoteTransactions);

        if ($fetched === 0) {
            Log::info('Vendista: синхронизация транзакций — новых нет', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
            return ['fetched' => 0, 'upserted' => 0];
        }

        $rows = array_map(fn(array $item) => [
            'trans_id' => $item['id'],
            'term_id' => $item['term_id'],
            'sum' => $item['sum'] ?? 0,
            'time' => Carbon::parse($item['time']),
            'result' => $item['result'] ?? 0,
            'status' => $item['status'] ?? 0,
            'response_code' => $item['response_code'] ?? 0,
            'card_number' => $item['card_number'] ?? null,
            'reverse_id' => $item['reverse_id'] ?? 0,
            'reverse_time' => isset($item['reverse_time']) ? Carbon::parse($item['reverse_time']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $remoteTransactions);

        // SQLite: лимит 999 bind variables, 12 полей → max ~83 строки на чанк
        $chunkSize = config('database.default') === 'sqlite' ? 50 : 500;
        $upserted = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            VendistaTransaction::upsert(
                $chunk,
                ['trans_id'],
                ['sum', 'result', 'status', 'response_code', 'card_number', 'reverse_id', 'reverse_time', 'updated_at']
            );
            $upserted += count($chunk);
        }

        Log::info('Vendista: синхронизация транзакций', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'fetched' => $fetched,
            'upserted' => $upserted,
        ]);

        return compact('fetched', 'upserted');
    }

    /** Автосинхронизация терминалов, если прошло больше суток */
    private function autoSyncTerminalsIfNeeded(): void
    {
        $lastSync = Cache::get(self::CACHE_TERMINALS_SYNC_KEY);

        if ($lastSync !== null && now()->diffInHours($lastSync) < self::TERMINALS_SYNC_INTERVAL_HOURS) {
            return;
        }

        $this->syncTerminals();
    }

    /** Выполнение HTTP-запроса с токеном */
    private function sendRequest(string $method, string $path, array $params, string $token): Response
    {
        $params['token'] = $token;
        $url = "{$this->baseUrl}{$path}";

        return match (strtoupper($method)) {
            'POST' => Http::post($url, $params),
            default => Http::get($url, $params),
        };
    }

    /** Логирование ошибки авторизации и уведомление админа */
    private function handleAuthError(string $error): void
    {
        Log::error("Vendista API: ошибка авторизации — {$error}");

        $admin = User::where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereNotNull('telegram_id')
            ->first();

        if ($admin === null) {
            Log::warning('Vendista API: не найден активный админ с Telegram для уведомления');
            return;
        }

        $this->telegramService->sendMessage(
            $admin->telegram_id,
            "Vendista API: ошибка авторизации\n{$error}"
        );
    }
}
