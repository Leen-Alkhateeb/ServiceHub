<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;

class VendorPublicController extends Controller
{
    public function show(User $vendor)
    {
        abort_unless($vendor->role === 'vendor' && $vendor->status === 'active', 404);

        $vendor->load('vendorProfile');

        return view('market.provider', [
            'vendor'       => $vendor,
            'services'     => $vendor->vendorServices()
                                ->where('status', 'active')
                                ->whereHas('service', fn ($q) => $q->where('approval_status', 'approved'))
                                ->with('service.category')
                                ->get(),
            'reviews'      => $vendor->reviewsReceived()->with('reviewer')->latest('id')->take(10)->get(),
            'rating'       => round((float) $vendor->reviewsReceived()->avg('rating'), 1),
            'reviewsCount' => $vendor->reviewsReceived()->count(),
            'completed'    => Booking::whereHas('vendorService', fn ($q) => $q->where('vendor_id', $vendor->id))
                                ->where('status', 'completed')->count(),
        ]);
    }
}