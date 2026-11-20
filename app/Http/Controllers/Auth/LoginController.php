<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function redirectTo(): string
    {
        return auth()->user()->role === 'vendor' ? route('vendor.profile') : route('home');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function validateLogin(Request $request): void
    {
        $email = $request->input('email');
        $request->merge(['email' => is_string($email) ? mb_strtolower(trim($email)) : null]);
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'regex:/\A[^\x00]{1,72}\z/s'],
        ]);
    }

    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password') + ['status' => 'active'];
    }
}
