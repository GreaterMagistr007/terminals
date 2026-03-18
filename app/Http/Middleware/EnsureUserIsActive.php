<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Аккаунт деактивирован.'], 403);
            }

            auth()->logout();
            return redirect('/login');
        }

        return $next($request);
    }
}
