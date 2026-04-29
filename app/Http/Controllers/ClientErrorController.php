<?php

namespace App\Http\Controllers;

use App\Models\ClientErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientErrorController extends Controller
{
    /**
     * Запись клиентской ошибки в БД (для отладки sync-проблем оффлайн-очереди и пр.).
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:5000'],
            'context' => ['nullable', 'array'],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        ClientErrorLog::create([
            'user_id' => $request->user()?->id,
            'source' => $validated['source'],
            'message' => $validated['message'],
            'context' => $validated['context'] ?? null,
            'url' => $validated['url'] ?? null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->noContent();
    }

    /**
     * Список ошибок для админки (последние записи).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'source' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = ClientErrorLog::with('user:id,name')
            ->orderByDesc('id');

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $errors = $query->limit($request->integer('limit', 100))->get();

        return response()->json(['errors' => $errors]);
    }

    /**
     * Удаление одной записи (например, после разбора).
     */
    public function destroy(ClientErrorLog $clientError): Response
    {
        $clientError->delete();

        return response()->noContent();
    }

    /**
     * Очистить все записи (или старее N дней).
     */
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'older_than_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = ClientErrorLog::query();
        if ($request->filled('older_than_days')) {
            $query->where('created_at', '<', now()->subDays($request->integer('older_than_days')));
        }

        $deleted = $query->delete();

        return response()->json(['deleted' => $deleted]);
    }
}
