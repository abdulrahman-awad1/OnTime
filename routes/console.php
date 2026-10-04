<?php

use App\Jobs\CancelExpiredAppointments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CancelExpiredAppointments)->everyFiveMinutes();
