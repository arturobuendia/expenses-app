<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | The public demo runs the real application against its own SQLite
    | database (see the "demo" connection in config/database.php) so visitors
    | can create and delete records without touching production data.
    |
    | Visitors are authenticated per request with Auth::onceUsingId(), which
    | never writes to the session. That is deliberate: sessions live in the
    | real database, and the demo account may end up with the same id as the
    | owner account.
    |
    */

    'cookie' => 'demo_mode',

    // Minutes the demo session cookie stays valid.
    'lifetime' => 1440,

    'user' => [
        'name' => 'Invitado Demo',
        'email' => 'demo@example.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abuse protection
    |--------------------------------------------------------------------------
    |
    | La demo acepta escrituras y se reinicia una vez por semana, así que cada
    | IP tiene un cupo de peticiones y de escrituras por minuto. Al pasarse,
    | la IP queda bloqueada por `ban_minutes` (incluso para lecturas).
    | Los contadores viven en la caché real, no en la base de datos de la demo.
    |
    */

    'throttle' => [
        'requests_per_minute' => 120,
        'writes_per_minute' => 20,
        'ban_minutes' => 60,
    ],

];
