<?php

namespace App\Http\Controllers;

use App\Models\Penyewa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PenyewaController extends Controller
{
    public function getAll()
    {
        try {
            $penyewa = Penyewa::all();
            return response()->json($penyewa, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mendapatkan data penyewa.'], 500);
        }
    }

    // Mendapatkan detail penyewa berdasarkan ID
    public function getById($id_penyewa)
    {
        try {
            $penyewa = Penyewa::with(['user']) // Perbaiki relasi untuk menggunakan nama yang benar
                ->where('id_penyewa', $id_penyewa)
                ->first();

            if (!$penyewa) {
                return response()->json(['error' => 'Penyewa tidak ditemukan.'], 404);
            }

            return response()->json($penyewa, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mendapatkan data penyewa. ' . $e->getMessage()], 500);
        }
    }

    // Membuat data penyewa baru
    public function create(Request $request)
    {
        $rules = [
            'id_user' => 'nullable|exists:users,id_user',
            'id_kamar' => 'nullable|exists:kamar,id_kamar',
            'nik' => 'required|string|size:16',
            'alamat_asal' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'durasi_sewa' => 'required|integer',
            'nomor_wa' => 'required|string',
            'tanggal_keluar' => 'required|date|after:tanggal_masuk',
            'status_penyewa' => 'required|in:aktif,tidak_aktif',
        ];
    
        if ($request->hasFile('foto_ktp')) {
            $rules['foto_ktp'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
        }
    
        $request->validate($rules);
    
        try {
            $validatedData = $request->except('foto_ktp');
    
            if ($request->hasFile('foto_ktp')) {
                $validatedData['foto_ktp'] = $this->uploadFile($request->file('foto_ktp'), 'ktp', 'penyewa/'); // Tambahkan path 'penyewa/'
            }
    
            $penyewa = Penyewa::create($validatedData);
    
            \Log::info('Data penyewa berhasil disimpan: ' . json_encode($penyewa));
            return response()->json($penyewa, 201);
        } catch (\Exception $e) {
            \Log::error('Gagal membuat data penyewa: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat data penyewa: ' . $e->getMessage()], 500);
        }
    }
    
    private function uploadFile($file, $type, $path)
    {
        $file_name = time() . $type . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('storage/images/' . $path), $file_name);
        return 'storage/images/' . $path . $file_name; 
    }
    // Mengupdate data penyewa
    public function update(Request $request, $id_penyewa)
    {
        try {
            $penyewa = Penyewa::findOrFail($id_penyewa);

            $request->validate([
                'id_user' => 'sometimes|required|exists:users,id_user',
                'id_kamar' => 'sometimes|required|exists:kamar,id_kamar',
                'nik' => 'sometimes|required|string|size:16',
                'foto_ktp' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
                'alamat_asal' => 'sometimes|required|string',
                'tanggal_masuk' => 'sometimes|required|date',
                'durasi_sewa' => 'sometimes|required|integer',
                'nomor_wa' => 'sometimes|required|integer',
                'tanggal_keluar' => 'sometimes|required|date|after:tanggal_masuk',
                'status_penyewa' => 'sometimes|required|in:aktif,tidak_aktif',
            ]);

            // Update foto KTP jika ada
            if ($request->hasFile('foto_ktp')) {
                // Hapus foto lama
                Storage::delete(str_replace('/storage', 'public', $penyewa->foto_ktp));
                
                // Upload foto baru
                $foto_ktp = $request->file('foto_ktp');
                $path = $foto_ktp->store('public/ktp');
                $foto_ktp_path = Storage::url($path);
                $request->merge(['foto_ktp' => $foto_ktp_path]);
            }

            $penyewa->update($request->all());

            return response()->json($penyewa, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengupdate data penyewa.'], 500);
        }
    }

    // Menghapus data penyewa
    public function delete($id_penyewa)
    {
        try {
            $penyewa = Penyewa::findOrFail($id_penyewa);
            
            // Hapus foto KTP dari storage
            Storage::delete(str_replace('/storage', 'public', $penyewa->foto_ktp));
            
            $penyewa->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus data penyewa.'], 500);
        }
    }
}
