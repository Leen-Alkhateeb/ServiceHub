<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\WalletTransaction;
use App\Observers\VendorFinancialObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Booking::observe(VendorFinancialObserver::class);
        Payment::observe(VendorFinancialObserver::class);
        RefundRequest::observe(VendorFinancialObserver::class);
        WalletTransaction::observe(VendorFinancialObserver::class);
        Paginator::useBootstrapFive();
    }
}
