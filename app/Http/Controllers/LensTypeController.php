<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LensType;

class LensTypeController extends Controller
{
    public function index()
    {
        // Ambil semua lens type
        $lensTypes = LensType::all();

        return response()->json($lensTypes);
    }
}