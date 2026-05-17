<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ConsultationMessage;
use Illuminate\Support\Facades\Auth;
// use App\Models\User;

class ConsultationController extends Controller
{
    //
    // 1. FUNGSI UNTUK MENGAMBIL RIWAYAT CHAT
    public function getMessages()
    {
        // Ambil data user yang sedang login dari token
        $user = Auth::user();

        // Cari semua pesan milik user ini, urutkan dari yang terlama ke terbaru (asc)
        // Ubah baris ini:
        $messages = ConsultationMessage::query()->where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages
        ]);
    }

    // 2. FUNGSI UNTUK MENGIRIM PESAN BARU DARI APP FLUTTER
    public function sendMessage(Request $request)
    {
       $request->validate([
            'message' => 'nullable|string', // Boleh kosong jika hanya kirim gambar
            'image' => 'nullable|image|mimes:jpeg,png,jpg', // Maksimal 2MB
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Simpan gambar ke folder storage/app/public/chat_images
            $imagePath = $request->file('image')->store('chat_images', 'public');
        }

        $message = ConsultationMessage::create([
            'user_id' => Auth::id(), // Sesuaikan dengan cara auth Anda
            'message' => $request->message ?? '',
            'image' => $imagePath,
            'is_admin' => false,
        ]);

        // (Opsional) Tambahkan full URL gambar agar mudah dibaca Flutter
        $message->image_url = $imagePath ? asset('storage/' . $imagePath) : null;

        return response()->json([
            'status' => 'success',
            'data' => $message
        ]);
    }
}
