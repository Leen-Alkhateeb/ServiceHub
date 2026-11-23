<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    










    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:10,1')->only('sendResetLinkEmail');
    }

    protected function credentials(Request $request): array
    {
        return ['email' => mb_strtolower(trim($request->input('email'))), 'status' => 'active'];
    }
}
