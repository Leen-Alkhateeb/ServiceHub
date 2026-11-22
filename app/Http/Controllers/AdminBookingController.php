<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    private const STATUSES = [
        'pending' => 'بانتظار رد المزوّد',
        'approved' => 'مقبول',
        'awaiting_payment' => 'بانتظار الدفع',
        'paid' => 'مدفوع',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
        'refunded' => 'مسترد',
        'objection_pending' => 'اعتراض قيد المراجعة',
        'confirmed' => 'مؤكد',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي المهلة',
        'closed' => 'مغلق',
    ];

    // Badge colour for each status (classes from the shared style.css).
    private const STATUS_COLORS = [
        'pending' => 'yellow',
        'awaiting_payment' => 'yellow',
        'objection_pending' => 'yellow',
        'approved' => 'green',
        'paid' => 'green',
        'in_progress' => 'green',
        'completed' => 'green',
        'confirmed' => 'green',
        'cancelled' => 'red',
        'rejected' => 'red',
        'expired' => 'red',
        'refunded' => '',
        'closed' => '',
    ];

    public function index(Request $request)
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'archived' => ['sometimes', 'boolean'],
        ];

        if ($request->filled('from')) {
            $rules['to'][] = 'after_or_equal:from';
        }

        $filters = $request->validate($rules);
        $search = trim($filters['q'] ?? '');
        $showArchived = ! empty($filters['archived']);

        $query = Booking::with(['customer', 'vendorService.vendor']);

        if ($showArchived) {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where(function ($bookings) use ($search) {
                $bookings->where('service_name', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('vendorService.vendor', function ($vendor) use ($search) {
                        $vendor->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });

                if (ctype_digit($search)) {
                    $bookings->orWhere('id', $search);
                }
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('booking_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('booking_date', '<=', $filters['to']);
        }

        $bookings = $query->latest('id')->paginate(15)->withQueryString();

        return view('pages.admin.admin-bookings', [
            'bookings' => $bookings,
            'filters' => $filters,
            'showArchived' => $showArchived,
            'statuses' => self::STATUSES,
            'statusColors' => self::STATUS_COLORS,
            'activeCount' => Booking::count(),
            'archivedCount' => Booking::onlyTrashed()->count(),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['customer', 'vendorService.vendor', 'payment', 'refundRequest', 'cancelledByUser']);
        if ($booking->cancellation_refund_amount !== null) {
            $booking->loadSum(['walletTransactions as cancellation_vendor_amount' => fn ($entries) =>
                $entries->whereIn('transaction_type', ['penalty', 'refund_share'])], 'amount');
        }

        $paymentStatuses = [
            'pending' => 'بانتظار الدفع',
            'paid' => 'مدفوعة',
            'failed' => 'فاشلة',
            'refunded' => 'مستردة',
        ];

        $refundStatuses = [
            'pending' => 'قيد المراجعة',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
        ];

        return view('pages.admin.admin-bookings-show', [
            'booking' => $booking,
            'statusLabel' => self::STATUSES[$booking->status] ?? $booking->status,
            'statusColor' => self::STATUS_COLORS[$booking->status] ?? '',
            'paymentStatusLabel' => $paymentStatuses[$booking->payment?->status] ?? $booking->payment?->status,
            'refundStatusLabel' => $refundStatuses[$booking->refundRequest?->status] ?? $booking->refundRequest?->status,
        ]);
    }

    public function destroy(Booking $booking)
    {
        // Soft delete only: the payment and wallet records of this booking stay in the database.
        $booking->delete();

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'تم نقل الحجز #' . $booking->id . ' إلى الأرشيف، وبقيت بيانات الدفع المرتبطة به محفوظة.');
    }
}
