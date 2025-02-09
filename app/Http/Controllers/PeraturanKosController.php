<?php

namespace App\Http\Controllers;

use App\Models\Peraturan_Kos;
use Illuminate\Http\Request;

class PeraturanKosController extends Controller
{
    public function getAll()
    {
        $peraturan = Peraturan_Kos::all();
        return response()->json($peraturan);
    }

    public function create(Request $request)
    {
        $request->validate([
            'isi_peraturan' => 'required|string',
            'tanggal_dibuat' => 'required|date'
        ]);

        $peraturan = Peraturan_Kos::create($request->all());
        return response()->json($peraturan, 201);
    }

    public function update(Request $request, $id)
    {
        $peraturan = Peraturan_Kos::findOrFail($id);

        $request->validate([
            'isi_peraturan' => 'required|string',
            'tanggal_dibuat' => 'required|date'
        ]);

        $peraturan->update($request->all());
        return response()->json($peraturan);
    }

    public function delete($id)
    {
        $peraturan = Peraturan_Kos::findOrFail($id);
        $peraturan->delete();
        return response()->json(null, 204);
    }
}
