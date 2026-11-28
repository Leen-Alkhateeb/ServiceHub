<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Middleware\ActiveAdmin;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('admin')->group(function () {

    Route::post('/login', [
        AdminAuthController::class,
        'login'
    ])->middleware('throttle:5,1');

    Route::middleware([
        'auth:sanctum',
        ActiveAdmin::class
    ])->group(function () {

        Route::get('/dashboard', [
            AdminDashboardController::class,
            'index'
        ]);

        Route::post('/logout', [
            AdminAuthController::class,
            'logout'
        ]);

    });

});
