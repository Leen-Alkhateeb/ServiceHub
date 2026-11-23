<?php
use App\Http\Controllers\AccountDetailsController;
use App\Http\Controllers\AccountPictureController;
use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminCancellationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\AdminServicesController;
use App\Http\Controllers\AdminVendorApplicationController;
use App\Http\Controllers\AdminWalletController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MaintenanceAssistantController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorApplicationController;
use App\Http\Controllers\VendorAvailabilityController;
use App\Http\Controllers\VendorPagesController;
use App\Http\Controllers\VendorReviewController;
use App\Http\Controllers\VendorServiceRequestController;
use App\Http\Controllers\VendorWalletController;
use App\Http\Middleware\ActiveAdmin;
use App\Http\Middleware\ActiveVendor;
use App\Models\User;
use App\Services\AccountHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminServiceApprovalController;







Auth::routes();








Route::get('/', HomeController::class)
    ->name('home');

Route::redirect('/index', '/');



Route::get('/categories', [HomeController::class, 'categories'])
    ->name('categories.index');

Route::get('/categories/{category}', [HomeController::class, 'category'])
    ->name('categories.show');



Route::get('/vendors', [HomeController::class, 'vendors'])
    ->name('vendors.index');

Route::get('/providers/{vendor}', function (User $vendor) {
    return redirect()->route('vendor.profile', [
        'vendor' => $vendor->id,
    ]);
})->name('provider.show');



Route::get('/services', [MarketController::class, 'index'])
    ->name('market.index');

Route::get('/services/{vendorService}', [MarketController::class, 'show'])
    ->name('market.show');



Route::view('/faq', 'pages.public.faq')
    ->name('faq');
Route::view('/about', 'pages.public.about')->name('about');
Route::view('/how-it-works', 'pages.public.how-it-works')->name('how-it-works');
Route::view('/contact', 'pages.public.contact')->name('contact');
Route::redirect('/forgot-password', '/password/reset');
Route::redirect('/booking', '/services');
Route::get('/service-details', function (Request $request) {
    $offer = \App\Models\VendorService::publiclyVisible()->find($request->integer('id'));

    return $offer ? redirect()->route('market.show', $offer) : redirect()->route('market.index');
});

Route::get('/profile-pictures/{account}', [AccountPictureController::class, 'show'])
    ->name('account.picture.show');



Route::post('/maintenance-assistant', MaintenanceAssistantController::class)
    ->middleware('throttle:8,1')
    ->name('maintenance.assistant');



Route::get('/become-vendor', [VendorApplicationController::class, 'show'])
    ->name('vendor.apply');








Route::name('vendor.')->group(function () {

    Route::get('/vendor-profile', [VendorPagesController::class, 'profile'])
        ->name('profile');

    Route::get('/vendor-services/{offer}/reviews', [VendorPagesController::class, 'serviceReviews'])
        ->name('service.reviews');

    Route::get('/vendor-services/{offer}/image', [VendorPagesController::class, 'serviceImage'])
        ->name('service.image');

    Route::get('/vendor-portfolio/{project}', [PortfolioController::class, 'show'])
        ->name('portfolio.show');

    Route::get('/vendor-portfolio-images/{image}', [PortfolioController::class, 'image'])
        ->name('portfolio.image');

});








