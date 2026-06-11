<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // Tambahkan ini

class ProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user();
        // Tambahkan full URL untuk avatar agar mudah dibaca Flutter
        if ($user->avatar) {
            $user->avatar_url = asset('storage/' . $user->avatar);
        } else {
            $user->avatar_url = null;
        }
        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // 💡 Validasi Input (Termasuk Email dan Foto)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                // Pastikan email unik, kecuali milik user ini sendiri
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string',
            'old_password' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi foto
        ]);

        $user->name = $request->name;
        $user->email = $request->email; // Update Email
        $user->phone = $request->phone;

        // 💡 Logika Upload Foto Profil
        if ($request->hasFile('avatar')) {
            // Hapus foto lama jika ada
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $image = $request->file('avatar');
            $imageName = time() . '_avatar.' . $image->getClientOriginalExtension();
            // Simpan di folder storage/app/public/avatars
            $imagePath = $image->storeAs('avatars', $imageName, 'public'); 
            $user->avatar = $imagePath;
        }

        // Jika user mengisi password baru
        if ($request->password) {
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => 'Password lama tidak sesuai!'
                ], 400); 
            }
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($user->avatar) {
            $user->avatar_url = asset('storage/' . $user->avatar);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
}