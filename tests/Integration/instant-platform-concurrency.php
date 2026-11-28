<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\VendorService;
use App\Services\InstantFinance;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = DB::connection();
$database = $connection->getDatabaseName();
if ($connection->getDriverName() !== 'mysql' || ! app()->environment('testing')
    || ! str_starts_with($database, 'servicehub_phase3_race_')) {
    throw new RuntimeException('Use a fresh migrated servicehub_phase3_race_ schema with APP_ENV=testing.');
}

$finance = app(InstantFinance::class);
if (($argv[1] ?? null) === 'worker') {
    echo DB::scalar('SELECT CONNECTION_ID()').PHP_EOL;
    flush();
    echo $finance->fundVendor(Booking::findOrFail($argv[2]), (int) $argv[3]) ? 'funded' : 'insufficient';
    exit;
}

if (Booking::withTrashed()->exists() || $finance->availableFils() !== 0) {
    throw new RuntimeException('This scenario starts with an empty schema.');
}
$vendorService = VendorService::factory()->create();
$admin = User::factory()->admin()->create();
$booking = static function (string $price) use ($vendorService): Booking {
    return Booking::factory()->for($vendorService)->create([
        'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => $price,
        'completed_at' => now()->subDays(2), 'payment_deadline' => now()->subDay(),
        'objection_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
    ]);
};
$paid = $booking('666.667');
$finance->collect($paid, $paid->customer_id);
$unpaid = [$booking('100.000'), $booking('100.000')];
$finance->run();
if ($finance->availableFils() !== 100000) throw new RuntimeException('Expected 100.000 of earned commission.');

$lock = 'now-platform-'.sha1($database);
if ((int) DB::scalar('SELECT GET_LOCK(?, 5)', [$lock]) !== 1) throw new RuntimeException('Could not hold the barrier.');
$workers = [];
try {
    $connections = [];
    foreach ($unpaid as $target) {
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $target->id, (string) $admin->id], [
            1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        if (! is_resource($process)) throw new RuntimeException('Could not start test process.');
        $workers[] = [$process, $pipes];
        $connections[] = (int) fgets($pipes[1]);
    }
    $waiting = 0;
    for ($attempt = 0; $attempt < 30 && $waiting < 2; $attempt++) {
        $waiting = DB::table('information_schema.PROCESSLIST')->whereIn('ID', $connections)
            ->where('INFO', 'like', '%GET_LOCK%')->count();
        if ($waiting < 2) usleep(50000);
    }
} finally {
    DB::scalar('SELECT RELEASE_LOCK(?)', [$lock]);
}
$results = [];
foreach ($workers as [$process, $pipes]) {
    $results[] = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($errors);
}
sort($results);
$payouts = DB::table('wallet_transactions')->whereIn('booking_id', array_column($unpaid, 'id'))->where('transaction_type', 'payout')->count();
printf("Waiting connections: %d; outcomes: %s; payouts: %d; available fils: %d\n", $waiting, implode(', ', $results), $payouts, $finance->availableFils());
if ($waiting !== 2 || $results !== ['funded', 'insufficient'] || $payouts !== 1 || $finance->availableFils() !== 15000) {
    throw new RuntimeException('Concurrent funding did not reconcile.');
}
