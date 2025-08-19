<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Debug uniquement en local et pour les routes API Platform
        if (app()->environment('local') && str_starts_with($request->path(), 'api/')) {
            
            Log::info('=== API DEBUG START ===', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'parsed_input' => $request->all(),
                'route_params' => $request->route()?->parameters() ?? [],
            ]);
        }

        $response = $next($request);

        if (app()->environment('local') && str_starts_with($request->path(), 'api/')) {
            Log::info('=== API DEBUG RESPONSE ===', [
                'status' => $response->getStatusCode(),
                'response_headers' => $response->headers->all(),
                'response_content' => $response->getContent(),
            ]);
        }

        return $response;
    }
}