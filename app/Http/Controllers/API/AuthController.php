<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Penyewa;
use App\Models\Unit_Kamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; 
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;  
use App\Models\Pemasukan_Pengeluaran;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'id_unit' => 'required|exists:unit_kamar,id_unit',
        'nik' => 'required|string|size:16',
        'foto_ktp' => 'required|image|mimes:jpeg,png,jpg',
        'alamat_asal' => 'required|string',
        'nomor_wa' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    // Cek apakah unit sudah dihuni
    $unit = Unit_Kamar::find($request->id_unit);
    if ($unit->status === 'dihuni') {
        return response()->json([
            'status' => false,
            'message' => 'Unit kamar sudah dihuni, silakan pilih unit lain.'
        ], 400);
    }

    DB::beginTransaction();
    try {
        // Upload foto KTP
        $fotoKtpPath = $request->file('foto_ktp')->store('ktp', 'public');

        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status_verifikasi' => 'pending'
        ]);

        // Refresh model untuk memastikan ID tersedia
        $user->refresh();

        // Debug untuk memeriksa ID user
        \Log::info('User ID: ' . $user->id_user);

        // Buat data penyewa (pending)
        $penyewa = new Penyewa();
        $penyewa->id_user = $user->id_user;
        $penyewa->id_unit = $request->id_unit;
        $penyewa->nik = $request->nik;
        $penyewa->foto_ktp = 'storage/' . $fotoKtpPath;
        $penyewa->alamat_asal = $request->alamat_asal;
        $penyewa->nomor_wa = $request->nomor_wa;
        $penyewa->tanggal_masuk = now();
        $penyewa->durasi_sewa = 1;
        $penyewa->tanggal_keluar = now()->addMonth();
        $penyewa->status_penyewa = 'tidak_aktif';
        $penyewa->save();

        DB::commit();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Pendaftaran berhasil dan menunggu verifikasi admin',
            'token' => $token,
            'user' => $user,
            'penyewa' => $penyewa
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();
        // Tambahkan log untuk debugging
        \Log::error('Registration Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'status' => false,
            'message' => 'Pendaftaran gagal',
            'error' => $e->getMessage()
        ], 500);
    }
}

// Tambahkan method baru untuk verifikasi user
public function verifikasiUser(Request $request, $userId)
{
    try {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:disetujui,ditolak',
            'tanggal_masuk' => 'required_if:status,disetujui|date',
            'durasi_sewa' => 'required_if:status,disetujui|integer',
            'harga_sewa' => 'required_if:status,disetujui|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $user = User::findOrFail($userId);
        $penyewa = Penyewa::where('id_user', $userId)->firstOrFail();

        $user->status_verifikasi = $request->status;
        $user->save();

        if ($request->status === 'disetujui') {
            // Update data penyewa
            $penyewa->update([
                'tanggal_masuk' => $request->tanggal_masuk,
                'durasi_sewa' => $request->durasi_sewa,
                'tanggal_keluar' => Carbon::parse($request->tanggal_masuk)
                    ->addMonths($request->durasi_sewa),
                'status_penyewa' => 'aktif',
                'harga_sewa' => $request->harga_sewa
            ]);

            // Update status unit kamar
            Unit_Kamar::where('id_unit', $penyewa->id_unit)
                     ->update(['status' => 'dihuni']);

            // Catat pembayaran di pemasukan_pengeluaran
            $pemasukanPengeluaran = new Pemasukan_Pengeluaran([
                'jenis_transaksi' => 'pemasukan',
                'kategori' => 'Pembayaran Sewa',
                'tanggal' => $request->tanggal_masuk,
                'jumlah' => $request->harga_sewa,
                'keterangan' => "Pembayaran sewa kamar {$penyewa->unit_kamar->nomor_kamar} - {$user->name}",
                'bulan' => Carbon::parse($request->tanggal_masuk)->month,
                'tahun' => Carbon::parse($request->tanggal_masuk)->year
            ]);

            // Hitung saldo
            $lastTransaction = Pemasukan_Pengeluaran::latest('id_transaksi')->first();
            $currentSaldo = $lastTransaction ? $lastTransaction->saldo : 0;
            $pemasukanPengeluaran->saldo = $currentSaldo + $request->harga_sewa;
            
            $pemasukanPengeluaran->save();
        } else {
            // Jika ditolak, hapus data penyewa
            Storage::delete(str_replace('storage/', 'public/', $penyewa->foto_ktp));
            $penyewa->delete();
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Verifikasi user berhasil',
            'status_verifikasi' => $request->status
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Verifikasi gagal',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function registerAdmin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'admin' // Set role sebagai admin
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'Admin registration successful',
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ]
    ], 201);
}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id_user,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials'
        ],401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => true,
            'user' => $request->user()
        ]);
    }

    public function deleteUser(Request $request, $userId)
    {
        // Pastikan user yang melakukan permintaan adalah admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
    
        DB::beginTransaction();
        try {
            // Temukan user berdasarkan ID
            $user = User::findOrFail($userId);
            
            // Hapus data penyewa terkait jika ada
            $penyewa = Penyewa::where('id_user', $userId)->first();
            if ($penyewa) {
                // Hapus foto KTP jika ada
                if ($penyewa->foto_ktp) {
                    Storage::delete(str_replace('storage/', 'public/', $penyewa->foto_ktp));
                }
                $penyewa->delete();
            }
    
            // Hapus user
            $user->delete();
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ], 200);
    
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
public function getUsersByRole(Request $request, $role)
{
    // Pastikan user yang melakukan permintaan adalah admin
    if ($request->user()->role !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    try {
        // Ambil semua pengguna dengan role yang diberikan
        $users = User::where('role', $role)
            ->with(['penyewa.unit_kamar.kamar']) // Tambahkan relasi dengan kamar
            ->get()
            ->map(function ($user) {
                return [
                    'id_user' => $user->id_user,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status_verifikasi' => $user->status_verifikasi,
                    'penyewa' => $user->penyewa ? [
                        'unit_kamar' => [
                            'nomor_kamar' => $user->penyewa->unit_kamar->nomor_kamar,
                            'kamar' => [
                                'harga_sewa' => $user->penyewa->unit_kamar->kamar->harga_sewa,
                                'harga_sewa1' => $user->penyewa->unit_kamar->kamar->harga_sewa1,
                                'harga_sewa2' => $user->penyewa->unit_kamar->kamar->harga_sewa2,
                                'harga_sewa3' => $user->penyewa->unit_kamar->kamar->harga_sewa3,
                                'harga_sewa4' => $user->penyewa->unit_kamar->kamar->harga_sewa4,
                            ]
                        ],
                        'nomor_wa' => $user->penyewa->nomor_wa
                    ] : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $users
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve users',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getUserById(Request $request, $userId)
{
    // Pastikan user yang melakukan permintaan adalah admin
    if ($request->user()->role !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    try {
        // Temukan user berdasarkan ID
        $user = User::findOrFail($userId);

        return response()->json([
            'status' => true,
            'data' => $user
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve user',
            'error' => $e->getMessage()
        ], 500);
    }
}
}