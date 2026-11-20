<?php

use App\Http\Controllers\AccountDetailsController;
use App\Http\Controllers\AccountPictureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MaintenanceAssistantController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\VendorApplicationController;
use App\Http\Controllers\VendorPagesController;
use App\Http\Controllers\VendorReviewController;
use App\Http\Controllers\VendorServiceRequestController;
use App\Http\Controllers\VendorWalletController;
use App\Http\Middleware\ActiveAdmin;
use App\Http\Middleware\ActiveVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::get('/', HomeController::class)->name('home');
    Route::redirect('/index', '/');
    Route::get('/categories', [HomeController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{category}', [HomeController::class, 'category'])->name('categories.show');
    Route::get('/vendors', [HomeController::class, 'vendors'])->name('vendors.index');
    Route::view('/faq', 'pages.public.faq')->name('faq');
    Route::get('/profile-pictures/{account}', [AccountPictureController::class, 'show'])->name('account.picture.show');
    Route::post('/maintenance-assistant', MaintenanceAssistantController::class)
        ->middleware('throttle:8,1')->name('maintenance.assistant');
    Route::get('/become-vendor', [VendorApplicationController::class, 'show'])->name('vendor.apply');
});

Auth::routes();

Route::name('vendor.')->group(function () {
    Route::get('/vendor-profile', [VendorPagesController::class, 'profile'])->name('profile');
    Route::get('/vendor-services/{offer}/reviews', [VendorPagesController::class, 'serviceReviews'])->name('service.reviews');
    Route::get('/vendor-services/{offer}/image', [VendorPagesController::class, 'serviceImage'])->name('service.image');
    Route::get('/vendor-portfolio/{project}', [PortfolioController::class, 'show'])->name('portfolio.show');
    Route::get('/vendor-portfolio-images/{image}', [PortfolioController::class, 'image'])->name('portfolio.image');
});

Route::middleware('auth')->group(function () {
    Route::patch('/my-profile', AccountDetailsController::class)->name('account.details.update');
    Route::post('/my-profile/photo', [AccountPictureController::class, 'update'])->middleware('throttle:10,1')->name('account.picture.update');
    Route::delete('/my-profile/photo', [AccountPictureController::class, 'destroy'])->middleware('throttle:10,1')->name('account.picture.destroy');
    Route::get('/my-profile', function (Request $request) {
        $account = $request->user();

        if ($account->role === 'vendor' && $account->status === 'active' && $account->vendorProfile()->exists()) {
            return redirect()->route('vendor.profile');
        }

        return view('pages.public.account', compact('account'));
    })->name('account.profile');

    Route::post('/become-vendor', [VendorApplicationController::class, 'store'])->middleware('throttle:10,1');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    });

    Route::middleware(ActiveVendor::class)->name('vendor.')->group(function () {
        Route::get('/vendor-wallet', VendorWalletController::class)->name('wallet');
        Route::get('/vendor-reviews', [VendorReviewController::class, 'index'])->name('reviews');
        Route::post('/vendor-reviews/{booking}', [VendorReviewController::class, 'store'])->name('reviews.store');
        Route::patch('/vendor-reviews/{booking}', [VendorReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/vendor-reviews/{booking}', [VendorReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/vendor-portfolio', [PortfolioController::class, 'store'])->middleware('throttle:10,1')->name('portfolio.store');
        Route::patch('/vendor-portfolio/{project}', [PortfolioController::class, 'update'])->middleware('throttle:10,1')->name('portfolio.update');
        Route::delete('/vendor-portfolio/{project}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');
        Route::delete('/vendor-portfolio-images/{image}', [PortfolioController::class, 'destroyImage'])->name('portfolio.image.destroy');
        Route::post('/vendor-service-requests', [VendorServiceRequestController::class, 'store'])->middleware('throttle:10,1')->name('service-requests.store');
        Route::post('/vendor-service-suggestions', [VendorServiceRequestController::class, 'suggest'])->middleware('throttle:10,1')->name('service-suggestions.store');
        Route::patch('/vendor-service-requests/{offer}', [VendorServiceRequestController::class, 'update'])->name('service-requests.update');
        Route::delete('/vendor-service-requests/{offer}', [VendorServiceRequestController::class, 'destroy'])->name('service-requests.destroy');
    });

    Route::middleware(ActiveAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/vendor-service-requests', [VendorServiceRequestController::class, 'index'])->name('service-requests.index');
        Route::post('/vendor-service-requests/{offer}', [VendorServiceRequestController::class, 'decide'])->name('service-requests.decide');
    });
});
