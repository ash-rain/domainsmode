<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Verify the Bearer token matches the configured API_KEY.
     * When API_KEY is empty (local dev / tests) the middleware is a no-op.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = env('API_KEY', '');

        if ($configured === '') {
            return $next($request);
        }

        $provided = $request->bearerToken();

        if ($provided === null || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