Route::middleware('auth')->group(function () {


    Route::get('/users/{user}/profile', [\App\Http\Controllers\UserPublicProfileController::class, 'show'])
        ->name('users.profile.show');








    Route::name('customer.')->group(function () {
        Route::get('/customer-reviews', [ProfileController::class, 'reviews'])->name('reviews');
        Route::get('/customer-messages', [ProfileController::class, 'messages'])->name('messages');

        Route::get('/dashboard', [ProfileController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/bookings', [ProfileController::class, 'bookings'])
            ->name('bookings');

        Route::get('/favorites', [ProfileController::class, 'favorites'])
            ->name('favorites');

        Route::get('/customer-notifications', [ProfileController::class, 'notifications'])
            ->name('notifications');

        Route::get('/settings', [ProfileController::class, 'settings'])
            ->name('settings');

        Route::put('/settings', [ProfileController::class, 'updateSettings'])
            ->name('settings.update');

    });








    Route::redirect('/customer-dashboard', '/dashboard');
    Route::redirect('/customer-bookings', '/bookings');
    Route::redirect('/customer-favorites', '/favorites');
    Route::redirect('/customer-settings', '/settings');
    Route::post('/services/{vendorService}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::post('/services/{vendorService}/book', [BookingProfileController::class, 'store'])->name('booking.store');
    Route::post('/bookings/{booking}/cancel', [BookingProfileController::class, 'cancel'])->name('booking.cancel');
    Route::post('/bookings/{booking}/completion', [\App\Http\Controllers\BookingCompletionController::class, 'confirm'])->name('booking.completion');
    Route::post('/bookings/{booking}/review', [\App\Http\Controllers\BookingCompletionController::class, 'review'])->name('booking.review');
    Route::post('/bookings/{booking}/objection', [\App\Http\Controllers\BookingCompletionController::class, 'object'])->name('booking.objection');
    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'store'])->name('payment.store');
    Route::get('/bookings/{booking}/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/bookings/{booking}/invoice', [PaymentController::class, 'invoice'])->withTrashed()->name('payment.invoice');
    Route::redirect('/booking-details', '/my-profile?tab=bookings');
    Route::get('/AdminDashboard', [AdminDashboardController::class, 'index'])->middleware(ActiveAdmin::class);

    Route::middleware(['auth', ActiveVendor::class])
        ->name('vendor.')
        ->group(function () {
            Route::get('/vendor-availability', [VendorAvailabilityController::class, 'index'])->name('availability.index');
            Route::post('/vendor-availability', [VendorAvailabilityController::class, 'store'])->name('availability.store');
            Route::patch('/vendor-availability/{availability}', [VendorAvailabilityController::class, 'update'])->name('availability.update');
            Route::delete('/vendor-availability/{availability}', [VendorAvailabilityController::class, 'destroy'])->name('availability.destroy');
            Route::resource('vendor-bookings', BookingController::class)->only(['index', 'update'])->parameters(['vendor-bookings' => 'booking']);
        });

    Route::patch('/my-profile', AccountDetailsController::class)->name('account.details.update');
    Route::post('/contact', [ProfileController::class, 'sendMessage'])->middleware('throttle:6,1')->name('contact.store');
    Route::post('/my-profile/photo', [AccountPictureController::class, 'update'])->middleware('throttle:10,1')->name('account.picture.update');
    Route::delete('/my-profile/photo', [AccountPictureController::class, 'destroy'])->middleware('throttle:10,1')->name('account.picture.destroy');
    Route::get('/my-profile', function (Request $request, AccountHistory $history) {

        $account = $request->user();
        $tab = $request->query('tab', 'overview');

        if (
            $account->role === 'vendor'
            && $account->status === 'active'
            && $account->vendorProfile()->exists()
        ) {
            return redirect()->route(
                'vendor.profile',
                $request->has('tab') && is_string($tab)
                ? ['tab' => $tab]
                : []
            );
        }

        abort_unless(
            in_array($tab, ['overview', 'bookings', 'payments'], true),
            404
        );

        $historyData = $history->forTab($account, $tab, $request);

        return view('pages.public.account', compact('account', 'tab') + $historyData);

    })->name('account.profile');








    Route::post('/become-vendor', [VendorApplicationController::class, 'store'])
        ->middleware('throttle:10,1');








    Route::prefix('notifications')
        ->name('notifications.')
        ->group(function () {

            Route::get('/', [NotificationController::class, 'index'])
                ->name('index');

            Route::post('/{notification}/read', [NotificationController::class, 'read'])
                ->name('read');

        });








    Route::middleware(ActiveVendor::class)
        ->name('vendor.')
        ->group(function () {

            Route::get('/vendor-dashboard', [VendorPagesController::class, 'dashboard'])->name('dashboard');
            Route::redirect('/vendor-settings', '/vendor-profile')->name('settings.redirect');
            Route::redirect('/vendor-services', '/vendor-profile?tab=services')->name('services.redirect');
            Route::redirect('/vendor-service-form', '/vendor-profile?tab=services')->name('service-form.redirect');
            Route::redirect('/vendor-portfolio', '/vendor-profile?tab=portfolio')->name('portfolio.redirect');
            Route::redirect('/vendor-portfolio-form', '/vendor-profile?tab=portfolio')->name('portfolio-form.redirect');
            Route::get('/vendor-wallet', VendorWalletController::class)
                ->name('wallet');



            Route::get('/vendor-reviews', [VendorReviewController::class, 'index'])
                ->name('reviews');

            Route::post('/vendor-reviews/{booking}', [VendorReviewController::class, 'store'])
                ->name('reviews.store');

            Route::patch('/vendor-reviews/{booking}', [VendorReviewController::class, 'update'])
                ->name('reviews.update');

            Route::delete('/vendor-reviews/{booking}', [VendorReviewController::class, 'destroy'])
                ->name('reviews.destroy');



            Route::post('/vendor-portfolio', [PortfolioController::class, 'store'])
                ->middleware('throttle:10,1')
                ->name('portfolio.store');

            Route::patch('/vendor-portfolio/{project}', [PortfolioController::class, 'update'])
                ->middleware('throttle:10,1')
                ->name('portfolio.update');

            Route::delete('/vendor-portfolio/{project}', [PortfolioController::class, 'destroy'])
                ->name('portfolio.destroy');

            Route::delete('/vendor-portfolio-images/{image}', [PortfolioController::class, 'destroyImage'])
                ->name('portfolio.image.destroy');



            Route::post('/vendor-service-requests', [VendorServiceRequestController::class, 'store'])
                ->middleware('throttle:10,1')
                ->name('service-requests.store');

            Route::post('/vendor-service-suggestions', [VendorServiceRequestController::class, 'suggest'])
                ->middleware('throttle:10,1')
                ->name('service-suggestions.store');

            Route::patch('/vendor-service-requests/{offer}', [VendorServiceRequestController::class, 'update'])
                ->name('service-requests.update');

            Route::delete('/vendor-service-requests/{offer}', [VendorServiceRequestController::class, 'destroy'])
                ->name('service-requests.destroy');

        });










    Route::middleware(ActiveAdmin::class)
        ->name('admin.')
        ->group(function () {





            Route::prefix('admin')->group(function () {
                Route::get('/dashboard', [AdminDashboardController::class, 'index'])
                    ->name('dashboard');

                Route::get('/Users', [AdminUsersController::class, 'index'])
                    ->name('admin.Users');

                Route::delete('/deleteUser/{user}', [AdminUsersController::class, 'destroy'])
                    ->name('users.destroy');

                Route::put('/users/{user}/toggle-status', [AdminUsersController::class, 'toggleStatus'])
                    ->name('users.toggle-status');

                Route::get('/admin-services', [AdminServicesController::class, 'adminServices'])
                    ->name('admin-services');

                Route::get('/admin-services/{service}/image', [AdminServicesController::class, 'image'])
                    ->name('services.image');

                Route::post('/addServices', [AdminServicesController::class, 'addServices'])
                    ->name('addServices');

                Route::put('/admin-services/{service}', [AdminServicesController::class, 'editServices'])
                    ->name('editServices');

                Route::put('/admin-services/toggle/{service}', [AdminServicesController::class, 'toggleServices'])
                    ->name('toggleServices');

                Route::delete('/admin-services/{service}', [AdminServicesController::class, 'deleteServices'])
                    ->name('deleteServices');

                Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
                Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->withTrashed()->name('bookings.show');
                Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');


                Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
                Route::get('/messages/{message}', [AdminMessageController::class, 'show'])->name('messages.show');
                Route::patch('/messages/{message}', [AdminMessageController::class, 'update'])->name('messages.update');


                Route::get('/admin-reviews', [AdminReviewController::class, 'index'])
                    ->name('reviews.index');

                Route::put('/admin-reviews/{id}', [AdminReviewController::class, 'update'])
                    ->name('reviews.update');

                Route::delete('/admin-reviews/{id}', [AdminReviewController::class, 'destroy'])
                    ->name('reviews.destroy');


                Route::get('/admin-cancellations', [AdminCancellationController::class, 'index'])
                    ->name('cancellations.index');

                Route::delete('/admin-cancellations/{id}', [AdminCancellationController::class, 'destroy'])
                    ->name('cancellations.destroy');

                Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
                Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

                Route::get('/admin-wallet', [AdminDashboardController::class, 'wallet'])->name('wallet.index');

                Route::get('/admin-applications', [AdminVendorApplicationController::class, 'index'])
                    ->name('admin-applications');

                Route::get('/admin-applications/{application}/permit', [AdminVendorApplicationController::class, 'permit'])
                    ->name('applications.permit');

                Route::get('/admin-refunds', [\App\Http\Controllers\AdminRefundsController::class, 'index'])
                    ->name('refunds.index');

                Route::post('/admin-refunds/{refund}', [\App\Http\Controllers\AdminRefundsController::class, 'decide'])
                    ->name('refunds.decide');

                Route::put(
                    '/admin-applications/{id}/approved',
                    [AdminVendorApplicationController::class, 'approved']
                )->name('admin-applications.approved');

                Route::put(
                    '/admin-applications/{id}/rejected',
                    [AdminVendorApplicationController::class, 'rejected']
                )->name('admin-applications.rejected');


                Route::get(
                    '/vendor-service-requests',
                    [AdminServiceApprovalController::class, 'index']
                )->name('service-requests.index');
                Route::post('/vendor-service-requests/{offer}', [AdminServiceApprovalController::class, 'decide'])
                    ->name('service-requests.decide');


                Route::get('/categories', [CategoryController::class, 'index'])
                    ->name('categories');
                Route::get('/categories/create', [CategoryController::class, 'create'])
                    ->name('categories.create');
                Route::post('/categories', [CategoryController::class, 'store'])
                    ->name('categories.store');
                Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
                    ->name('categories.edit');
                Route::put('/categories/{category}', [CategoryController::class, 'update'])
                    ->name('categories.update');
                Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
                    ->name('categories.destroy');


            });
        });
});


