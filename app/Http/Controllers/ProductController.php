<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
//     public function index()
// {
//     $products = \App\Models\Product::with('primaryImage')->get();

//     $products->map(function ($product) {

//         if ($product->primaryImage && $product->primaryImage->image_name) {

//             $filename = basename($product->primaryImage->image_name);

//             $product->image_url = asset('storage/product-images/' . $filename);

//         } else {
//             $product->image_url = null; // 🔥 penting
//         }

//         return $product;
//     });

//     return response()->json($products);
// }

public function index()
    {
        // 💡 UPDATE: Kita memanggil relasi 'images' (untuk semua gambar) 
        // dan 'primaryImage' (jika butuh gambar utama) sekaligus.
        $products = \App\Models\Product::with(['images', 'primaryImage'])->get();

        // Tidak perlu lagi pakai $products->map(...) karena 'image_url' 
        // sudah otomatis dibuatkan oleh Model Product.php yang kita edit tadi!

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
