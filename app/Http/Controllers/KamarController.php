<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use Illuminate\Http\Request;

class KamarController extends Controller
{
    // Function untuk menambahkan kamar baru
    public function create(Request $request)
    {
        try {
            $request->validate([
                'nomor_kamar' => 'sometimes|required|string|max:10',
                'tipe_kamar' => 'sometimes|required|',
                'status' => 'sometimes|required|in:tersedia,terisi',
                'harga_sewa' => 'sometimes|required|numeric',
                'harga_sewa1' => 'sometimes|nullable|numeric',
                'harga_sewa2' => 'sometimes|nullable|numeric',
                'harga_sewa3' => 'sometimes|nullable|numeric',
                'harga_sewa4' => 'sometimes|nullable|numeric',
                'jumlah_unit' => 'sometimes|required|numeric',
            ]);

            $kamar = Kamar::create($request->all());

            return response()->json($kamar, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Function untuk memperbarui kamar
    public function update(Request $request, $id)
    {
        try {
            $kamar = Kamar::findOrFail($id);
            $kamar->update($request->all());

            return response()->json($kamar, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Function untuk menghapus kamar
    public function delete($id)
    {
        try {
            $kamar = Kamar::findOrFail($id);
            $kamar->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Function untuk mendapatkan semua kamar
    public function getAll()
    {
        try {
            $kamar = Kamar::all(); // Mengambil semua data kamar
            return response()->json($kamar, 200); // Mengembalikan response JSON dengan status 200
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
