<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:alerter-sessions-ouvertes-trop-longtemps')->hourly();
Schedule::command('app:alerter-stock-sous-seuil')->hourly();
