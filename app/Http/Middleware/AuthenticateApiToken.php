<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->bearerToken();
        if ($header === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! (bool) app(SettingsService::class)->get('api.enabled', false)) {
            return response()->json(['message' => 'API is disabled in Settings.'], 503);
        }

        $token = ApiToken::findActiveByPlain($header);
        if (! $token || ! $token->user || ! $token->user->is_active) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        auth()->setUser($token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
