<?php

namespace App\Http\Controllers;

use App\Models\Unit_Kamar;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Exception;

class UnitKamarController extends Controller
{
   

    public function updateStatus($id_unit, Request $request)
    {
        try {
            $unit = Unit_Kamar::findOrFail($id_unit);
            $unit->update(['status' => $request->status]);

            return response()->json([
                'message' => 'Status unit berhasil diupdate',
                'data' => $unit
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengupdate status unit',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getAvailableUnits()
    {
        try {
            $units = Unit_Kamar::with('kamar')
                ->where('status', 'tersedia')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mengambil data unit tersedia',
                'data' => $units
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDetail($id_unit)
    {
        try {
            $unit = Unit_Kamar::with('kamar')
                ->where('id_unit', $id_unit)
                ->firstOrFail();

            $kamar = $unit->kamar;
            
            // Format response dengan harga-harga yang tersedia
            $response = [
                'id_unit' => $unit->id_unit,
                'nomor_kamar' => $unit->nomor_kamar,
                'status' => $unit->status,
                'harga_bulanan' => $kamar->harga_sewa,  // 1 bulan
                'harga_2_bulan' => $kamar->harga_sewa1, // 2 bulan
                'harga_3_bulan' => $kamar->harga_sewa1, // 3 bulan
                'harga_6_bulan' => $kamar->harga_sewa2, // 6 bulan
                'harga_tahunan' => $kamar->harga_sewa3, // 12 bulan
            ];

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mengambil detail kamar',
                'data' => $response
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil detail kamar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}