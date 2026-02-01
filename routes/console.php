<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Sincronización de despachos: Cada domingo a las 18:00 (semana cerrada)
Schedule::command('dispatch:sync-containers')
    ->weeklyOn(0, '18:00') // 0 = Domingo
    ->withoutOverlapping()
    ->runInBackground()
    ->emailOutputOnFailure('desarrollo@paitadev.cloud') // Opcional: enviar email en caso de fallo
    ->onSuccess(function () {
        \Log::info('✅ Sincronización semanal de despachos completada exitosamente', [
            'executed_at' => now()->format('Y-m-d H:i:s'),
            'week' => now()->subWeek()->weekOfYear
        ]);
    })
    ->onFailure(function () {
        \Log::error('❌ Falló la sincronización semanal de despachos', [
            'executed_at' => now()->format('Y-m-d H:i:s'),
            'week' => now()->subWeek()->weekOfYear
        ]);
    });
