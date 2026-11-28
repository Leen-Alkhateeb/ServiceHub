<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

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
            'payment_status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'objection_status' => ['nullable', 'in:pending,accepted,rejected'],
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

        $query = AdminPeriod::apply(Booking::with(['customer', 'vendorService.vendor']));

        if ($showArchived) {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where(function ($bookings) use ($search) {
                $bookings->where('service_name', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                            $customer->where(fn ($name) => $name->where('first_name', 'like', '%'.$word.'%')->orWhere('last_name', 'like', '%'.$word.'%'));
                        }
                    })
                    ->orWhereHas('vendorService.vendor', function ($vendor) use ($search) {
                        foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                            $vendor->where(fn ($name) => $name->where('first_name', 'like', '%'.$word.'%')->orWhere('last_name', 'like', '%'.$word.'%'));
                        }
                    });

                if (ctype_digit($search)) {
                    $bookings->orWhere('id', $search);
                }
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        foreach (['payment_status' => 'payment', 'objection_status' => 'refundRequest'] as $filter => $relation) {
            if (! empty($filters[$filter])) {
                $query->whereHas($relation, fn ($records) => $records->where('status', $filters[$filter]));
            }
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
            'activeCount' => AdminPeriod::apply(Booking::query())->count(),
            'archivedCount' => AdminPeriod::apply(Booking::onlyTrashed())->count(),
        ]);
    }

    public function show(Booking $booking)
    {
        abort_unless(AdminPeriod::includes($booking), 404);
        $booking->load(['customer', 'vendorService.vendor', 'payment', 'refundRequest', 'cancelledByUser', 'evidence']);
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
        abort_if($booking->booking_type === 'instant', 409, 'تُدار الحجوزات الفورية من لوحة صلّحها الآن.');
         
        $booking->delete();

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'تم نقل الحجز #' . $booking->id . ' إلى الأرشيف، وبقيت بيانات الدفع المرتبطة به محفوظة.');
    }
}
