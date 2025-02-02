<?php

namespace App\Http\Controllers;

use App\Models\Pesanan_Makanan;
use App\Models\Katalog_Makanan;
use Illuminate\Http\Request;

class PesananMakananController extends Controller
{
    // Mendapatkan semua pesanan
    public function getAll()
    {
        $pesanan = Pesanan_Makanan::with(['penyewa', 'makanan'])->get();
        return response()->json($pesanan, 200);
    }

    // Mendapatkan detail pesanan berdasarkan ID
    public function getById($id_pesanan)
    {
        $pesanan = Pesanan_Makanan::with(['penyewa', 'makanan'])
            ->where('id_pesanan', $id_pesanan)
            ->firstOrFail();
        return response()->json($pesanan, 200);
    }

    // Membuat pesanan baru
    public function create(Request $request)
    {
        $request->validate([
            'id_penyewa' => 'required|exists:penyewa,id_penyewa',
            'id_makanan' => 'required|exists:katalog_makanan,id_makanan',
            'jumlah' => 'required|integer|min:1',
        ]);

        // Mengambil harga makanan dari katalog
        $makanan = Katalog_Makanan::findOrFail($request->id_makanan);
        $total_harga = $makanan->harga * $request->jumlah;

        $pesanan = Pesanan_Makanan::create([
            'id_penyewa' => $request->id_penyewa,
            'id_makanan' => $request->id_makanan,
            'jumlah' => $request->jumlah,
            'total_harga' => $total_harga,
            'status_pesanan' => 'pending'
        ]);

        return response()->json($pesanan, 201);
    }

    // Mengupdate status pesanan
    public function updateStatus(Request $request, $id_pesanan)
    {
        $request->validate([
            'status_pesanan' => 'required|in:pending,diproses,selesai'
        ]);

        $pesanan = Pesanan_Makanan::findOrFail($id_pesanan);
        $pesanan->update([
            'status_pesanan' => $request->status_pesanan
        ]);

        return response()->json($pesanan, 200);
    }

    // Mengupdate jumlah pesanan
    public function update(Request $request, $id_pesanan)
    {
        $pesanan = Pesanan_Makanan::findOrFail($id_pesanan);

        $request->validate([
            'jumlah' => 'required|integer|min:1'
        ]);

        // Menghitung ulang total harga
        $makanan = Katalog_Makanan::findOrFail($pesanan->id_makanan);
        $total_harga = $makanan->harga * $request->jumlah;

        $pesanan->update([
            'jumlah' => $request->jumlah,
            'total_harga' => $total_harga
        ]);

        return response()->json($pesanan, 200);
    }

    // Menghapus pesanan
    public function delete($id_pesanan)
    {
        $pesanan = Pesanan_Makanan::findOrFail($id_pesanan);
        $pesanan->delete();

        return response()->json(null, 204);
    }

    // Mendapatkan pesanan berdasarkan penyewa
    public function getByPenyewa($id_penyewa)
    {
        $pesanan = Pesanan_Makanan::with(['makanan'])
            ->where('id_penyewa', $id_penyewa)
            ->get();
        return response()->json($pesanan, 200);
    }
}