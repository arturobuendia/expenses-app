<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs the current request against the public demo database.
 *
 * The demo is opt-in: it only activates when the demo cookie is present. When
 * it is, every model query in the request is redirected to the "demo"
 * connection and the visitor is authenticated for this request only.
 *
 * DemoMode must run before `auth` (see the middleware priority list in
 * bootstrap/app.php) so that `auth` and `verified` see the one-time user.
 */
class DemoMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasCookie(config('demo.cookie'))) {
            return $next($request);
        }

        $database = config('database.connections.demo.database');

        if (! is_string($database) || ! is_file($database)) {
            abort(503, 'La demo todavía se está preparando. Vuelve a intentarlo en unos minutos.');
        }

        $originalDefault = config('database.default');

        config(['database.default' => 'demo']);

        try {
            $user = User::on('demo')->where('email', config('demo.user.email'))->first();

            if ($user === null) {
                abort(503, 'La demo todavía se está preparando. Vuelve a intentarlo en unos minutos.');
            }

            // Autenticación de un solo request: NO escribe la llave de login en la
            // sesión, así que el visitante no puede quedar autenticado en la app real.
            Auth::onceUsingId($user->getKey());

            $request->attributes->set('demo', true);

            return $next($request);
        } finally {
            // Nunca dejamos la conexión global apuntando a la demo.
            config(['database.default' => $originalDefault]);
        }
    }
}
