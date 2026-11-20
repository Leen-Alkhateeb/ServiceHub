<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class VendorApplicationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user && $request->boolean('start')) {
            $request->session()->put('url.intended', route('vendor.apply'));

            return redirect()->route('login');
        }

        $application = $user
            ? DB::table('vendor_profiles')->where('user_id', $user->id)
                ->first(['application_status', 'applied_at', 'rejection_reason'])
            : null;

        return view('pages.public.become-vendor', [
            'application' => $application,
            'canApply' => $user?->role === 'customer' && $user->status === 'active',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'customer' && $user->status === 'active', 403);

        if (DB::table('vendor_profiles')->where('user_id', $user->id)->exists()) {
            return redirect()->route('vendor.apply');
        }

        $data = $request->validate([
            'experience_years' => ['required', 'integer', 'between:0,80'],
            'work_type' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:5000'],
            'permit_expiry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:9999-12-31'],
            'work_permit' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'experience_years.*' => 'أدخل عدد سنوات الخبرة من 0 إلى 80.',
            'work_type.*' => 'أدخل تخصصًا لا يتجاوز 255 حرفًا.',
            'bio.*' => 'أدخل نبذة عن خبرتك لا تتجاوز 5000 حرف.',
            'permit_expiry_date.*' => 'أدخل تاريخ انتهاء تصريح ساري المفعول.',
            'work_permit.*' => 'ارفع تصريح العمل بصيغة PDF أو JPG أو PNG وبحجم لا يتجاوز 5 ميغابايت.',
        ]);

        $path = $data['work_permit']->store('vendor-permits', 'local');
        if (! $path) {
            return back()->withInput()->withErrors(['work_permit' => 'تعذر حفظ التصريح. حاول مرة أخرى.']);
        }

        try {
            $now = now();
            DB::table('vendor_profiles')->insert([
                'user_id' => $user->id,
                'experience_years' => $data['experience_years'],
                'work_type' => $data['work_type'],
                'bio' => $data['bio'],
                'permit_expiry_date' => $data['permit_expiry_date'],
                'work_permit_path' => $path,
                'application_status' => 'pending',
                'applied_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException) {
            Storage::disk('local')->delete($path);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('vendor.apply');
    }
}
