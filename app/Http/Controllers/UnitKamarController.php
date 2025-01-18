<?php

namespace App\Http\Controllers;

use App\Models\Unit_Kamar;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Exception;

class UnitKamarController extends Controller
{
    public function getAvailableUnits($id_kamar)
    {
        try {
            $units = Unit_Kamar::with('kamar')
                ->where('id_kamar', $id_kamar)
                ->where('status', 'tersedia')
                ->get();

            return response()->json([
                'message' => 'Berhasil mengambil data unit tersedia',
                'data' => $units
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data unit',
                'error' => $e->getMessage()
            ], 400);
        }
    }

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
}