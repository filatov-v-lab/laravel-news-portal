<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Статистика для модераторов. Для проверки — каждую минуту
// (на «боевом» сайте заменить на ->dailyAt('18:00')).
// Запуск планировщика: php artisan schedule:run  (или schedule:work).
Schedule::command('stats:daily')->everyMinute();
