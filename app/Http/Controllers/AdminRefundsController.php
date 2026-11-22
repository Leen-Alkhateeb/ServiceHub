<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRefundsController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in(['pending', 'accepted', 'rejected']),
            ],
        ]);

        $search = trim($data['search'] ?? '');

        $query = RefundRequest::query()
            ->with([
                'customer',
                'booking.vendorService.vendor',
                'booking.vendorService.service',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', function ($booking) use ($search) {
                    $booking->where('service_name', 'like', "%{$search}%")
                        ->orWhereHas('vendorService.vendor', function ($vendor) use ($search) {
                            $vendor->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                })
                ->orWhereHas('customer', function ($customer) use ($search) {
                    $customer->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search)
                        ->orWhere('booking_id', (int) $search);
                }
            });
        }

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $refunds = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    ELSE 1
                END
            ")
            ->latest('created_at')
            ->latest('id')
            ->paginate(6)
            ->withQueryString();

        $counts = RefundRequest::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending")
            ->selectRaw("SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected")
            ->first();

        $stats = [
            'total' => (int) ($counts->total ?? 0),
            'pending' => (int) ($counts->pending ?? 0),
            'accepted' => (int) ($counts->accepted ?? 0),
            'rejected' => (int) ($counts->rejected ?? 0),
        ];

        return view('pages.admin.admin-refunds', compact('refunds', 'stats'));
    }

    public function decide(
        Request $request,
        RefundRequest $refund
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                Rule::in(['accepted', 'rejected']),
            ],
        ]);

        DB::transaction(function () use ($refund, $data) {
            $current = RefundRequest::query()
                ->lockForUpdate()
                ->findOrFail($refund->id);

            abort_unless($current->status === 'pending', 409);

            $booking = $current->booking()
                ->lockForUpdate()
                ->firstOrFail();

            $offer = $booking->vendorService;

            abort_unless($offer !== null, 409);

            $vendorId = $offer->vendor_id;
            $payment = $booking->payment()->first();
            abort_unless($payment?->status === 'paid', 409);
            $amount = (float) $payment->amount;

            // لا نعالج طلباً لحجز لم يكتمل.
            abort_unless(
                $booking->completed_at !== null,
                409
            );

            $accepted = $data['decision'] === 'accepted';

            // منع تكرار الصرف إذا كانت هناك معاملات سابقة.
            $hasPayout = WalletTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('vendor_id', $vendorId)
                ->whereIn('transaction_type', [
                    'payout',
                    'refund_share',
                    'credit',
                ])
                ->exists();

            abort_if($hasPayout, 409);

            $current->update([
                'status' => $data['decision'],
                'reviewed_at' => now(),
                'refund_amount' => $accepted ? round($amount * 0.70, 2) : null,
            ]);

            if ($accepted) {
                $payment->update(['status' => 'refunded']);
                /*
                 * قبول الاعتراض:
                 * 70% للعميل
                 * 15% للـVendor
                 * 15% للمنصة
                 *
                 * تسجيل حصة الـVendor في محفظته.
                 */
                WalletTransaction::create([
                    'vendor_id' => $vendorId,
                    'booking_id' => $booking->id,
                    'transaction_type' => 'refund_share',
                    'amount' => round($amount * 0.15, 2),
                    'description' => 'حصة مقدم الخدمة بعد قبول الاعتراض',
                ]);

                $booking->update([
                    'status' => 'refunded',
                ]);
            } else {
                /*
                 * رفض الاعتراض:
                 * 85% للـVendor
                 * 15% للمنصة
                 */
                WalletTransaction::create([
                    'vendor_id' => $vendorId,
                    'booking_id' => $booking->id,
                    'transaction_type' => 'payout',
                    'amount' => round($amount * 0.85, 2),
                    'description' => 'تحرير مستحقات مقدم الخدمة بعد رفض الاعتراض',
                ]);

                $booking->update([
                    'status' => 'completed',
                ]);
            }

            $current->customer?->notifications()->create([
                'title' => $accepted
                    ? 'تم قبول طلب الاسترداد'
                    : 'تم رفض طلب الاسترداد',
                'message' => $accepted
                    ? 'تم قبول اعتراضك على الحجز رقم '
                        . $booking->id
                        . '، ونسبة الاسترداد 70%.'
                    : 'تم رفض اعتراضك على الحجز رقم '
                        . $booking->id . '.',
                'type' => 'refund',
            ]);

            $offer->vendor?->notifications()->create([
                'title' => $accepted
                    ? 'تم قبول اعتراض العميل'
                    : 'تم رفض اعتراض العميل',
                'message' => $accepted
                    ? 'تم اعتماد حصة 15% لك من الحجز رقم '
                        . $booking->id . '.'
                    : 'تم اعتماد مستحقاتك بنسبة 85% من الحجز رقم '
                        . $booking->id . '.',
                'type' => 'refund',
            ]);
        }, 3);

        return redirect()
            ->route('admin.refunds.index')
            ->with(
                'status',
                $data['decision'] === 'accepted'
                    ? 'تم قبول طلب الاسترداد.'
                    : 'تم رفض طلب الاسترداد.'
            );
    }
}
