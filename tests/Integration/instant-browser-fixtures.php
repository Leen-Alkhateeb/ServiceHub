<?php
require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!preg_match('/^servicehub_phase4_browser(?:_[a-z0-9]+)?$/D', Illuminate\Support\Facades\DB::connection()->getDatabaseName())
    || !app()->environment('testing')) exit(2);
if (isset($argv[1])) {
    $booking=App\Models\Booking::where('booking_type','instant')->sole();
    if (!in_array($booking->payment?->status, ['paid', 'partially_refunded'], true) || $booking->walletTransactions()->where('transaction_type','payout')->exists()) exit(3);
    $booking->forceFill(['objection_deadline'=>now()->subSecond(), 'payment_deadline'=>now()->subSecond()])->save();
    app(App\Services\InstantFinance::class)->run();
    echo $booking->walletTransactions()->where('transaction_type','payout')->sole()->amount.PHP_EOL;
    exit;
}
App\Models\User::factory()->create(['email'=>'customer.phase4@example.test','password'=>'Browser-Phase4-9!']);
App\Models\User::factory()->admin()->create(['email'=>'admin.phase4@example.test','password'=>'Browser-Phase4-9!']);
$vendor=App\Models\User::factory()->vendor()->create(['email'=>'vendor.phase4@example.test','password'=>'Browser-Phase4-9!']);
App\Models\VendorProfile::factory()->for($vendor)->create();
$service=App\Models\Service::factory()->create(['name'=>'فحص تجريبي للمرحلة الرابعة','supports_instant'=>true]);
App\Models\VendorService::factory()->for($vendor,'vendor')->for($service)->create(['instant_enabled'=>true]);
