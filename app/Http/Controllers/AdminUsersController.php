<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUsersController extends Controller
{
   
public function index(Request $request)
{
    $query = User::query();

    if ($request->filled('search')) {

        $search = trim($request->input('search'));

        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw(
                  "CONCAT(first_name, ' ', last_name) LIKE ?",
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

    $users = $query->orderByDesc('role')->get();

    $count = 0;

    return view('pages.admin.admin-users', compact('users', 'count'));
}

}