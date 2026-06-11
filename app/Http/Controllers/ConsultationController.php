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




// namespace App\Http\Controllers;

// use App\Http\Controllers\Controller;
// use App\Models\ConsultationMessage;
// use App\Models\Product;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Storage;

// class ConsultationController extends Controller
// {
//     public function getMessages()
//     {
//         $user = Auth::user();

//         $messages = ConsultationMessage::query()
//             ->where('user_id', $user->id)
//             ->orderBy('created_at', 'asc')
//             ->get();

//         return response()->json([
//             'status' => 'success',
//             'data' => $messages,
//         ]);
//     }

//     public function sendMessage(Request $request)
//     {
//         $validated = $request->validate([
//             'message' => ['nullable', 'string', 'max:5000'],
//             'image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
//         ]);

//         $userId = Auth::id();
//         $userMessage = trim($validated['message'] ?? '');
//         $imagePath = null;

//         if ($userMessage === '' && !$request->hasFile('image')) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Pesan atau gambar wajib diisi.',
//             ], 422);
//         }

//         if ($request->hasFile('image')) {
//             $imagePath = $request->file('image')->store('chat_images', 'public');
//         }

//         // Simpan pesan user
//         $userChat = ConsultationMessage::create([
//             'user_id'  => $userId,
//             'message'  => $userMessage !== '' ? $userMessage : null,
//             'is_admin' => 0,
//             'image'    => $imagePath,
//         ]);

//         $apiKey = config('services.gemini.api_key');
//         $model = config('services.gemini.model', 'gemini-2.5-flash-lite');

//         if (!$apiKey) {
//             $fallbackMessage = 'Maaf, konfigurasi AI belum tersedia. Admin kami akan segera membantu Anda.';

//             $adminChat = ConsultationMessage::create([
//                 'user_id'  => $userId,
//                 'message'  => $fallbackMessage,
//                 'is_admin' => 1,
//                 'image'    => null,
//             ]);

//             return response()->json([
//                 'status' => 'success',
//                 'user_message' => $userChat,
//                 'ai_message' => $adminChat,
//                 'recommended_products' => [],
//             ]);
//         }

//         $systemPrompt = <<<PROMPT
// Kamu adalah Customer Service Optik Alhazen yang ramah, profesional, singkat, dan membantu.

// Tugasmu HANYA menjawab pertanyaan yang berkaitan dengan:
// - kacamata
// - frame
// - lensa
// - kesehatan mata
// - pemeriksaan mata
// - bentuk wajah dan rekomendasi frame
// - layanan optik Optik Alhazen
// - lokasi toko
// - informasi umum produk optik

// Informasi penting:
// - harga frame mulai Rp 100.000
// - lensa antiradiasi mulai Rp 150.000
// - lokasi toko di Jalan Merdeka, Bireuen

// Aturan jawaban:
// - jawab dalam Bahasa Indonesia
// - ramah dan profesional
// - maksimal 2 paragraf
// - jangan mengarang informasi di luar data yang diberikan
// - jika ditanya di luar topik optik, arahkan dengan sopan bahwa kamu hanya melayani pertanyaan seputar optik Alhazen
// - jika user bertanya soal bentuk wajah, berikan saran jenis frame yang cocok
// PROMPT;

//         $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

//         try {
//             // Ambil riwayat 10 chat terakhir untuk konteks
//             $recentMessages = ConsultationMessage::query()
//                 ->where('user_id', $userId)
//                 ->orderBy('created_at', 'desc')
//                 ->limit(10)
//                 ->get()
//                 ->reverse()
//                 ->values();

//             $contents = [];

//             foreach ($recentMessages as $msg) {
//                 $role = ($msg->is_admin == 1) ? 'model' : 'user';

//                 $parts = [];

//                 if (!empty($msg->message)) {
//                     $parts[] = ['text' => $msg->message];
//                 }

//                 if (!empty($msg->image)) {
//                     $publicUrl = asset('storage/' . $msg->image);
//                     $parts[] = ['text' => 'User juga mengirim gambar: ' . $publicUrl];
//                 }

//                 if (!empty($parts)) {
//                     $contents[] = [
//                         'role' => $role,
//                         'parts' => $parts,
//                     ];
//                 }
//             }

//             // jaga-jaga kalau contents kosong
//             if (empty($contents)) {
//                 $parts = [];

//                 if ($userMessage !== '') {
//                     $parts[] = ['text' => $userMessage];
//                 }

//                 if ($imagePath) {
//                     $parts[] = ['text' => 'User juga mengirim gambar: ' . asset('storage/' . $imagePath)];
//                 }

//                 $contents[] = [
//                     'role' => 'user',
//                     'parts' => $parts,
//                 ];
//             }

