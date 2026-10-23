<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionIdleTimeout
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) $this->settings->get('security.session_idle_minutes', config('session.lifetime', 120));

        if ($minutes > 0) {
            config(['session.lifetime' => $minutes]);
        }

        return $next($request);
    }
}
