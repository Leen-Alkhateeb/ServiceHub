<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\VendorProfile;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),

            'vendors' => User::where('role', 'vendor')->count(),

            'bookings' => Booking::count(),

            'pendingVendors' => VendorProfile::where(
                'application_status', 'pending'
            )->count(),

            'pendingRefunds' => RefundRequest::where(
                'status', 'pending'
            )->count(),

            'paidAmount' => Payment::where(
                'status', 'paid'
            )->sum('amount'),
        ];

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => $stats,
        ], 200);
    }
}
