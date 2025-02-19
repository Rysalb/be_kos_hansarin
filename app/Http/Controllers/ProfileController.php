<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . auth()->id() . ',id_user',
                'nomor_wa' => 'required|string|max:15',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'nomor_wa' => $request->nomor_wa,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Profile berhasil diupdate',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate profile: ' . $e->getMessage()
            ], 500);
        }
    }
} 