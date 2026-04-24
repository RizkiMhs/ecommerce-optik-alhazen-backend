<?php

namespace App\Http\Controllers; // Pastikan namespace-nya Api jika file ini ada di dalam folder Api

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Carts; // Wajib di-uncomment
use App\Models\Product;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // 1. Validasi Input dari Flutter
        $validator = Validator::make($request->all(), [
            'product_id'   => 'required|exists:products,id',
            // Nullable agar bisa menerima input produk aksesoris (yang tidak butuh lensa)
            'lens_type_id' => 'nullable|exists:lens_types,id',
            'qty'          => 'nullable|integer|min:1',

            // Validasi Resep Mata (Semua nullable karena opsional)
            'sph_right'    => 'nullable|string',
            'cyl_right'    => 'nullable|string',
            'axis_right'   => 'nullable|string', // Tambahan sesuai struktur migration terbaru
            'sph_left'     => 'nullable|string',
            'cyl_left'     => 'nullable|string',
            'axis_left'    => 'nullable|string', // Tambahan sesuai struktur migration terbaru
            'pd'           => 'nullable|string',
            'note'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Simpan ke Database dengan perlindungan Try-Catch
        try {
            $cartItem = Carts::create([
                // Mengambil ID user berdasarkan token login (Sanctum) yang dikirim dari Flutter
                'user_id'      => $request->user()->id,

                'product_id'   => $request->product_id,
                'lens_type_id' => $request->lens_type_id,
                'qty'          => $request->qty ?? 1, // Jika Flutter tidak kirim qty, default jadi 1

                // Data Resep
                'sph_right'    => $request->sph_right,
                'cyl_right'    => $request->cyl_right,
                'axis_right'   => $request->axis_right,
                'sph_left'     => $request->sph_left,
                'cyl_left'     => $request->cyl_left,
                'axis_left'    => $request->axis_left,
                'pd'           => $request->pd,
                'note'         => $request->note,
            ]);

            // 3. Berikan response sukses ke Flutter (Code 201: Created)
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil dimasukkan ke keranjang!',
                'data' => $cartItem
            ], 201);
        } catch (\Exception $e) {
            // Jika terjadi gagal simpan ke database (misal server down)
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
            ], 500);
        }
    }

    // 1. Mengambil semua isi keranjang milik user yang login
    public function index(Request $request)
    {
        $cartItems = Carts::with([
            'product.primaryImage', // ambil produk + gambar utama
            'lensType'               // ambil lensa
        ])
            ->where('user_id', $request->user()->id)
            ->get();

        // Tambahkan field image_url untuk Flutter
        $cartItems->map(function ($item) {
            if ($item->product && $item->product->primaryImage) {
                $filename = basename($item->product->primaryImage->image_name);
                $item->product->image_url = asset('storage/product-images/' . $filename);
            } else {
                $item->product->image_url = null;
            }
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => $cartItems
        ]);
    }

    // 2. Mengubah QTY (Jumlah) barang di keranjang
    public function update(Request $request, $id)
    {
        $cart = Carts::where('user_id', $request->user()->id)->findOrFail($id);
        $cart->update(['qty' => $request->qty]);

        return response()->json(['message' => 'Jumlah berhasil diperbarui']);
    }

    // 3. Menghapus satu item dari keranjang
    public function destroy(Request $request, $id)
    {
        $cart = Carts::where('user_id', $request->user()->id)->findOrFail($id);
        $cart->delete();

        return response()->json(['message' => 'Item berhasil dihapus']);
    }
}
