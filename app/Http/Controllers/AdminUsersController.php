<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersController extends Controller
{
   
public function index(Request $request)
{
    $request->validate(['search' => ['nullable', 'string']]);
    $query = AdminPeriod::apply(User::query());

    if ($request->filled('search')) {

        $search = trim($request->input('search'));

        $fullName = $query->getConnection()->getDriverName() === 'sqlite'
            ? "first_name || ' ' || last_name"
            : "CONCAT(first_name, ' ', last_name)";
        $query->where(function ($q) use ($search, $fullName) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw(
                  "($fullName) LIKE ?",
                  ["%{$search}%"]
              )
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    if (in_array($request->input('role'), ['customer', 'vendor', 'admin'], true)) {
        $query->where('role', $request->input('role'));
    }

    if (in_array($request->input('gender'), ['male', 'female'], true)) {
        $query->where('gender', $request->input('gender'));
    }

    if (in_array($request->input('status'), ['active', 'inactive'], true)) {
        $query->where('status', $request->input('status'));
    }

    $users = $query->latest('id')->paginate(5)->withQueryString();

    return view('pages.admin.admin-users', compact('users'));
}

public function toggleStatus(User $user)
{
    if ($user->id === auth()->id()) {
        return back()->with('error', 'لا يمكنك تعطيل حسابك الشخصي.');
    }

    $user->status = $user->status === 'active'
        ? 'inactive'
        : 'active';

    $user->save();

    return back()->with(
        'success',
        $user->status === 'active'
            ? 'تم تفعيل المستخدم بنجاح.'
            : 'تم تعطيل المستخدم بنجاح.'
    );
}

public function destroy(User $user)
{
    if ($user->id === auth()->id()) {
        return back()->with('error', 'لا يمكنك حذف حسابك.');
    }

    if ($user->role === 'admin') {
        return back()->with('error', 'لا يمكن حذف حساب الأدمن.');
    }

    DB::transaction(function () use ($user) {
        $account = User::query()->lockForUpdate()->findOrFail($user->id);
        Service::where('suggested_by', $account->id)->update(['suggested_by' => null]);
        $account->delete();
    });

    return redirect()
        ->route('admin.admin.Users')
        ->with('success', 'تم حذف المستخدم نهائيًا بنجاح.');
}


}
