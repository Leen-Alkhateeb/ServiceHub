<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUsersController extends Controller
{
   public function index(){
    $users = User::all();
    $count=0;
return view('pages.admin.admin-users', compact('users', 'count'));   }

public function destroy(User $user)
{
    if ($user->role === 'admin') {
        return back()->with('error', 'لا يمكن حذف حساب الأدمن.');
    }

    $user->delete();

    return back()->with('success', 'تم تعطيل المستخدم .');
}
}