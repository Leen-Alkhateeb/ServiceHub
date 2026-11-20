<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const GOVERNORATES = [
        'عمّان', 'الزرقاء', 'إربد', 'العقبة', 'البلقاء', 'مادبا',
        'الكرك', 'جرش', 'عجلون', 'المفرق', 'الطفيلة', 'معان',
    ];

    public function dashboard(Request $request)
    {
        $user = $this->customer($request);

        return view('pages.customer.customer-dashboard', [
            'stats' => [
                'حجوزات قادمة'  => $user->bookings()->whereIn('status', ['pending', 'approved', 'confirmed'])->count(),
                'حجوزات مكتملة' => $user->bookings()->where('status', 'completed')->count(),
                'المفضلة'        => $user->favorites()->count(),
            ],
        ]);
    }

    public function bookings(Request $request)
    {
        $user = $this->customer($request);
        $base = fn () => $user->bookings()->with(['vendorService.vendor', 'payment'])->latest('id');

        return view('pages.customer.customer-bookings', [
            'current' => $base()->whereIn('status', ['pending', 'approved', 'confirmed'])->get(),
            'past'    => $base()->whereIn('status', ['completed', 'rejected', 'cancelled', 'expired', 'closed'])->get(),
        ]);
    }

    public function favorites(Request $request)
    {
        $user = $this->customer($request);

        return view('pages.customer.customer-favorites', [
            'favorites' => $user->favorites()->with(['vendorService.vendor', 'vendorService.service'])->latest('id')->get(),
        ]);
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

    /** الصفحات هاي للعميل فقط */
    private function customer(Request $request)
    {
        abort_unless($request->user()->role === 'customer', 403);

        return $request->user();
    }
}