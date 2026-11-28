<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\VendorService;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
     
    public function toggle(Request $request, VendorService $vendorService)
    {
        $favorite = Favorite::where('customer_id', $request->user()->id)
            ->where('vendor_service_id', $vendorService->id)
            ->first();

        $favorite
            ? $favorite->delete()
            : Favorite::create([
                'customer_id'       => $request->user()->id,
                'vendor_service_id' => $vendorService->id,
            ]);

        return back();
    }
}