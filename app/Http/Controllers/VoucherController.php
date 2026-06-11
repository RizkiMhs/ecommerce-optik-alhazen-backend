<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    // 1. Mengambil semua voucher yang sedang aktif
    public function index()
    {
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', now());
            })->orderBy('created_at', 'desc')->get();

        return response()->json(['status' => 'success', 'data' => $vouchers]);
    }

    // 2. Memvalidasi dan menghitung diskon voucher
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_belanja' => 'required|numeric' // Butuh info subtotal untuk ngecek min. belanja
        ]);

        $voucher = Voucher::where('code', $request->code)->where('is_active', true)->first();

        // Cek Eksistensi
        if (!$voucher) {
            return response()->json(['status' => 'error', 'message' => 'Voucher tidak ditemukan atau sudah tidak aktif.'], 404);
        }

        // Cek Kadaluarsa
        if ($voucher->valid_until && $voucher->valid_until < now()) {
            return response()->json(['status' => 'error', 'message' => 'Maaf, voucher ini sudah kadaluarsa.'], 400);
        }

        // Cek Minimal Belanja
        if ($request->total_belanja < $voucher->min_purchase) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Minimal belanja Rp ' . number_format($voucher->min_purchase, 0, ',', '.') . ' belum terpenuhi.'
            ], 400);
        }

        // Hitung Potongan Diskon
        $discountAmount = 0;
        if ($voucher->discount_type === 'percent') {
            $discountAmount = ($request->total_belanja * $voucher->discount_value) / 100;
        } else {
            $discountAmount = $voucher->discount_value;
        }

        // Jangan sampai diskon lebih besar dari total belanja
        if ($discountAmount > $request->total_belanja) {
            $discountAmount = $request->total_belanja;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil dipasang!',
            'data' => [
                'voucher' => $voucher,
                'discount_amount' => $discountAmount
            ]
        ]);
    }
}
