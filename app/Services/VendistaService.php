<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VendistaService
{
    private const CACHE_TOKEN_KEY = 'vendista_api_token';

    private string $baseUrl;
    private string $login;
    private string $password;

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
