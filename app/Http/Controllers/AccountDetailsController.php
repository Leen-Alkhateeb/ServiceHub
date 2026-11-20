<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountDetailsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $account = $request->user();
        $profile = $account->role === 'vendor' && $account->status === 'active' ? $account->vendorProfile : null;
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
        ];
        if ($profile) {
            $rules += [
                'bio' => ['required', 'string', 'max:2000'],
                'work_type' => ['required', 'string', 'max:255'],
                'experience_years' => ['required', 'integer', 'between:0,80'],
            ];
        }

        $data = $request->validateWithBag('accountDetails', $rules);
        DB::transaction(function () use ($account, $profile, $data) {
            $account->update(array_intersect_key($data, array_flip(['first_name', 'last_name', 'phone', 'location'])));
            $profile?->update(array_intersect_key($data, array_flip(['bio', 'work_type', 'experience_years'])));
        });

        return redirect()->route($profile ? 'vendor.profile' : 'account.profile')
            ->with('account_status', 'تم حفظ بيانات الملف الشخصي.');
    }
}
