<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

use App\Models\Booking;
use Illuminate\Http\Request;

class AdminCancellationController extends Controller
{
    


    public function index()
    {
         $cancellations = AdminPeriod::apply(Booking::with(['customer', 'cancelledByUser']), 'cancelled_at')
            ->where(fn ($query) => $query->whereNotNull('cancelled_at')->orWhere('status', 'cancelled'))
            ->latest('cancelled_at')
            ->paginate(10);

        return view('pages.admin.admin-cancellations', compact('cancellations'));
    }

    


    public function create()
    {
         
    }

    


    public function store(Request $request)
    {
         
    }

    


    public function show(string $id)
    {
         
    }

    


    public function edit(string $id)
    {
         
    }

    


    public function update(Request $request, string $id)
    {
         
    }

    


    public function destroy(string $id)
    {
        $booking = Booking::where(fn ($query) => $query->whereNotNull('cancelled_at')->orWhere('status', 'cancelled'))
            ->findOrFail($id);
        $booking->delete();

        return redirect()->route('admin.cancellations.index')
            ->with('success', 'تم حذف سجل الإلغاء بنجاح.');
    }
}
