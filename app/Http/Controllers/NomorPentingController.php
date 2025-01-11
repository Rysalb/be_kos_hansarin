<?php

namespace App\Http\Controllers;

use App\Models\NomorPenting;
use Illuminate\Http\Request;

class NomorPentingController extends Controller
{
    // Mendapatkan semua nomor penting
    public function getAll()
    {
        $nomorPenting = NomorPenting::all();
        return response()->json($nomorPenting, 200);
    }

    // Mendapatkan detail nomor penting berdasarkan ID
    public function getById($id_nomor)
    {
        $nomorPenting = NomorPenting::findOrFail($id_nomor);
        return response()->json($nomorPenting, 200);
    }

    // Membuat nomor penting baru
    public function create(Request $request)
    {
        $request->validate([
            'nama_kontak' => 'required|string|max:100',
            'nomor_telepon' => 'required|string|max:15',
            'kategori' => 'required|string|max:50',
            'keterangan' => 'nullable|string'
        ]);

        $nomorPenting = NomorPenting::create($request->all());

        return response()->json($nomorPenting, 201);
    }

    // Mengupdate nomor penting
    public function update(Request $request, $id_nomor)
    {
        $nomorPenting = NomorPenting::findOrFail($id_nomor);

        $request->validate([
            'nama_kontak' => 'sometimes|required|string|max:100',
            'nomor_telepon' => 'sometimes|required|string|max:15',
            'kategori' => 'sometimes|required|string|max:50',
            'keterangan' => 'nullable|string'
        ]);

        $nomorPenting->update($request->all());

        return response()->json($nomorPenting, 200);
    }

    // Menghapus nomor penting
    public function delete($id_nomor)
    {
        $nomorPenting = NomorPenting::findOrFail($id_nomor);
        $nomorPenting->delete();

        return response()->json(null, 204);
    }

    // Mencari nomor penting berdasarkan kategori
    public function getByKategori($kategori)
    {
        $nomorPenting = NomorPenting::where('kategori', $kategori)->get();
        return response()->json($nomorPenting, 200);
    }
}