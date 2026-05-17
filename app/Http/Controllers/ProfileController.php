<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // 💡 Tambahkan validasi untuk old_password
        $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'old_password' => 'nullable|string',
            'password' => 'nullable'
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;

        // Jika user mengisi password baru
        if ($request->password) {
            // 💡 Cek apakah password lama yang dimasukkan cocok dengan yang di database
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => 'Password lama tidak sesuai!'
                ], 400); // 400 = Bad Request (Gagal)
            }

            // Jika cocok, baru ubah ke password baru
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated',
            'data' => $user
        ]);
    }
}
