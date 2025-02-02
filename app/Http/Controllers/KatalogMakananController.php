<?php

namespace App\Http\Controllers;

use App\Models\Katalog_Makanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KatalogMakananController extends Controller
{
    // Mendapatkan semua data makanan
    public function getAll()
    {
        $makanan = Katalog_Makanan::all();
        return response()->json($makanan, 200);
    }

    // Mendapatkan detail makanan berdasarkan ID
    public function getById($id_makanan)
    {
        $makanan = Katalog_Makanan::findOrFail($id_makanan);
        return response()->json($makanan, 200);
    }

    // Membuat data makanan baru
    public function create(Request $request)
    {
        $request->validate([
            'nama_makanan' => 'required|string|max:100',
            'harga' => 'required|numeric',
            'kategori' => 'required|string',
            'deskripsi' => 'nullable|string',
            'foto_makanan' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:tersedia,tidak_tersedia'
        ]);

        // Upload foto makanan
        if ($request->hasFile('foto_makanan')) {
            $foto = $request->file('foto_makanan');
            $path = $foto->store('public/makanan');
            $foto_path = Storage::url($path);
        }

        $makanan = Katalog_Makanan::create([
            'nama_makanan' => $request->nama_makanan,
            'harga' => $request->harga,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'foto_makanan' => $foto_path,
            'status' => $request->status
        ]);

        return response()->json($makanan, 201);
    }

    // Mengupdate data makanan
    public function update(Request $request, $id_makanan)
    {
        $makanan = Katalog_Makanan::findOrFail($id_makanan);

        $request->validate([
            'nama_makanan' => 'sometimes|required|string|max:100',
            'harga' => 'sometimes|required|numeric',
            'kategori' => 'sometimes|required|',
            'deskripsi' => 'nullable|string',
            'foto_makanan' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|required|in:tersedia,tidak_tersedia'
        ]);

        // Update foto makanan jika ada
        if ($request->hasFile('foto_makanan')) {
            // Hapus foto lama
            Storage::delete(str_replace('/storage', 'public', $makanan->foto_makanan));
            
            // Upload foto baru
            $foto = $request->file('foto_makanan');
            $path = $foto->store('public/makanan');
            $foto_path = Storage::url($path);
            $request->merge(['foto_makanan' => $foto_path]);
        }

        $makanan->update($request->all());

        return response()->json($makanan, 200);
    }

    // Menghapus data makanan
    public function delete($id_makanan)
    {
        $makanan = Katalog_Makanan::findOrFail($id_makanan);
        
        // Hapus foto makanan dari storage
        Storage::delete(str_replace('/storage', 'public', $makanan->foto_makanan));
        
        $makanan->delete();

        return response()->json(null, 204);
    }

    // Mengubah status makanan
    public function updateStatus(Request $request, $id_makanan)
    {
        $request->validate([
            'status' => 'required|in:tersedia,tidak_tersedia'
        ]);

        $makanan = Katalog_Makanan::findOrFail($id_makanan);
        $makanan->update([
            'status' => $request->status
        ]);

        return response()->json($makanan, 200);
    }
}