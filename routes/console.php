<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Apaga clipes com mais de 2 dias todo dia à meia-noite
Schedule::command('clips:purge')->dailyAt('00:00');

// Expira assinaturas vencidas todo dia à 00:05
Schedule::command('subscriptions:expire')->dailyAt('00:05');

// Notifica clientes com assinatura vencendo em 7 ou 1 dia (08:00)
Schedule::command('subscriptions:notify-expiring')->dailyAt('08:00');

// Snapshots agendados — verifica a cada minuto e captura câmeras com intervalo pendente
Schedule::command('cameras:snapshot')->everyMinute()->withoutOverlapping();

// Verifica câmeras offline/online a cada 5 minutos
Schedule::command('cameras:check-events')->everyFiveMinutes()->withoutOverlapping();
