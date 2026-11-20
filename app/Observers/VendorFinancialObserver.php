<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;

class VendorFinancialObserver
{
    public function created(Booking|Payment|RefundRequest|WalletTransaction $record): void
    {
        if ($record instanceof Booking && ($record->status !== 'completed' || ! $record->completed_at)) {
            return;
        }

        $this->notify($record);
    }

    public function updated(Booking|Payment|RefundRequest|WalletTransaction $record): void
    {
        if ($record instanceof Booking) {
            if ($record->status === 'completed' && $record->completed_at && $record->wasChanged(['status', 'completed_at'])) {
                $this->notify($record);
            }

            return;
        }

        if ($record->wasChanged(['status', 'amount', 'transaction_type', 'refund_amount'])) {
            $this->notify($record);
        }
    }

    private function notify(Model $record): void
    {
        $booking = $record instanceof Booking ? $record : $record->booking;
        $message = match (true) {
            $record instanceof Booking => 'اكتمل الحجز وبدأت مهلة احتجاز المبلغ لمدة 24 ساعة.',
            $record instanceof WalletTransaction => 'تم تحديث السجل المالي بمبلغ '.$record->amount.' د.أ.',
            $record instanceof Payment => 'تم تحديث معلومات الدفع الخاصة بالحجز.',
            default => 'تم تحديث طلب المراجعة المالية الخاص بالحجز.',
        };

        Notification::create([
            'user_id' => $booking->vendorService->vendor_id,
            'title' => 'تحديث مالي للحجز رقم '.$booking->id,
            'message' => $message,
            'type' => 'wallet',
        ]);
    }
}
