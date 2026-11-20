<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VendorSettlement
{
    public function completed(): Builder
    {
        return Booking::query()->where('status', 'completed')->whereNotNull('completed_at');
    }

    public function statusQuery(Builder $query, string $status): Builder
    {
        $credit = fn (Builder $entries) => $entries->where('transaction_type', 'credit');
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

        return $query->whereHas('payment', $paid)->whereDoesntHave('refundRequest', $complaint)
            ->where('completed_at', $status === 'ready' ? '<=' : '>', now()->subHours(24));
    }

    public function release(): int
    {
        $released = 0;
        $this->statusQuery($this->completed(), 'ready')->chunkById(100, function ($bookings) use (&$released) {
            foreach ($bookings as $candidate) {
                $released += (int) DB::transaction(function () use ($candidate) {
                    $booking = Booking::query()->lockForUpdate()->find($candidate->id);
                    if (! $booking || ! $this->statusQuery($this->completed()->whereKey($booking->id), 'ready')->exists()) {
                        return false;
                    }

                    WalletTransaction::create([
                        'vendor_id' => $booking->vendorService->vendor_id,
                        'booking_id' => $booking->id,
                        'transaction_type' => 'credit',
                        'amount' => $booking->payment->amount,
                        'description' => 'تحرير مبلغ الحجز بعد مرور 24 ساعة',
                    ]);

                    return true;
                }, 3);
            }
        });

        return $released;
    }
}
