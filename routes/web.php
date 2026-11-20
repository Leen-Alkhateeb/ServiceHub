<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;


    

 
        



// admin 
Route::get('/AdminDashboard',[AdminDashboardController::class , 'index'])->name('admin.dashboard');
// vindoer

// customer

        Route::get('/dashboard',     [ProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings',      [ProfileController::class, 'bookings'])->name('bookings');
        Route::get('/favorites',     [ProfileController::class, 'favorites'])->name('favorites');
        Route::get('/notifications', [ProfileController::class, 'notifications'])->name('notifications');
        Route::get('/settings',      [ProfileController::class, 'settings'])->name('settings');
        Route::put('/settings',      [ProfileController::class, 'updateSettings'])->name('settings.update');

