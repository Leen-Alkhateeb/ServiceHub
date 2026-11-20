<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'archived' => ['sometimes', 'boolean'],
        ]);
        $search = trim($filters['q'] ?? '');

        $bookings = Booking::query()
            ->with(['customer', 'vendorService.vendor'])
            ->when($filters['archived'] ?? false, fn (Builder $query) => $query->onlyTrashed())
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $bookings) use ($search) {
                    $bookings->where('service_name', 'like', '%'.$search.'%');

                    if (ctype_digit($search)) {
                        $bookings->orWhere('id', $search);
                    }
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('booking_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('booking_date', '<=', $to))
            ->latest('id')
            ->paginate(15)
            ->appends($filters);

        return view('pages.admin.admin-bookings', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('pages.admin.admin-bookings-show', [
            'booking' => $booking->load(['customer', 'vendorService.vendor', 'payment']),
            'statusLabel' => self::STATUSES[$booking->status] ?? $booking->status,
        ]);
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('status', 'تم نقل الحجز إلى الأرشيف.');
    }
}
