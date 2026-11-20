<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers {
        register as private registerWithUi;
    }

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:10,1')->only('register');
    }

    public function register(Request $request)
    {
        $email = $request->input('email');
        $request->merge(['email' => is_string($email) ? mb_strtolower(trim($email)) : null]);

        return $this->registerWithUi($request);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:32'],
            'location' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/\A[^\x00]{1,72}\z/s'],
            'account_type' => ['sometimes', 'required', 'in:customer,vendor'],
            'work_type' => ['exclude_unless:account_type,vendor', 'required', 'string', 'max:255'],
            'experience_years' => ['exclude_unless:account_type,vendor', 'required', 'integer', 'between:0,80'],
            'bio' => ['exclude_unless:account_type,vendor', 'required', 'string', 'max:5000'],
            'permit_expiry_date' => ['exclude_unless:account_type,vendor', 'required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:9999-12-31'],
            'work_permit' => ['exclude_unless:account_type,vendor', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        $isVendor = ($data['account_type'] ?? null) === 'vendor';
        $permit = $isVendor ? $data['work_permit']->store('vendor-permits', 'local') : null;
        if ($isVendor && ! $permit) {
            throw ValidationException::withMessages(['work_permit' => 'تعذر حفظ تصريح العمل. حاول مرة أخرى.']);
        }

        try {
            return DB::transaction(function () use ($data, $isVendor, $permit) {
                $user = User::forceCreate([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'location' => $data['location'],
                    'gender' => $data['gender'],
                    'password' => $data['password'],
                    'role' => $isVendor ? 'vendor' : 'customer',
                    'status' => 'active',
                ]);
                if ($isVendor) {
                    $user->vendorProfile()->create([
                        'work_type' => $data['work_type'],
                        'experience_years' => $data['experience_years'],
                        'bio' => $data['bio'],
                        'permit_expiry_date' => $data['permit_expiry_date'],
                        'work_permit_path' => $permit,
                        'application_status' => 'pending',
                        'applied_at' => now(),
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            if ($permit) {
                Storage::disk('local')->delete($permit);
            }
            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => 'البريد الإلكتروني مستخدم بالفعل.']);
            }
            throw $exception;
        }
    }

    protected function registered(Request $request, $user)
    {
        $request->session()->regenerate();
        $intended = $request->session()->pull('url.intended');
        if (! $request->wantsJson() && $user->role === 'vendor') {
            return redirect()->route('vendor.dashboard');
        }
        if (! $request->wantsJson() && $intended === route('vendor.apply')) {
            return redirect()->route('vendor.apply');
        }
    }
}
