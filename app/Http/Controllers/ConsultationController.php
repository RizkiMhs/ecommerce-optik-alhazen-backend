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
        // Validasi pesannya tidak boleh kosong
        $request->validate([
            'message' => 'required|string'
        ]);

        $user = Auth::user();

        // Simpan ke database
        $message = ConsultationMessage::create([
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin' => false, // Selalu false karena ini API untuk Kustomer
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil terkirim',
            'data' => $message
        ]);
    }
}
