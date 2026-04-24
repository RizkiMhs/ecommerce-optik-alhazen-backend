<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 2. Attempt login
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah']
            ]);
        }

        // 3. Ambil user
        $user = Auth::user();

        // 4. Hapus token lama (opsional, biar tidak numpuk)
        $user->tokens()->delete();

        // 5. Generate token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Response JSON lebih lengkap
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 200);
    }
}