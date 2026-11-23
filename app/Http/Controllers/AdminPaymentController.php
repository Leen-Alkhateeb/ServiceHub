<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPaymentController extends Controller
{
     
    private const STATUSES = [
        'pending' => 'بانتظار الدفع',
        'paid' => 'مدفوعة',
        'failed' => 'فاشلة',
        'refunded' => 'مستردة',
    ];

    private const STATUS_COLORS = [
        'pending' => 'yellow',
        'paid' => 'green',
        'failed' => 'red',
        'refunded' => '',
    ];

    private const METHODS = [
        'card' => 'بطاقة',
        'cash' => 'نقداً',
        'simulated' => 'تجريبي',
    ];

    private const TRANSACTION_TYPES = [
        'credit' => 'إضافة لمحفظة المزوّد',
        'payout' => 'سحب للمزوّد',
        'penalty' => 'غرامة',
        'refund_share' => 'حصة استرداد',
        'adjustment' => 'تسوية',
    ];

    public function index(Request $request)
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'method' => ['nullable', Rule::in(array_keys(self::METHODS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($request->filled('from')) {
            $rules['to'][] = 'after_or_equal:from';
        }

        $filters = $request->validate($rules);
        $search = trim($filters['q'] ?? '');

         
        $query = Payment::with(['booking.customer', 'booking.vendorService.vendor']);

        if ($search !== '') {
            $query->where(function ($payments) use ($search) {
                $payments->whereHas('booking', function ($booking) use ($search) {
                    $booking->where('service_name', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%');
                        });
                });

                if (ctype_digit($search)) {
                    $payments->orWhere('id', $search)->orWhere('booking_id', $search);
                }
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('paid_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('paid_at', '<=', $filters['to']);
        }

        $payments = $query->latest('id')->paginate(15)->withQueryString();

        return view('pages.admin.admin-payments', [
            'payments' => $payments,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'statusColors' => self::STATUS_COLORS,
            'methods' => self::METHODS,
            'paidTotal' => Payment::where('status', 'paid')->sum('amount'),
            'paidCount' => Payment::where('status', 'paid')->count(),
            'pendingCount' => Payment::where('status', 'pending')->count(),
            'refundedTotal' => Payment::where('payments.status', 'refunded')
                ->leftJoin('refund_requests', 'refund_requests.booking_id', '=', 'payments.booking_id')
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(refund_requests.refund_amount, payments.amount)')),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'booking.customer',
            'booking.vendorService.vendor',
            'booking.refundRequest',
            'booking.walletTransactions',
        ]);

        $booking = $payment->booking;
        $remainingAmount = $booking ? max((float) $booking->original_price - (float) $payment->amount, 0) : null;

        return view('pages.admin.admin-payments-show', [
            'payment' => $payment,
            'booking' => $booking,
            'remainingAmount' => $remainingAmount,
            'statusLabel' => self::STATUSES[$payment->status] ?? $payment->status,
            'statusColor' => self::STATUS_COLORS[$payment->status] ?? '',
            'methodLabel' => self::METHODS[$payment->payment_method] ?? '—',
            'transactionTypes' => self::TRANSACTION_TYPES,
        ]);
    }
}
