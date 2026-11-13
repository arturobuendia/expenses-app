<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limits how much a single IP can use the public demo.
 *
 * The demo accepts writes and is rebuilt once a week, so every IP gets a
 * request and a write budget per minute. Going over it bans the IP for a
 * while, reads included.
 *
 * Counters live in the real cache on purpose: if they lived in the demo
 * database, the weekly rebuild would reset them. That is why this middleware
 * runs before DemoMode swaps the default connection.
 */
class DemoThrottle
{
    /**
     * Requests that do not consume the write budget.
     */
    private const READS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasCookie(config('demo.cookie'))) {
            return $next($request);
        }

        $ip = (string) $request->ip();
        $banMinutes = (int) config('demo.throttle.ban_minutes');

        if ($banMinutes > 0 && Cache::has($this->banKey($ip))) {
            abort(429, 'Tu IP está bloqueada temporalmente por uso abusivo de la demo.');
        }

        $budget = [
            ['demo:requests:'.$ip, (int) config('demo.throttle.requests_per_minute')],
        ];

        if (! in_array($request->method(), self::READS, true)) {
            $budget[] = ['demo:writes:'.$ip, (int) config('demo.throttle.writes_per_minute')];
        }

        foreach ($budget as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                if ($banMinutes > 0) {
                    Cache::put($this->banKey($ip), true, now()->addMinutes($banMinutes));
                }

                abort(429, 'Has alcanzado el límite de la demo. Vuelve a intentarlo en unos minutos.');
            }
        }

        foreach ($budget as [$key]) {
            RateLimiter::hit($key, 60);
        }

        return $next($request);
    }

    /**
     * Cache key of the temporary IP ban.
     */
    private function banKey(string $ip): string
    {
        return 'demo:ban:'.$ip;
    }
}
