<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])
            ->first();

        if (!$user || !Hash::check(
            $validated['password'],
            $user->password
        )) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->role !== 'admin' ||
            $user->status !== 'active') {

            return response()->json([
                'message' => 'Access denied',
            ], 403);
        }

        $token = $user->createToken('admin-token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
}
