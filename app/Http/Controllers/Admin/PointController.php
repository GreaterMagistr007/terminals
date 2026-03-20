<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendistaTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointController extends Controller
{
    /** Список всех точек с настройками */
    public function index(): JsonResponse
    {
        $terminals = VendistaTerminal::with('settings')
            ->orderBy('comment')
            ->get();

        return response()->json(['terminals' => $terminals]);
    }

    /** Одна точка с настройками */
    public function show(VendistaTerminal $terminal): JsonResponse
    {
        $terminal->load('settings');

        return response()->json(['terminal' => $terminal]);
    }

    /** Обновление настроек точки */
    public function update(Request $request, VendistaTerminal $terminal): JsonResponse
    {
        $validated = $request->validate([
            'hidden' => ['required', 'boolean'],
            'uses_water' => ['required', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $terminal->settings()->updateOrCreate(
            ['vendista_terminal_id' => $terminal->id],
            $validated,
        );

        $terminal->load('settings');

        return response()->json(['terminal' => $terminal]);
    }
}
