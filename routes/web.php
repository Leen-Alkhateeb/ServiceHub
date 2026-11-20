<?php

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ... (booking / payment / favorites كما هي)
    Route::get('/bookings/{booking}/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');

    Route::prefix('customer')->name('customer.')->group(function () {
        Route::get('/dashboard',     [ProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings',      [ProfileController::class, 'bookings'])->name('bookings');
        Route::get('/favorites',     [ProfileController::class, 'favorites'])->name('favorites');
        Route::get('/notifications', [ProfileController::class, 'notifications'])->name('notifications');
        Route::get('/settings',      [ProfileController::class, 'settings'])->name('settings');
        Route::put('/settings',      [ProfileController::class, 'updateSettings'])->name('settings.update');
    });
});