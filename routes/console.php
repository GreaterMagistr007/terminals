<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Окно 09:00-00:00 по Иркутску: последний запуск — 23:00, в 00:00 уже отсекается.
Schedule::command('water:check')
    ->hourly()
    ->timezone('Asia/Irkutsk')
    ->between('09:00', '23:59');
