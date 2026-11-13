<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DemoController extends Controller
{
    /**
     * Marca la visita como demo y entra al dashboard.
     */
    public function start(): RedirectResponse
    {
        $database = config('database.connections.demo.database');

        if (! is_string($database) || ! is_file($database)) {
            abort(503, 'La demo todavía se está preparando. Vuelve a intentarlo en unos minutos.');
        }

        return redirect()
            ->route('dashboard')
            ->withCookie(cookie(config('demo.cookie'), '1', config('demo.lifetime')));
    }

    /**
     * Cierra la demo y regresa a la portada.
     */
    public function stop(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->withoutCookie(config('demo.cookie'));
    }
}
