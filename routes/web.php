<?php

use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;


// admin 
Route::get('/AdminDashboard',[AdminDashboardController::class , 'index'])->name('admin.dashboard');
// vindoer

// customer