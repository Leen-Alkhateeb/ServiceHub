
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'users' => User::count(),
            'vendors' => User::where('role', 'vendor')->count(),
            'bookings' => Booking::where('status', 'completed')->count(),
            'pendingVendors' => VendorProfile::where('application_status', 'pending')->count(),
            'pendingRefunds' => RefundRequest::where('status', 'pending')->count(),
            'paidAmount' => Payment::where('status', 'paid')->sum('amount'),
        ];

        $start = $today->copy()->subDays(6);

        $bookingCounts = Booking::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $bookingChart = [
            'labels' => [],
            'values' => [],
        ];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);

            $bookingChart['labels'][] = $day->format('m/d');
            $bookingChart['values'][] = (int) ($bookingCounts[$day->toDateString()] ?? 0);
        }

        $bookingStatuses = Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $statusChart = [
            'labels' => $bookingStatuses->pluck('status')->values(),
            'values' => $bookingStatuses->pluck('total')->map(fn ($v) => (int) $v)->values(),
        ];

        $latestUsers = User::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestReviews = Review::query()
            ->with(['reviewer', 'reviewedUser'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestRefunds = RefundRequest::query()
            ->with(['customer', 'booking'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $latestVendorApplications = VendorProfile::query()
            ->with('user')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('pages.admin.admin-dashboard', compact(
            'stats',
            'bookingChart',
            'statusChart',
            'latestUsers',
            'latestReviews',
            'latestRefunds',
            'latestVendorApplications'
        ));
    }
}
