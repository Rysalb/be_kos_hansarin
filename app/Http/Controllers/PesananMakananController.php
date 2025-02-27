<?php

namespace App\Http\Controllers;

use App\Models\Pesanan_Makanan;
use App\Models\Katalog_Makanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    DB::beginTransaction();
    try {
        $request->validate([
            'id_penyewa' => 'required|exists:penyewa,id_penyewa',
            'id_pembayaran' => 'required|exists:pembayaran,id_pembayaran',
            'pesanan' => 'required|array',
            'total_harga' => 'required|numeric',
        ]);

        foreach ($request->pesanan as $item) {
            $makanan = Katalog_Makanan::findOrFail($item['id_makanan']);
            
            if ($makanan->stock < $item['jumlah']) {
                throw new \Exception("Stok {$makanan->nama_makanan} tidak mencukupi");
            }

            // Reduce stock
            $makanan->stock -= $item['jumlah'];
            $makanan->save();

            // Create order
            Pesanan_Makanan::create([
                'id_penyewa' => $request->id_penyewa,
                'id_pembayaran' => $request->id_pembayaran,
                'id_makanan' => $item['id_makanan'],
                'jumlah' => $item['jumlah'],
                'total_harga' => $item['total_harga'],
                'status_pesanan' => 'pending'
            ]);
        }

        DB::commit();
        return response()->json([
            'message' => 'Pesanan berhasil dibuat',
            'status' => true
        ], 201);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'message' => $e->getMessage(),
            'status' => false
        ], 400);
    }
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