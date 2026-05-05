<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-approve draft POs at H-2 from expected delivery date, runs daily at 07:00
Schedule::command('app:approve-purchase-orders')->dailyAt('07:00');
