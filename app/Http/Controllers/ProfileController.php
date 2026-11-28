<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Booking;
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
        $tabs = ['all' => 'الكل', 'pending' => 'طلبات معلقة', 'active' => 'الحجوزات النشطة', 'completed' => 'المكتملة', 'cancelled' => 'الملغاة والمنتهية', 'action' => 'تحتاج إجراء'];
        $filters = $request->validate(['tab' => ['nullable', Rule::in(array_keys($tabs))], 'q' => ['nullable', 'string', 'max:100']]);
        $tab = $filters['tab'] ?? 'all';
        $query = $user->bookingsAsCustomer()->with(['vendorService.vendor', 'vendorService.service.category', 'payment', 'refundRequest']);
        match ($tab) {
            'pending' => $query->where('status', 'pending'),
            'active' => $query->whereIn('status', ['approved', 'awaiting_payment', 'confirmed', 'paid', 'in_progress', 'objection_pending']),
            'completed' => $query->where('status', 'completed'),
            'cancelled' => $query->whereIn('status', ['cancelled', 'rejected', 'expired', 'closed', 'refunded']),
            'action' => $query->where(fn ($rows) => $rows
                ->where(fn ($payment) => $payment->whereIn('status', ['approved', 'awaiting_payment'])->where('payment_deadline', '>', now()))
                ->orWhere(fn ($completion) => $completion->whereIn('status', ['confirmed', 'paid', 'in_progress', 'completed'])
                    ->whereNotNull('vendor_completed_at')->whereNull('customer_completed_at')
                    ->whereDoesntHave('refundRequest', fn ($refund) => $refund->where('status', 'pending')))),
            default => null,
        };
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($rows) => $rows->where('service_name', 'like', '%'.$search.'%')
                ->when(ctype_digit($search), fn ($matches) => $matches->orWhere('id', $search)));
        }
        $bookings = $query->latest('id')->paginate(9)->withQueryString();
        $visibleOffers = \App\Models\VendorService::publiclyVisible()->whereIn('id', $bookings->pluck('vendor_service_id'))->pluck('id')->all();

        return view('pages.customer.customer-bookings', compact('bookings', 'tab', 'tabs', 'filters', 'visibleOffers'));
    }

    public function booking(Request $request, Booking $booking)
    {
        abort_unless((int) $booking->customer_id === (int) $this->customer($request)->id, 404);
        $booking->load(['vendorService.vendor.vendorProfile', 'payment', 'refundRequest', 'evidence']);

        return view('pages.customer.booking-details', compact('booking'));
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
