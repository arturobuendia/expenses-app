<?php

use App\Http\Middleware\DemoMode;
use App\Http\Middleware\DemoThrottle;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'demo' => DemoMode::class,
            'demo.throttle' => DemoThrottle::class,
        ]);

        // DemoMode autentica al visitante, así que debe correr antes que `auth`.
        // La lista de prioridad usa la interfaz (no la clase Authenticate), y sin
        // esto el pipeline mueve `auth` por encima de la demo y redirige al login.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: DemoMode::class,
        );

        // El throttle debe correr antes del cambio de conexión para que sus
        // contadores vivan en la caché real y no en la base de la demo.
        $middleware->prependToPriorityList(
            before: DemoMode::class,
            prepend: DemoThrottle::class,
        );

        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
