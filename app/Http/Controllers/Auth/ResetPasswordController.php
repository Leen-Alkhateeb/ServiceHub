<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    










    use ResetsPasswords;

    




    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:10,1')->only('reset');
    }

    protected function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['bail', 'required', 'string', new StrongPassword, 'confirmed'],
        ];
    }

    protected function credentials(Request $request): array
    {
        return $request->only('password', 'password_confirmation', 'token') + [
            'email' => mb_strtolower(trim($request->input('email'))),
            'status' => 'active',
        ];
    }
}
