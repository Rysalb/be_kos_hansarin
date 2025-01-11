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

    public function getById($id_penyewa)
    {
        try {
            $penyewa = Penyewa::with(['user'])
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

    public function create(Request $request)
    {
        $rules = [
            'id_user' => 'nullable|exists:users,id_user',
            'id_kamar' => 'nullable|exists:kamar,id_kamar',
            'nik' => 'required|string',
            'alamat_asal' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'durasi_sewa' => 'required|integer',
            'nomor_wa' => 'required|string',
            'tanggal_keluar' => 'required|date|after:tanggal_masuk',
            'status_penyewa' => 'required|in:aktif,tidak_aktif',
            'foto_ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        $request->validate($rules);

        try {
            $validatedData = $request->all();

            if ($request->hasFile('foto_ktp')) {
                $filePath = $request->file('foto_ktp')->store('ktp', 'public');
                $validatedData['foto_ktp'] = 'storage/' . $filePath;
            }

            $penyewa = Penyewa::create($validatedData);

            \Log::info('Data penyewa berhasil disimpan: ' . json_encode($penyewa));
            return response()->json($penyewa, 201);
        } catch (\Exception $e) {
            \Log::error('Gagal membuat data penyewa: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat data penyewa: ' . $e->getMessage()], 500);
        }
    }

    
    private function uploadFile($file, $path)
{
    $file_name = time() . '_' . $file->getClientOriginalName();
    $file->storeAs('public/' . $path, $file_name);
    return 'storage/' . $path . '/' . $file_name;
}

    // Mengupdate data penyewa
    public function update(Request $request, $id_penyewa)
    {
        try {
            $penyewa = Penyewa::findOrFail($id_penyewa);

            $request->validate([
                'id_user' => 'sometimes|exists:users,id_user',
                'id_kamar' => 'sometimes|exists:kamar,id_kamar',
                'nik' => 'sometimes|string',
                'foto_ktp' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'alamat_asal' => 'sometimes|string',
                'tanggal_masuk' => 'sometimes|date',
                'durasi_sewa' => 'sometimes|integer',
                'nomor_wa' => 'sometimes|string',
                'tanggal_keluar' => 'sometimes|date|after:tanggal_masuk',
                'status_penyewa' => 'sometimes|in:aktif,tidak_aktif',
            ]);

            $validatedData = $request->all();
            if ($request->hasFile('foto_ktp')) {
  
                if ($penyewa->foto_ktp) {
                    Storage::delete(str_replace('storage', 'public', $penyewa->foto_ktp));
                }

                $filePath = $request->file('foto_ktp')->store('ktp', 'public');
                $validatedData['foto_ktp'] = 'storage/' . $filePath;
            }

            $penyewa->update($validatedData);

            return response()->json($penyewa, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengupdate data penyewa: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id_penyewa)
    {
        try {
            $penyewa = Penyewa::findOrFail($id_penyewa);
            
            Storage::delete(str_replace('/storage', 'public', $penyewa->foto_ktp));
            
            $penyewa->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus data penyewa.'], 500);
        }
    }
}
