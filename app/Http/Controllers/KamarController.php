<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Unit_Kamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class KamarController extends Controller
{
    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validasi request
            $validated = $request->validate([
                'tipe_kamar' => 'required|string',
                'harga_sewa' => 'required|numeric',
                'harga_sewa1' => 'nullable|numeric',
                'harga_sewa2' => 'nullable|numeric',
                'harga_sewa3' => 'nullable|numeric',
                'harga_sewa4' => 'nullable|numeric',
                'jumlah_unit' => 'required|integer|min:1',
            ]);

            // Buat kamar baru
            $kamar = Kamar::create([
                'tipe_kamar' => $validated['tipe_kamar'],
                'harga_sewa' => $validated['harga_sewa'],
                'harga_sewa1' => $validated['harga_sewa1'] ?? null,
                'harga_sewa2' => $validated['harga_sewa2'] ?? null,
                'harga_sewa3' => $validated['harga_sewa3'] ?? null,
                'harga_sewa4' => $validated['harga_sewa4'] ?? null,
            ]);

            // Buat unit kamar sesuai jumlah yang diminta
            for ($i = 1; $i <= $validated['jumlah_unit']; $i++) {
                Unit_Kamar::create([
                    'id_kamar' => $kamar->id_kamar,
                    'nomor_kamar' => $validated['tipe_kamar'] . '-' . str_pad($i, 3, '0', STR_PAD_LEFT), // Format: TIPE-001
                    'status' => 'tersedia'
                ]);
            }

            DB::commit();

            // Load relasi unit_kamar setelah commit
            $kamar->load('unit_kamar');

            return response()->json([
                'message' => 'Data kamar berhasil dibuat',
                'data' => $kamar
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat data kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getAll()
    {
        try {
            $kamar = Kamar::with('unit_kamar')->get();
            return response()->json([
                'message' => 'Data kamar berhasil diambil',
                'data' => $kamar
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
    
            $kamar = Kamar::with('unit_kamar')->findOrFail($id);
            
            // Validasi request
            $validated = $request->validate([
                'tipe_kamar' => 'required|string',
                'harga_sewa' => 'required|numeric',
                'harga_sewa1' => 'nullable|numeric',
                'harga_sewa2' => 'nullable|numeric',
                'harga_sewa3' => 'nullable|numeric',
                'harga_sewa4' => 'nullable|numeric',
                'jumlah_unit' => 'sometimes|required|integer|min:1',
            ]);
    
            // Update kamar
            $kamar->update([
                'tipe_kamar' => $validated['tipe_kamar'],
                'harga_sewa' => $validated['harga_sewa'],
                'harga_sewa1' => $validated['harga_sewa1'] ?? null,
                'harga_sewa2' => $validated['harga_sewa2'] ?? null,
                'harga_sewa3' => $validated['harga_sewa3'] ?? null,
                'harga_sewa4' => $validated['harga_sewa4'] ?? null,
            ]);
    
            // Update nomor kamar untuk semua unit yang ada
            $existingUnits = $kamar->unit_kamar()->orderBy('id_unit')->get();
            foreach ($existingUnits as $index => $unit) {
                $unit->update([
                    'nomor_kamar' => $validated['tipe_kamar'] . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)
                ]);
            }
    
            // Update jumlah unit jika ada perubahan
            if (isset($validated['jumlah_unit'])) {
                $current_units = $kamar->unit_kamar()->count();
                $new_units = $validated['jumlah_unit'];
    
                // Jika perlu menambah unit baru
                if ($new_units > $current_units) {
                    for ($i = $current_units + 1; $i <= $new_units; $i++) {
                        Unit_Kamar::create([
                            'id_kamar' => $kamar->id_kamar,
                            'nomor_kamar' => $validated['tipe_kamar'] . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                            'status' => 'tersedia'
                        ]);
                    }
                }
                // Jika perlu mengurangi unit (hanya unit yang tersedia yang bisa dihapus)
                elseif ($new_units < $current_units) {
                    $units_to_delete = $kamar->unit_kamar()
                        ->where('status', 'tersedia')
                        ->latest('id_unit')
                        ->take($current_units - $new_units)
                        ->get();
    
                    if ($units_to_delete->count() < ($current_units - $new_units)) {
                        throw new Exception('Tidak dapat mengurangi jumlah unit karena beberapa unit sedang dihuni');
                    }
    
                    foreach ($units_to_delete as $unit) {
                        $unit->delete();
                    }
    
                    // Reorder nomor kamar untuk unit yang tersisa
                    $remaining_units = $kamar->unit_kamar()->orderBy('id_unit')->get();
                    foreach ($remaining_units as $index => $unit) {
                        $unit->update([
                            'nomor_kamar' => $validated['tipe_kamar'] . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)
                        ]);
                    }
                }
            }
    
            DB::commit();
    
            // Reload model dengan unit yang telah diupdate
            $kamar->load('unit_kamar');
    
            return response()->json([
                'message' => 'Data kamar berhasil diupdate',
                'data' => $kamar
            ], 200);
    
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengupdate data kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function delete($id)
    {
        try {
            $kamar = Kamar::findOrFail($id);
            $kamar->delete();

            return response()->json([
                'message' => 'Data kamar berhasil dihapus'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data kamar',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}