//             $payload = [
//                 'system_instruction' => [
//                     'parts' => [
//                         ['text' => $systemPrompt],
//                     ],
//                 ],
//                 'contents' => $contents,
//                 'generationConfig' => [
//                     'temperature' => 0.7,
//                     'maxOutputTokens' => 300,
//                 ],
//             ];

//             $response = Http::acceptJson()
//                 ->timeout(45)
//                 ->post($url, $payload);

//             if (!$response->successful()) {
//                 Log::error('Gemini API error', [
//                     'status' => $response->status(),
//                     'body'   => $response->body(),
//                     'model'  => $model,
//                     'user_id' => $userId,
//                 ]);

//                 $fallbackMessage = 'Maaf, fitur asisten AI sedang gangguan teknis. Admin kami akan segera membalas Anda secara manual ya.';

//                 $adminChat = ConsultationMessage::create([
//                     'user_id'  => $userId,
//                     'message'  => $fallbackMessage,
//                     'is_admin' => 1,
//                     'image'    => null,
//                 ]);

//                 return response()->json([
//                     'status' => 'success',
//                     'user_message' => $userChat,
//                     'ai_message' => $adminChat,
//                     'recommended_products' => [],
//                 ]);
//             }

//             $data = $response->json();

//             $aiReply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

//             if (!$aiReply || trim($aiReply) === '') {
//                 Log::warning('Gemini response has no text', [
//                     'response' => $data,
//                     'user_id' => $userId,
//                 ]);

//                 $fallbackMessage = 'Maaf, saya belum bisa menjawab saat ini. Silakan coba beberapa saat lagi ya.';

//                 $adminChat = ConsultationMessage::create([
//                     'user_id'  => $userId,
//                     'message'  => $fallbackMessage,
//                     'is_admin' => 1,
//                     'image'    => null,
//                 ]);

//                 return response()->json([
//                     'status' => 'success',
//                     'user_message' => $userChat,
//                     'ai_message' => $adminChat,
//                     'recommended_products' => [],
//                 ]);
//             }

//             $aiReply = trim($aiReply);

//             $adminChat = ConsultationMessage::create([
//                 'user_id'  => $userId,
//                 'message'  => $aiReply,
//                 'is_admin' => 1,
//                 'image'    => null,
//             ]);

//             $recommendedProducts = $this->findRecommendedProducts($userMessage, $aiReply);

//             return response()->json([
//                 'status' => 'success',
//                 'user_message' => $userChat,
//                 'ai_message' => $adminChat,
//                 'recommended_products' => $recommendedProducts,
//             ]);
//         } catch (\Throwable $e) {
//             Log::error('Gemini integration exception', [
//                 'message' => $e->getMessage(),
//                 'user_id' => $userId,
//                 'line' => $e->getLine(),
//                 'file' => $e->getFile(),
//             ]);

//             $fallbackMessage = 'Maaf, fitur asisten AI sedang gangguan teknis. Admin kami akan segera membalas Anda secara manual ya.';

//             $adminChat = ConsultationMessage::create([
//                 'user_id'  => $userId,
//                 'message'  => $fallbackMessage,
//                 'is_admin' => 1,
//                 'image'    => null,
//             ]);

//             return response()->json([
//                 'status' => 'success',
//                 'user_message' => $userChat,
//                 'ai_message' => $adminChat,
//                 'recommended_products' => [],
//             ]);
//         }
//     }

//     private function findRecommendedProducts(?string $userMessage, ?string $aiReply)
//     {
//         $text = strtolower(trim(($userMessage ?? '') . ' ' . ($aiReply ?? '')));

//         $keywords = [];

//         // Mapping sederhana rekomendasi bentuk wajah
//         if (str_contains($text, 'bulat')) {
//             $keywords = ['kotak', 'persegi', 'square', 'rectangle'];
//         } elseif (str_contains($text, 'oval')) {
//             $keywords = ['oval', 'aviator', 'kotak'];
//         } elseif (str_contains($text, 'persegi')) {
//             $keywords = ['bulat', 'oval', 'round'];
//         } elseif (str_contains($text, 'hati')) {
//             $keywords = ['oval', 'rimless', 'tipis'];
//         } elseif (
//             str_contains($text, 'kacamata') ||
//             str_contains($text, 'frame') ||
//             str_contains($text, 'rekomendasi')
//         ) {
//             $keywords = ['frame', 'kacamata'];
//         }

//         if (empty($keywords)) {
//             return [];
//         }

//         $query = Product::with(['images', 'primaryImage'])
//             ->where('stock', '>', 0)
//             ->where('category', '!=', 'aksesoris')
//             ->where(function ($q) use ($keywords) {
//                 foreach ($keywords as $keyword) {
//                     $q->orWhere('name', 'like', '%' . $keyword . '%')
//                       ->orWhere('description', 'like', '%' . $keyword . '%');
//                 }
//             })
//             ->limit(5)
//             ->get();

//         return $query->values();
//     }
// }