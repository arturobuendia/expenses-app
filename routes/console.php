<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// La demo pública acepta escrituras, así que se reinicia una vez por semana.
Schedule::command('demo:refresh')->weeklyOn(1, '04:00');
