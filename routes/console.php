<?php

use App\Jobs\CancelExpiredAppointments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CancelExpiredAppointments)->everyFiveMinutes();
Schedule::command('medicines:sync')->weeklyOn(5, '03:00');
