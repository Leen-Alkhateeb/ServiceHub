<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    










    use AuthenticatesUsers;

    




    protected function redirectTo(): string
    {
        return auth()->user()->role === 'vendor' ? route('vendor.profile') : route('home');
    }

    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        if ($user->role === 'admin') {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.dashboard');
        }

        return null;
    }

    




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
            'password' => ['bail', 'required', 'string', new StrongPassword],
        ]);
    }

    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password') + ['status' => 'active'];
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
        ]);
    }
}
