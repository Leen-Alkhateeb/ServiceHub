<?php

use App\Services\VendorSettlement;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('wallet:release', function (VendorSettlement $settlement) {
    $this->info($settlement->release().' bookings credited.');
})->purpose('Credit paid, undisputed bookings after the 24-hour hold');

Schedule::command('wallet:release')->everyMinute()->withoutOverlapping();
