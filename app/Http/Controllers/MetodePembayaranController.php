<?php

namespace App\Http\Controllers;

use App\Models\metode_pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        $metodePembayaran = metode_pembayaran::all();
        return response()->json([
            'status' => 'success',
            'data' => $metodePembayaran
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:bank,e-wallet',
            'nama' => 'required|string',
            'nomor_rekening' => 'required_if:kategori,bank',
            'qr_code' => 'required_if:kategori,e-wallet|image|mimes:jpeg,png,jpg|max:2048',
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/metode-pembayaran/logo');
            $data['logo'] = str_replace('public/', '', $logoPath);
        }

        // Handle QR code upload for e-wallet
        if ($request->hasFile('qr_code')) {
            $qrPath = $request->file('qr_code')->store('public/metode-pembayaran/qr');
            $data['qr_code'] = str_replace('public/', '', $qrPath);
        }

        $metodePembayaran = metode_pembayaran::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Metode pembayaran berhasil ditambahkan',
            'data' => $metodePembayaran
        ]);
    }

    public function destroy($id)
    {
        $metodePembayaran = metode_pembayaran::find($id);
        
        if (!$metodePembayaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Metode pembayaran tidak ditemukan'
            ], 404);
        }

        // Delete associated files
        if ($metodePembayaran->logo) {
            Storage::delete('public/' . $metodePembayaran->logo);
        }
        if ($metodePembayaran->qr_code) {
            Storage::delete('public/' . $metodePembayaran->qr_code);
        }

        $metodePembayaran->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Metode pembayaran berhasil dihapus'
        ]);
    }

    public function update(Request $request, $id)
    {
        $metodePembayaran = metode_pembayaran::find($id);
        
        if (!$metodePembayaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Metode pembayaran tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:bank,e-wallet',
            'nama' => 'required|string',
            'nomor_rekening' => 'required_if:kategori,bank',
            'qr_code' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle logo upload if new file is provided
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($metodePembayaran->logo) {
                Storage::delete('public/' . $metodePembayaran->logo);
            }
            $logoPath = $request->file('logo')->store('public/metode-pembayaran/logo');
            $data['logo'] = str_replace('public/', '', $logoPath);
        }

        // Handle QR code upload if new file is provided
        if ($request->hasFile('qr_code')) {
            // Delete old QR code
            if ($metodePembayaran->qr_code) {
                Storage::delete('public/' . $metodePembayaran->qr_code);
            }
            $qrPath = $request->file('qr_code')->store('public/metode-pembayaran/qr');
            $data['qr_code'] = str_replace('public/', '', $qrPath);
        }

        $metodePembayaran->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Metode pembayaran berhasil diperbarui',
            'data' => $metodePembayaran
        ]);
    }

    public function show($id)
    {
        $metodePembayaran = metode_pembayaran::find($id);
        
        if (!$metodePembayaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Metode pembayaran tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $metodePembayaran
        ]);
    }
}
