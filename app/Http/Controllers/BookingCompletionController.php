<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingCompletionController extends Controller
{
    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        $message = DB::transaction(function () use ($request, $booking) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $actor = (int) $request->user()->id;
            $customer = (int) $current->customer_id;
            $vendor = (int) $current->vendorService->vendor_id;
            abort_unless(in_array($actor, [$customer, $vendor], true), 403);

            $own = $actor === $customer ? 'customer_completed_at' : 'vendor_completed_at';
            $other = $actor === $customer ? 'vendor_completed_at' : 'customer_completed_at';
            if ($current->{$own}) {
                return 'تم تسجيل تأكيدك مسبقًا.';
            }

            abort_unless($current->acceptsCompletionConfirmation(), 403);
            $confirmedAt = now();
            $current->{$own} = $confirmedAt;
            $complete = $current->{$other} !== null;
            if ($complete) {
                $current->status = 'completed';
                $current->completed_at = $confirmedAt;
                $current->objection_deadline = $confirmedAt->copy()->addHours(24);
            }
            $current->save();

            $message = $complete
                ? 'اكتمل الحجز ويمكن للطرفين التقييم. بدأت مهلة الاعتراض لمدة 24 ساعة.'
                : 'تم تسجيل تأكيد الاكتمال. بانتظار تأكيد الطرف الآخر.';

            Notification::create([
                'user_id' => $actor === $customer ? $vendor : $customer,
                'title' => 'تحديث اكتمال الحجز',
                'message' => $message,
                'type' => 'booking',
            ]);

            return $message;
        });

        return back()->with('success', $message);
    }

    public function review(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && $current->completed_at, 403);
            $current->reviews()->updateOrCreate(
                ['reviewer_id' => $current->customer_id],
                $data + ['reviewed_user_id' => $current->vendorService->vendor_id, 'comment' => null, 'is_active' => true],
            );
        });

        return back()->with('success', 'تم حفظ تقييم الفني.');
    }

    public function object(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($booking, $data) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && $current->objection_deadline?->isFuture(), 403);
            abort_if($current->refundRequest()->exists() || $current->walletTransactions()
                ->whereIn('transaction_type', ['credit', 'payout'])->exists(), 409);

            $current->refundRequest()->create($data + ['customer_id' => $current->customer_id, 'status' => 'pending']);
        });

        return back()->with('success', 'تم إرسال الاعتراض للإدارة وإيقاف تحرير المبلغ لحين المراجعة.');
    }
}
