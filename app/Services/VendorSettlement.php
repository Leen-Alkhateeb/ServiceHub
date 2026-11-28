<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;

class VendorSettlement
{
    public static function vendorShare(string $amount): string
    {
        [$dinars, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $minorUnits = (int) $dinars * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        $share = intdiv($minorUnits * 85 + 50, 100);

        return sprintf('%d.%02d', intdiv($share, 100), $share % 100);
    }

    public function completed(): Builder
    {
        return Booking::withTrashed()->where('booking_type', 'scheduled')->where('status', 'completed')->whereNotNull('completed_at');
    }

    public function statusQuery(Builder $query, string $status): Builder
    {
        $credit = fn (Builder $entries) => $entries->whereIn('transaction_type', ['credit', 'payout']);
        if ($status === 'released') {
            return $query->whereHas('walletTransactions', $credit);
        }

        $query->whereDoesntHave('walletTransactions', $credit);
        $paid = fn (Builder $payment) => $payment->where('status', 'paid');
        $complaint = fn (Builder $refund) => $refund->where('status', '!=', 'rejected');

        if ($status === 'issue') {
            return $query->where(fn (Builder $rows) => $rows->whereDoesntHave('payment', $paid)
                ->orWhereHas('refundRequest', $complaint));
        }

        $comparison = $status === 'ready' ? '<=' : '>';

        return $query->whereHas('payment', $paid)->whereDoesntHave('refundRequest', $complaint)
            ->where(fn (Builder $rows) => $rows->where('objection_deadline', $comparison, now())
                ->orWhere(fn (Builder $legacy) => $legacy->whereNull('objection_deadline')
                    ->where('completed_at', $comparison, now()->subHours(24))));
    }

    public function release(): int
    {
        $released = 0;
        $instantFinance = app(InstantFinance::class);
        $this->statusQuery($this->completed(), 'ready')->chunkById(100, function ($bookings) use (&$released, $instantFinance) {
            foreach ($bookings as $candidate) {
                $vendor = $candidate->vendorService?->vendor_id;
                if (! $vendor) continue;
                $released += (int) $instantFinance->withPlatformLock(function () use ($candidate, $vendor, $instantFinance) {
                    $users = User::query()->whereKey([$candidate->customer_id, $vendor])->orderBy('id')->lockForUpdate()->get();
                    if (! $users->contains('id', $vendor)) return false;
                    $booking = Booking::withTrashed()->lockForUpdate()->find($candidate->id);
                    if (! $booking || ! $this->statusQuery($this->completed()->whereKey($booking->id), 'ready')->exists()) {
                        return false;
                    }

                    WalletTransaction::create([
                        'user_id' => $booking->vendorService->vendor_id,
                        'booking_id' => $booking->id,
                        'transaction_type' => 'credit',
                        'amount' => $instantFinance->scheduledNetEntitlement($booking, $vendor, self::vendorShare((string) $booking->payment->amount)),
                        'description' => 'تحرير حصة الفني (85٪) بعد انتهاء مهلة الاعتراض',
                    ]);

                    return true;
                });
            }
        });

        return $released;
    }
}
