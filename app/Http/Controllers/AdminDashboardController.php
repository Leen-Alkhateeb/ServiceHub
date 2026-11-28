<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\WalletTransaction;
use App\Services\AdminPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function wallet()
    {
        $transactions = AdminPeriod::apply(WalletTransaction::with(['vendor', 'booking'])->whereNotNull('booking_id'))
            ->latest('id')
            ->paginate(15);

        return view('pages.admin.admin-wallet', compact('transactions'));
    }

    public function period(Request $request)
    {
        $dates = $request->validate([
            'period_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01'],
            'period_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', ...($request->filled('period_from') ? ['after_or_equal:period_from'] : [])],
        ], [
            'period_from.*' => 'تأكد من تاريخ بداية الفترة.',
            'period_to.*' => 'تأكد من تاريخ نهاية الفترة.',
            'period_to.after_or_equal' => 'النهاية لازم تكون بنفس يوم البداية أو بعده.',
        ]);

        $request->session()->put(AdminPeriod::SESSION_KEY, array_filter([
            'from' => $dates['period_from'] ?? null,
            'to' => $dates['period_to'] ?? null,
        ]));

        return redirect()->route('admin.dashboard');
    }

    public function index()
    {
        $period = AdminPeriod::dates();
        $bookings = AdminPeriod::apply(Booking::query());

        $stats = [
            'users' => AdminPeriod::apply(User::query())->count(),
            'vendors' => AdminPeriod::apply(User::where('role', 'vendor'))->count(),
            'bookings' => (clone $bookings)->count(),
            'pendingVendors' => AdminPeriod::apply(VendorProfile::where('application_status', 'pending'), 'applied_at')->count(),
            'pendingRefunds' => AdminPeriod::apply(RefundRequest::where('status', 'pending'))->count(),
            'paidAmount' => AdminPeriod::apply(Payment::where('status', 'paid'))->sum('amount'),
        ];

        $start = Carbon::today()->subDays(6);

        $bookingCounts = (clone $bookings)
            ->when($period === [], fn ($query) => $query->where('created_at', '>=', $start))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $bookingChart = [
            'labels' => [],
            'values' => [],
        ];

        if ($period !== []) {
            $bookingChart = ['labels' => $bookingCounts->keys()->all(), 'values' => $bookingCounts->map(fn ($count) => (int) $count)->values()->all()];
        } else {
            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $bookingChart['labels'][] = $day->format('m/d');
                $bookingChart['values'][] = (int) ($bookingCounts[$day->toDateString()] ?? 0);
            }
        }

        $bookingStatuses = (clone $bookings)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $statusChart = [
            'labels' => $bookingStatuses->pluck('status')->values(),
            'values' => $bookingStatuses->pluck('total')->map(fn ($v) => (int) $v)->values(),
        ];

        $latestUsers = AdminPeriod::apply(User::query())
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestReviews = AdminPeriod::apply(Review::query())
            ->with(['reviewer', 'reviewedUser'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestRefunds = AdminPeriod::apply(RefundRequest::query())
            ->with(['customer', 'booking'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestVendorApplications = AdminPeriod::apply(VendorProfile::query(), 'applied_at')
            ->with('user')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('pages.admin.admin-dashboard', compact(
            'stats',
            'period',
            'bookingChart',
            'statusChart',
            'latestUsers',
            'latestReviews',
            'latestRefunds',
            'latestVendorApplications'
        ));
    }
}
