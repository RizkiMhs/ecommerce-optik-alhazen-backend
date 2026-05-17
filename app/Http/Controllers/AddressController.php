<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Address;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->addresses;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required',
            'recipient_name' => 'required',
            'phone' => 'required',
            'complete_address' => 'required',
            'city_id' => 'required',
            'postal_code' => 'required',
            'is_main' => 'boolean'
        ]);

        $data['user_id'] = $request->user()->id;

        if ($data['is_main']) {
            Address::where('user_id', $data['user_id'])->update(['is_main' => false]);
        }

        return Address::create($data);
    }

    public function update(Request $request, $id)
    {
        $address = Address::findOrFail($id);

        $data = $request->all();

        if ($request->is_main) {
            Address::where('user_id', $address->user_id)->update(['is_main' => false]);
        }

        $address->update($data);

        return $address;
    }

    public function destroy($id)
    {
        Address::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function getCities(Request $request)
    {
        try {
            $search = $request->query('search', '');

            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_API_KEY')
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                'search' => $search
            ]);

            $data = $response->json();

            if (isset($data['data']) && is_array($data['data'])) {
                $formattedCities = collect($data['data'])->map(function ($city) {
                    
                    // 💡 BARU: Mencoba mengambil kode pos (5 angka terakhir di string label)
                    $postalCode = '';
                    if (isset($city['label']) && preg_match('/(\d{5})$/', trim($city['label']), $matches)) {
                        $postalCode = $matches[1];
                    }

                    return [
                        'id' => $city['id'] ?? null,
                        'name' => $city['label'] ?? $city['name'] ?? 'Tanpa Nama',
                        'postal_code' => $postalCode, // 💡 Kirim kode pos ini ke Flutter
                    ];
                });

                return response()->json([
                    'status' => 'success',
                    'data' => $formattedCities
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Format data API tidak sesuai',
                'raw_debug' => $data // Tambahkan ini untuk intip isi aslinya jika masih gagal
            ], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
