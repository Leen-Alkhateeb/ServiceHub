<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const GOVERNORATES = [
        'عمّان', 'الزرقاء', 'إربد', 'العقبة', 'البلقاء', 'مادبا',
        'الكرك', 'جرش', 'عجلون', 'المفرق', 'الطفيلة', 'معان',
    ];

    public function reviews(Request $request)
    {
        $reviews = $this->customer($request)->reviewsGiven()->where('is_active', true)
            ->with(['booking' => fn ($booking) => $booking->withTrashed()])->latest('id')->paginate(12);

        return view('pages.customer.customer-reviews', compact('reviews'));
    }

    public function messages(Request $request)
    {
        $messages = ContactMessage::where('sender_id', $this->customer($request)->id)->latest('id')->paginate(12);

        return view('pages.customer.customer-messages', compact('messages'));
    }

    public function sendMessage(Request $request)
    {
        abort_unless($request->user()->status === 'active', 403);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);
        ContactMessage::create($data + ['sender_id' => $request->user()->id, 'status' => 'pending']);

        return redirect()->route('contact')->with('status', 'تم إرسال رسالتك إلى الإدارة.');
    }

    public function dashboard(Request $request)
    {
        $user = $this->customer($request);

        return view('pages.customer.customer-dashboard', [
            'stats' => [
                'حجوزات قادمة'  => $user->bookingsAsCustomer()->whereIn('status', ['pending', 'approved', 'confirmed'])->count(),
                'حجوزات مكتملة' => $user->bookingsAsCustomer()->where('status', 'completed')->count(),
                'المفضلة'        => $user->favorites()->count(),
            ],
        ]);
    }

    public function bookings(Request $request)
    {
        $user = $this->customer($request);
        $base = fn () => $user->bookingsAsCustomer()->with(['vendorService.vendor', 'payment', 'refundRequest'])->latest('id');

        return view('pages.customer.customer-bookings', [
            'current' => $base()->whereIn('status', ['pending', 'approved', 'awaiting_payment', 'confirmed', 'paid', 'in_progress', 'objection_pending'])->get(),
            'past'    => $base()->whereIn('status', ['completed', 'rejected', 'cancelled', 'expired', 'closed', 'refunded'])->get(),
        ]);
    }

    public function favorites(Request $request)
    {
        $user = $this->customer($request);

        $favorites = $user->favorites()
                ->with(['vendorService.vendor', 'vendorService.service.category'])
                ->latest('id')
                ->get();
        $availableOffers = \App\Models\VendorService::publiclyVisible()
            ->whereIn('id', $favorites->pluck('vendor_service_id'))->pluck('id')->all();

        return view('pages.customer.customer-favorites', compact('favorites', 'availableOffers'));
    }

    public function notifications(Request $request)
    {
        $user = $this->customer($request);

        $notifications = $user->notifications()->latest('id')->get();
        $user->notifications()->where('is_read', false)->update(['is_read' => true]);

        return view('pages.customer.customer-notifications', compact('notifications'));
    }

    public function settings(Request $request)
    {
        $this->customer($request);

        return view('pages.customer.customer-settings', ['governorates' => self::GOVERNORATES]);
    }

    public function updateSettings(Request $request)
    {
        $user = $this->customer($request);

        $data = $request->validate([
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'location'      => ['nullable', Rule::in(self::GOVERNORATES)],
            'gender'        => ['nullable', Rule::in(['male', 'female'])],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('avatars', 'public');
        }

        $user->update($data);

        return redirect()->route('customer.settings')->with('success', 'تم تحديث معلوماتك.');
    }

     
    private function customer(Request $request)
    {
        abort_unless($request->user()->role === 'customer', 403);

        return $request->user();
    }
}
