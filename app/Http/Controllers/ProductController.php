<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{

    public function index()
    {
        // 💡 UPDATE: Tambahkan where('stock', '>', 0) agar produk yang stoknya habis tidak ditarik dari database
        $products = \App\Models\Product::with(['images', 'primaryImage'])
            ->where('stock', '>', 0)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
