<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPublicProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        abort_unless(in_array($request->user()->role, ['admin', 'vendor'], true), 403);

        return view('pages.public.user-profile', compact('user'));
    }
}
