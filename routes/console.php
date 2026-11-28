<?php

use App\Models\Booking;
use App\Services\InstantMarketplace;
use App\Services\InstantFinance;
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

Schedule::call(fn () => app(InstantMarketplace::class)->expire())
    ->name('instant:expire')->everyMinute()->withoutOverlapping()->when(fn () => config('instant.enabled'));
Schedule::call(fn () => app(InstantMarketplace::class)->notifyActiveMatches())
    ->name('instant:match')->everyThirtySeconds()->withoutOverlapping()->when(fn () => config('instant.enabled'));
Schedule::call(fn () => app(InstantFinance::class)->run())
    ->name('instant:settle')->everyMinute()->withoutOverlapping()->when(fn () => config('instant.enabled'));
Schedule::call(fn () => app(InstantMarketplace::class)->notifyDurationOverruns())
    ->name('instant:duration')->everyMinute()->withoutOverlapping()->when(fn () => config('instant.enabled'));

Schedule::call(function () {
    Booking::query()
        ->whereIn('status', ['approved', 'awaiting_payment'])
        ->where('payment_deadline', '<=', now())
        ->whereDoesntHave('payment')
        ->update(['status' => 'expired']);
})->name('bookings:expire-unpaid')->everyMinute()->withoutOverlapping();
