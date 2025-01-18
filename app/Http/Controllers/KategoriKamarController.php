<?php

namespace App\Http\Controllers;

use App\Models\Kategori_Kamar;
use App\Models\Unit_Kamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class KategoriKamarController extends Controller
{
    public function createUnits($id_kategori)
    {
        try {
            DB::beginTransaction();
            
            $kategori = KategoriKamar::findOrFail($id_kategori);
            $existing_units = UnitKamar::where('id_kategori', $id_kategori)->count();
            
            // Buat unit kamar sesuai jumlah yang dibutuhkan
            for ($i = 1; $i <= $kategori->jumlah_unit - $existing_units; $i++) {
                UnitKamar::create([
                    'id_kategori' => $id_kategori,
                    'nomor_kamar' => 'K' . $id_kategori . '-' . ($existing_units + $i),
                    'status' => 'tersedia'
                ]);
            }
            
            DB::commit();
            return response()->json(['message' => 'Unit kamar berhasil dibuat'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getAvailableUnits($id_kategori)
    {
        try {
            $units = UnitKamar::where('id_kategori', $id_kategori)
                             ->where('status', 'tersedia')
                             ->get();
            
            return response()->json(['data' => $units], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
