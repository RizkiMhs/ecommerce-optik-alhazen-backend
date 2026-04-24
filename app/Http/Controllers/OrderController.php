<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // 💡 WAJIB DITAMBAHKAN
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Carts;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log; // Untuk debugging
use Midtrans\Config;
use Midtrans\Snap;

class OrderController extends Controller
{
    // ... (fungsi checkout yang sudah kita buat sebelumnya biarkan saja di sini) ...
    public function checkout(Request $request)
    {
        // 1. Validasi Input dari Flutter
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'shipping_cost' => 'required|numeric',
            'cart_ids' => 'required|array',      // Berisi array ID keranjang yang dicentang
            'cart_ids.*' => 'exists:carts,id',
        ]);

        // MULAI TRANSAKSI DATABASE (Aman dari error separuh jalan)
        DB::beginTransaction();

        try {
            // 2. Ambil data keranjang yang dipilih
            $cartItems = Carts::with(['product', 'lensType'])
                ->where('user_id', $request->user()->id)
                ->whereIn('id', $request->cart_ids)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Tidak ada barang yang dipilih'], 400);
            }

            // 3. Hitung Total Pembayaran
            $subtotalProduk = 0;
            foreach ($cartItems as $item) {
                $basePrice = $item->product->base_price;
                $lensPrice = $item->lensType ? $item->lensType->additional_price : 0;
                $subtotalProduk += (($basePrice + $lensPrice) * $item->qty);
            }
            $grandTotal = $subtotalProduk + $request->shipping_cost;

            // 4. Buat Induk Pesanan (Tabel Orders)
            $order = Order::create([
                'user_id'       => $request->user()->id,
                'address_id'    => $request->address_id,
                'shipping_cost' => $request->shipping_cost,
                'total_amount'  => $grandTotal,
                'status'        => 'pending', // Status awal: Menunggu Pembayaran
            ]);

            // 5. Pindahkan data Keranjang ke Detail Pesanan (Tabel Order Items)
            foreach ($cartItems as $item) {
                $basePrice = $item->product->base_price;
                $lensPrice = $item->lensType ? $item->lensType->additional_price : 0;
                $itemPrice = $basePrice + $lensPrice;

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item->product_id,
                    'lens_type_id' => $item->lens_type_id,
                    'qty'          => $item->qty,
                    'price'        => $itemPrice, // Harga satuan saat di-checkout (penting jika harga produk berubah nanti)

                    // Pindahkan Data Resep Mata
                    'sph_right'  => $item->sph_right,
                    'sph_left'   => $item->sph_left,
                    'cyl_right'  => $item->cyl_right,
                    'cyl_left'   => $item->cyl_left,
                    'axis_right' => $item->axis_right,
                    'axis_left'  => $item->axis_left,
                    'pd'         => $item->pd,
                    'note'       => $item->note,
                ]);
            }

            // 6. KOSONGKAN KERANJANG (Hanya barang yang di-checkout)
            Carts::whereIn('id', $request->cart_ids)->delete();

            // SIMPAN PERMANEN KE DATABASE
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pesanan berhasil dibuat!',
                'order_id' => $order->id // Berguna nanti untuk diarahkan ke halaman pembayaran
            ], 201);
        } catch (\Exception $e) {
            // JIKA TERJADI ERROR, BATALKAN SEMUA PERUBAHAN
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
    // 💡 FUNGSI BARU: Cek Ongkos Kirim
    // 
    

    public function checkOngkir(Request $request)
    {
        $request->validate([
            'destination_city_id' => 'required', 
        ]);

        try {
            // 💡 1. Gunakan URL resmi RajaOngkir V2 dari Komerce
            // 💡 2. Wajib menggunakan asForm() karena mereka meminta format 'x-www-form-urlencoded'
            $response = Http::asForm()->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY')
            ])->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
                'origin'      => env('RAJAONGKIR_ORIGIN_CITY'), // 11 (Aceh Utara)
                'destination' => $request->destination_city_id,
                'weight'      => 500, // 500 gram
                'courier'     => 'jne' 
            ]);

            $data = $response->json();

            // 💡 3. Format balasan dari RajaOngkir V2
            if (isset($data['meta']['code']) && $data['meta']['code'] == 200 && !empty($data['data'])) {
                
                // Kita ambil opsi pengiriman urutan pertama (biasanya REG / Reguler)
                $kurirData = $data['data'][0];
                
                return response()->json([
                    'status' => 'success',
                    'courier' => strtoupper($kurirData['code']) . ' ' . $kurirData['service'], // Contoh output: "JNE REG"
                    'etd' => $kurirData['etd'],
                    'shipping_cost' => $kurirData['cost']
                ]);
            }

            // Jika kota tidak didukung atau ada error dari API
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan pengiriman tidak tersedia ke kota ini.',
                'debug' => $data // Ini agar jika error lagi, pesan aslinya bisa kita baca di Flutter
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data ongkir: ' . $e->getMessage()
            ], 500);
        }
    }

    // buat pesanan

    public function store(Request $request)
    {
        // // 💡 1. CATAT APA YANG DITERIMA DARI FLUTTER
        // Log::info('====== DATA DARI FLUTTER ======');
        // Log::info($request->all());
        // 1. Validasi data yang dikirim dari Flutter
        $request->validate([
            'shipping_cost' => 'required|numeric',
            'courier' => 'required|string',
            'recipient_name' => 'required|string',
            'phone' => 'required|string',
            'full_address' => 'required|string', // Alamat lengkap + kode pos
        ]);

        $user = Auth::user();
        $carts = Carts::where('user_id', $user->id)->with(['product', 'lensType'])->get();

        if ($carts->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong'], 400);
        }

        try {
            DB::beginTransaction();

            // ... (Kodingan perhitungan Subtotal & Grand Total Anda tetap sama) ...
            $subtotal = 0;
            foreach ($carts as $cart) {
                $basePrice = $cart->product->base_price;
                $lensPrice = $cart->lensType ? $cart->lensType->additional_price : 0;
                $subtotal += ($basePrice + $lensPrice) * $cart->qty;
            }
            $totalAmount = $subtotal + $request->shipping_cost;

            $addressSnapshot = json_encode([
                'name' => $request->recipient_name,
                'phone' => $request->phone,
                'address' => $request->full_address,
            ]);

            // 1. Buat Order Utama
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid()),
                'total_amount' => $totalAmount,
                'shipping_cost' => $request->shipping_cost,
                'status' => 'unpaid',
                'address_snapshot' => $addressSnapshot,
                'courier' => $request->courier,
            ]);

            // 2. Siapkan array untuk dikirim ke Midtrans (Harus sangat akurat harganya!)
            $item_details = [];

            // 3. Pindahkan Cart ke Order Items & Masukkan ke $item_details
            foreach ($carts as $cart) {
                $basePrice = $cart->product->base_price;
                $lensPrice = $cart->lensType ? $cart->lensType->additional_price : 0;
                $pricePerItem = $basePrice + $lensPrice;

                // Urusan Database Kita
                $prescriptionJson = null;
                if ($cart->sph_right || $cart->sph_left || $cart->cyl_right || $cart->cyl_left || $cart->pd || $cart->note) {
                    $prescriptionJson = json_encode([
                        'sph_right' => $cart->sph_right, 'cyl_right' => $cart->cyl_right, 'axis_right' => $cart->axis_right,
                        'sph_left' => $cart->sph_left, 'cyl_left' => $cart->cyl_left, 'axis_left' => $cart->axis_left,
                        'pd' => $cart->pd, 'note' => $cart->note,
                    ]);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'lens_type_id' => $cart->lens_type_id,
                    'price_at_purchase' => $pricePerItem,
                    'qty' => $cart->qty,
                    'prescription_data' => $prescriptionJson,
                ]);

                // Urusan Midtrans: Masukkan barang ke nota Midtrans
                $item_details[] = [
                    'id' => 'PRD-' . $cart->product_id,
                    'price' => (int) $pricePerItem,
                    'quantity' => $cart->qty,
                    'name' => substr($cart->product->name, 0, 50) // Midtrans maksimal 50 huruf
                ];
            }

            // 4. Tambahkan Ongkos Kirim sebagai "Barang" di nota Midtrans
            $item_details[] = [
                'id' => 'SHIPPING',
                'price' => (int) $request->shipping_cost,
                'quantity' => 1,
                'name' => 'Ongkir ' . $request->courier
            ];

            // 5. Kosongkan Keranjang
            Carts::where('user_id', $user->id)->delete();

            // ==========================================
            // 💡 BLOK INTEGRASI MIDTRANS (VERSI AMAN & STABIL)
            // ==========================================
            
            Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            Config::$isSanitized = true;
            Config::$is3ds = true;

            // 💡 PARAMETER MIDTRANS (Tanpa pembatasan metode pembayaran)
            $params = [
                'transaction_details' => [
                    'order_id' => $order->order_number,
                    'gross_amount' => (int) $totalAmount,
                ],
                'customer_details' => [
                    'first_name' => $request->recipient_name,
                    'phone' => $request->phone,
                    'email' => $user->email,
                ],
                'item_details' => $item_details,
                // Kita biarkan kosong tanpa 'enabled_payments' agar Midtrans
                // otomatis menampilkan semua opsi yang aktif di akun Anda.
            ];

            // Minta Snap Token ke Midtrans
            $snapToken = Snap::getSnapToken($params);

            // Simpan token tersebut ke database kita
            $order->update(['payment_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pesanan berhasil dibuat',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'payment_token' => $snapToken 
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 💡 FUNGSI BARU: Menerima notifikasi otomatis dari Midtrans
    public function callback(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        
        // 1. Keamanan: Pastikan pesan ini benar-benar dari Midtrans (bukan hacker)
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        
        if ($hashed == $request->signature_key) {
            // 2. Cari pesanan di database berdasarkan order_number dari Midtrans
            $order = Order::where('order_number', $request->order_id)->first();
            
            if ($order) {
                // 3. Cek status transaksinya apa
                if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                    // Jika sukses dibayar
                    $order->update(['status' => 'paid']);
                    Log::info('Pesanan Lunas: ' . $order->order_number);
                } 
                else if ($request->transaction_status == 'cancel' || $request->transaction_status == 'deny' || $request->transaction_status == 'expire') {
                    // Jika dibatalkan atau kadaluarsa
                    $order->update(['status' => 'cancelled']);
                }
                else if ($request->transaction_status == 'pending') {
                    // Jika masih menunggu pembayaran
                    $order->update(['status' => 'unpaid']);
                }
            }
            
            // Beri tahu Midtrans bahwa pesan sudah kita terima dengan baik
            return response()->json(['message' => 'Callback diterima']);
        }

        // Jika pesan palsu / bukan dari Midtrans
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    // 💡 FUNGSI BARU: Mengambil riwayat pesanan user
    public function index()
    {
        $user = Auth::user();

        // Ambil pesanan milik user ini, sertakan juga detail item dan produknya
        // Urutkan dari yang terbaru (created_at descending)
        // 💡 UPDATE: Tambahkan .primaryImage agar gambar langsung di-load
        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.product.primaryImage', 'orderItems.lensType'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
}
