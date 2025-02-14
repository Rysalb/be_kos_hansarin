<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    // Mendapatkan semua data pembayaran
    public function getAll()
    {
        $pembayaran = Pembayaran::with('penyewa')->get();
        return response()->json($pembayaran, 200);
    }

    // Mendapatkan detail pembayaran berdasarkan ID
    public function getById($id_pembayaran)
    {
        $pembayaran = Pembayaran::with('penyewa')
            ->where('id_pembayaran', $id_pembayaran)
            ->firstOrFail();
        return response()->json($pembayaran, 200);
    }

    // Membuat data pembayaran baru
    public function create(Request $request)
    {
        $request->validate([
            'id_penyewa' => 'required|exists:penyewa,id_penyewa',
            'tanggal_pembayaran' => 'required|date',
            'jumlah_pembayaran' => 'required|numeric',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'status_verifikasi' => 'required|in:pending,verified,rejected',
            'keterangan' => 'nullable|string'
        ]);

        // Upload bukti pembayaran
        if ($request->hasFile('bukti_pembayaran')) {
            $bukti = $request->file('bukti_pembayaran');
            $path = $bukti->store('public/pembayaran');
            $bukti_path = Storage::url($path);
        }

        $pembayaran = Pembayaran::create([
            'id_penyewa' => $request->id_penyewa,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'jumlah_pembayaran' => $request->jumlah_pembayaran,
            'bukti_pembayaran' => $bukti_path,
            'status_verifikasi' => $request->status_verifikasi,
            'keterangan' => $request->keterangan
        ]);

        return response()->json($pembayaran, 201);
    }

    // Mengupdate data pembayaran
    public function update(Request $request, $id_pembayaran)
    {
        $pembayaran = Pembayaran::findOrFail($id_pembayaran);

        $request->validate([
            'id_penyewa' => 'sometimes|required|exists:penyewa,id_penyewa',
            'tanggal_pembayaran' => 'sometimes|required|date',
            'jumlah_pembayaran' => 'sometimes|required|numeric',
            'bukti_pembayaran' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
            'status_verifikasi' => 'sometimes|required|in:pending,verified,rejected',
            'keterangan' => 'nullable|string'
        ]);

        // Update bukti pembayaran jika ada
        if ($request->hasFile('bukti_pembayaran')) {
            // Hapus bukti lama
            Storage::delete(str_replace('/storage', 'public', $pembayaran->bukti_pembayaran));
            
            // Upload bukti baru
            $bukti = $request->file('bukti_pembayaran');
            $path = $bukti->store('public/pembayaran');
            $bukti_path = Storage::url($path);
            $request->merge(['bukti_pembayaran' => $bukti_path]);
        }

        $pembayaran->update($request->all());

        return response()->json($pembayaran, 200);
    }

    // Menghapus data pembayaran
    public function delete($id_pembayaran)
    {
        $pembayaran = Pembayaran::findOrFail($id_pembayaran);
        
        // Hapus bukti pembayaran dari storage
        Storage::delete(str_replace('/storage', 'public', $pembayaran->bukti_pembayaran));
        
        $pembayaran->delete();

        return response()->json(null, 204);
    }

    // Verifikasi pembayaran
 

    public function verifikasi(Request $request, $id_pembayaran)
{
    try {
        $request->validate([
            'status_verifikasi' => 'required|in:verified,rejected',
            'keterangan' => 'required|string'
        ]);

        $pembayaran = Pembayaran::findOrFail($id_pembayaran);
        $pembayaran->update([
            'status_verifikasi' => $request->status_verifikasi,
            'keterangan' => $request->keterangan
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil diverifikasi',
            'data' => $pembayaran
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal memverifikasi pembayaran',
            'error' => $e->getMessage()
        ], 400);
    }
}
}
