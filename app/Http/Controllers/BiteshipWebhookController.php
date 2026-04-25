<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class BiteshipWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil data yang dikirim oleh server Biteship
        $biteshipId = $request->input('order_id');
        $status = $request->input('status'); // Contoh: 'allocated', 'picking_up', 'delivered'

        // Simpan log untuk berjaga-jaga
        Log::info("Webhook Biteship Masuk: Order $biteshipId status menjadi $status");

        // 2. Cari pesanan di database kita berdasarkan ID Biteship
        $order = Order::where('biteship_order_id', $biteshipId)->first();

        if ($order) {
            // 3. Jika status dari kurir adalah "delivered" (Terkirim)
            if ($status === 'delivered') {
                $order->update([
                    'status' => 'completed'
                ]);
            } 
            // Opsional: Jika kurir gagal kirim atau barang hilang
            elseif ($status === 'cancelled' || $status === 'rejected') {
                $order->update([
                    'status' => 'cancelled'
                ]);
            }
            
            return response()->json(['message' => 'Status pesanan berhasil diupdate otomatis!'], 200);
        }

        return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
    }
}
