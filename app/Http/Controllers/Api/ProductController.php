<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\LensType;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        // 1. Ambil data Frame/Aksesoris (Hanya yang stoknya masih ada)
        $products = Product::where('stock', '>', 0)->get();

        // 2. Ambil data Master Lensa
        $lenses = LensType::all();

        // 3. Gabungkan dan kirim sebagai format JSON
        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'Berhasil mengambil data katalog Optik Alhazen'
            ],
            'data' => [
                'products' => $products,
                'lenses' => $lenses
            ]
        ]);
    }
}
