<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Apaga clipes com mais de 2 dias todo dia à meia-noite
Schedule::command('clips:purge')->dailyAt('00:00');
