<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\VendorService;
use App\Services\InstantFinance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$database = DB::connection()->getDatabaseName();
if (! app()->environment('testing') || ! str_starts_with($database, 'servicehub_phase4_race_')) {
    throw new RuntimeException('Use a fresh servicehub_phase4_race_ schema with APP_ENV=testing.');
}
$finance = app(InstantFinance::class);
if (($argv[1] ?? null) === 'resolve') {
    echo DB::scalar('SELECT CONNECTION_ID()').PHP_EOL;
    flush();
    try {
        $finance->resolveExceptionalService(Booking::findOrFail($argv[2]), (int) $argv[3], (int) $argv[4], $argv[5], 'Concurrent decision', null);
        echo 'resolved';
    } catch (HttpExceptionInterface $error) {
        echo (string) $error->getStatusCode();
    }
    exit;
}
if (($argv[1] ?? null) === 'object') {
    echo DB::scalar('SELECT CONNECTION_ID()').PHP_EOL;
    flush();
    try {
        DB::transaction(function () use ($argv) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($argv[2]);
            abort_if($booking->refundRequest()->exists(), 409);
            $booking->refundRequest()->create(['submitted_by' => $booking->customer_id, 'reason' => 'Concurrent objection', 'status' => 'pending']);
        });
        echo 'filed';
    } catch (HttpExceptionInterface $error) {
        echo (string) $error->getStatusCode();
    }
    exit;
}
if (($argv[1] ?? null) === 'correct') {
    echo DB::scalar('SELECT CONNECTION_ID()').PHP_EOL;
    flush();
    try {
        $finance->reverseCommission((int) $argv[2], (int) $argv[3], '10.000', 'Concurrent test correction', (string) Str::uuid());
        echo 'corrected';
    } catch (HttpExceptionInterface $error) {
        echo (string) $error->getStatusCode();
    }
    exit;
}

if (Booking::withTrashed()->exists()) throw new RuntimeException('Expected an empty schema.');
$service = VendorService::factory()->create();
$admin = User::factory()->admin()->create();
$booking = Booking::factory()->for($service)->create([
    'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
    'completed_at' => now()->subDays(2), 'objection_deadline' => now()->subDay(),
    'payment_deadline' => now()->subDay(), 'settlement_status' => 'on_hold',
]);
$finance->collect($booking, $booking->customer_id);
$finance->run();
$commission = DB::table('wallet_transactions')->whereNull('booking_id')->where('transaction_type', 'commission')->first();
if (! $commission || $finance->availableFils() !== 15000) throw new RuntimeException('Expected a realized simulated commission of 15.000.');

$lock = 'now-platform-'.sha1($database);
if ((int) DB::scalar('SELECT GET_LOCK(?, 5)', [$lock]) !== 1) throw new RuntimeException('Could not hold the barrier.');
$workers = [];
try {
    $connections = [];
    for ($i = 0; $i < 2; $i++) {
        $process = proc_open([PHP_BINARY, __FILE__, 'correct', (string) $commission->id, (string) $admin->id], [
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
$corrections = DB::table('wallet_transactions')->where('related_transaction_id', $commission->id)->where('transaction_type', 'adjustment')->count();
printf("Waiting: %d; outcomes: %s; corrections: %d; available fils: %d\n", $waiting, implode(', ', $results), $corrections, $finance->availableFils());
if ($waiting !== 2 || $results !== ['409', 'corrected'] || $corrections !== 1 || $finance->availableFils() !== 5000) {
    throw new RuntimeException('Concurrent corrections did not reconcile.');
}

$objectionBooking = Booking::factory()->for($service)->create([
    'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
    'completed_at' => now(), 'objection_deadline' => now()->addDay(),
]);
DB::beginTransaction();
$workers = [];
try {
    Booking::whereKey($objectionBooking->id)->lockForUpdate()->firstOrFail();
    $connections = [];
    for ($i = 0; $i < 2; $i++) {
        $process = proc_open([PHP_BINARY, __FILE__, 'object', (string) $objectionBooking->id], [
            1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        if (! is_resource($process)) throw new RuntimeException('Could not start objection worker.');
        $workers[] = [$process, $pipes];
        $connections[] = (int) fgets($pipes[1]);
    }
    $waiting = 0;
    for ($attempt = 0; $attempt < 30 && $waiting < 2; $attempt++) {
        $waiting = DB::table('information_schema.PROCESSLIST')->whereIn('ID', $connections)
            ->where('INFO', 'like', '%for update%')->count();
        if ($waiting < 2) usleep(50000);
    }
} finally {
    DB::commit();
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
$count = $objectionBooking->refundRequest()->count();
printf("Objection waiters: %d; outcomes: %s; objections: %d\n", $waiting, implode(', ', $results), $count);
if ($waiting < 2 || $results !== ['409', 'filed'] || $count !== 1) {
    fwrite(STDERR, "Concurrent ordinary objections were not serialized.\n");
    exit(1);
}

$partial = Booking::factory()->for($service)->create([
    'booking_type' => 'instant', 'status' => 'completed', 'agreed_price' => '100.000',
    'completed_at' => now(), 'payment_deadline' => now()->addDay(), 'objection_deadline' => now()->addDay(),
]);
$finance->collect($partial, $partial->customer_id);
$case = $partial->cases()->make(['submitted_by' => $partial->customer_id, 'reason' => 'Partial service', 'status' => 'pending']);
$case->forceFill(['case_type' => 'partial_service'])->save();
if ((int) DB::scalar('SELECT GET_LOCK(?, 5)', [$lock]) !== 1) throw new RuntimeException('Could not hold the resolution barrier.');
$workers = [];
try {
    $connections = [];
    foreach (['40.000', '60.000'] as $amount) {
        $process = proc_open([PHP_BINARY, __FILE__, 'resolve', (string) $partial->id, (string) $case->id, (string) $admin->id, $amount], [
            1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        if (! is_resource($process)) throw new RuntimeException('Could not start a resolution worker.');
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
$refunds = $partial->walletTransactions()->where('transaction_type', 'refund')->count();
printf("Resolution waiters: %d; outcomes: %s; refunds: %d\n", $waiting, implode(', ', $results), $refunds);
if ($waiting !== 2 || $results !== ['409', 'resolved'] || $refunds !== 1) exit(1);
