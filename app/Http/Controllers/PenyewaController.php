<?php

namespace App\Http\Controllers;

use App\Models\Penyewa;
use App\Models\Unit_Kamar;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Hash;

class PenyewaController extends Controller
{
    public function getAll()
    {
        try {
            $penyewa = Penyewa::with(['user', 'unit_kamar.kamar'])->get();
            return response()->json([
                'message' => 'Berhasil mendapatkan data penyewa',
                'data' => $penyewa
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data penyewa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getById($id_penyewa)
    {
        try {
            $penyewa = Penyewa::with(['user', 'unit_kamar.kamar'])
                ->where('id_penyewa', $id_penyewa)
                ->firstOrFail();

            return response()->json([
                'message' => 'Berhasil mendapatkan data penyewa',
                'data' => $penyewa
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data penyewa',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getKategoriKamar()
    {
        try {
            $kategori = Kamar::select('id_kamar', 'tipe_kamar', 'harga_sewa')
                            ->with(['unit_kamar' => function($query) {
                                $query->where('status', 'tersedia');
                            }])
                            ->get()
                            ->map(function($kamar) {
                                return [
                                    'id_kamar' => $kamar->id_kamar,
                                    'tipe_kamar' => $kamar->tipe_kamar,
                                    'harga_sewa' => $kamar->harga_sewa,
                                    'unit_tersedia' => $kamar->unit_kamar->count()
                                ];
                            });

            return response()->json([
                'message' => 'Berhasil mendapatkan data kategori kamar',
                'data' => $kategori
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data kategori kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getUnitTersedia($id_kamar)
    {
        try {
            $units = Unit_Kamar::where('id_kamar', $id_kamar)
                              ->where('status', 'tersedia')
                              ->with('kamar')
                              ->get();
    
            if ($units->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada unit tersedia untuk kategori kamar ini',
                    'data' => []
                ], 200);
            }
    
            return response()->json([
                'message' => 'Berhasil mendapatkan data unit tersedia',
                'data' => $units
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data unit tersedia',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();
    
            $validated = $request->validate([
                'id_user' => 'required|exists:users,id_user',
                'id_kamar' => 'required|exists:kamar,id_kamar',
                'id_unit' => 'required|exists:unit_kamar,id_unit',
                'nik' => 'required|string|size:16',
                'foto_ktp' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'alamat_asal' => 'required|string',
                'tanggal_masuk' => 'required|date',
                'durasi_sewa' => 'required|integer',
                'nomor_wa' => 'required|string',
                'tanggal_keluar' => 'required|date|after:tanggal_masuk',
            ]);
    
            // Cek ketersediaan unit dan validasi unit sesuai dengan kategori kamar
            $unit = Unit_Kamar::where('id_unit', $validated['id_unit'])
                             ->where('id_kamar', $validated['id_kamar'])
                             ->where('status', 'tersedia')
                             ->first();
    
            if (!$unit) {
                throw new Exception('Unit kamar tidak tersedia atau tidak sesuai dengan kategori yang dipilih');
            }
    
            // Upload foto KTP
            if ($request->hasFile('foto_ktp')) {
                $filePath = $request->file('foto_ktp')->store('ktp', 'public');
                $validated['foto_ktp'] = 'storage/' . $filePath;
            }
    
            // Buat data penyewa
            $penyewa = Penyewa::create([
                'id_user' => $validated['id_user'],
                'id_unit' => $validated['id_unit'],
                'nik' => $validated['nik'],
                'foto_ktp' => $validated['foto_ktp'],
                'alamat_asal' => $validated['alamat_asal'],
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'durasi_sewa' => $validated['durasi_sewa'],
                'nomor_wa' => $validated['nomor_wa'],
                'tanggal_keluar' => $validated['tanggal_keluar'],
                'status_penyewa' => 'aktif'
            ]);
    
            // Update status unit menjadi dihuni
            $unit->update(['status' => 'dihuni']);
    
            DB::commit();
    
            // Load relasi untuk response
            $penyewa->load('unit_kamar.kamar');
    
            return response()->json([
                'message' => 'Data penyewa berhasil dibuat',
                'data' => $penyewa
            ], 201);
    
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat data penyewa',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id_penyewa)
    {
        try {
            DB::beginTransaction();

            // Cari data penyewa
            $penyewa = Penyewa::with('user')->findOrFail($id_penyewa);

            // Validasi request
            $rules = [
                'name' => 'sometimes|string|max:255',
                'alamat_asal' => 'sometimes|string',
                'tanggal_masuk' => 'sometimes|date',
                'tanggal_keluar' => 'sometimes|date|after:tanggal_masuk',
                'nomor_wa' => 'sometimes|string',
                'email' => 'sometimes|email',
                'password' => 'sometimes|nullable|string|min:8',
                'foto_ktp' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ];

            $validated = $request->validate($rules);

            // Update data user jika ada perubahan nama, email, atau password
            if ($request->has('name') || $request->has('email') || $request->has('password')) {
                $userData = [];
                
                if ($request->has('name')) {
                    $userData['name'] = $validated['name'];
                }
                
                if ($request->has('email')) {
                    $userData['email'] = $validated['email'];
                }
                
                if ($request->has('password') && !empty($validated['password'])) {
                    $userData['password'] = Hash::make($validated['password']);
                }

                // Update user data
                $penyewa->user()->update($userData);
            }

            // Data yang akan diupdate untuk penyewa
            $penyewaData = collect($validated)->only([
                'alamat_asal',
                'tanggal_masuk',
                'tanggal_keluar',
                'nomor_wa',
            ])->toArray();

            // Handle foto KTP
            if ($request->hasFile('foto_ktp')) {
                // Hapus foto lama
                if ($penyewa->foto_ktp) {
                    $oldPath = str_replace('storage/', 'public/', $penyewa->foto_ktp);
                    if (Storage::exists($oldPath)) {
                        Storage::delete($oldPath);
                    }
                }
                
                // Upload foto baru
                $filePath = $request->file('foto_ktp')->store('ktp', 'public');
                $penyewaData['foto_ktp'] = 'storage/' . $filePath;
            }

            // Update data penyewa
            $penyewa->update($penyewaData);

            DB::commit();

            // Load relasi untuk response
            $penyewa->load('user', 'unit_kamar.kamar');

            return response()->json([
                'message' => 'Data penyewa berhasil diupdate',
                'data' => $penyewa
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            
            // Log error untuk debugging
            \Log::error('Error updating penyewa: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'message' => 'Gagal mengupdate data penyewa',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function delete($id_penyewa)
    {
        try {
            DB::beginTransaction();

            $penyewa = Penyewa::findOrFail($id_penyewa);

            // Set unit menjadi tersedia
            Unit_Kamar::where('id_unit', $penyewa->id_unit)
                     ->update(['status' => 'tersedia']);

            // Hapus foto KTP jika ada
            if ($penyewa->foto_ktp) {
                Storage::delete(str_replace('storage/', 'public/', $penyewa->foto_ktp));
            }

            $penyewa->delete();

            DB::commit();

            return response()->json([
                'message' => 'Data penyewa berhasil dihapus'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus data penyewa',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getAllUnits()
    {
        try {
            $units = Unit_Kamar::with(['kamar', 'penyewa.user'])
                ->orderBy('nomor_kamar')
                ->get()
                ->map(function ($unit) {
                    return [
                        'id_unit' => $unit->id_unit,
                        'nomor_kamar' => $unit->nomor_kamar,
                        'status' => $unit->status,
                        'kamar' => $unit->kamar,
                        'penyewa' => $unit->penyewa
                    ];
                });

            return response()->json([
                'message' => 'Berhasil mendapatkan data unit kamar',
                'data' => $units
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data unit kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
