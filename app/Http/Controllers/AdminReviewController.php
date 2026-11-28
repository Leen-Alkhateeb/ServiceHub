<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    


    public function index()
    {
        $reviews = AdminPeriod::apply(Review::with(['reviewer', 'reviewedUser', 'booking', 'attachment']))
            ->latest('created_at')
            ->paginate(15);

             return view('pages.admin.admin-reviews', compact('reviews'));
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
          $review = Review::findOrFail($id);
        
        
        $review->update([
            'is_active' => !$review->is_active,
        ]);

        $message = $review->is_active ? 'تم تفعيل التقييم بنجاح.' : 'تم إخفاء التقييم عن العامة.';

        return redirect()->back()->with('success', $message);
    }

    


    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return redirect()->back()->with('success', 'تم حذف التقييم بنجاح.');
    }
}
