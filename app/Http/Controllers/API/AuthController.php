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
use App\Models\Notification;


class AuthController extends Controller
{
    public function register(Request $request)
{
    // Cek email terlebih dahulu sebelum validasi lainnya
    $emailExists = User::where('email', $request->email)->exists();
    if ($emailExists) {
        return response()->json([
            'status' => false,
            'message' => 'Email sudah terdaftar. Silakan gunakan email lain.',
            'type' => 'email_exists'
        ], 422);
    }

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

        // Setelah penyewa berhasil didaftarkan (sebelum DB::commit())
        // Kirim notifikasi ke semua admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $notificationController = app()->make(NotificationsController::class);
            $notificationController->sendNotification(
                $admin->id_user,
                'Pendaftaran Penyewa Baru',
                "Penyewa baru {$request->name} memerlukan verifikasi untuk kamar {$unit->nomor_kamar}",
                'tenant_registration',
                ['id_user' => $user->id_user]
            );
        }

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
        // Different validation rules based on status
        $rules = [
            'status' => 'required|in:disetujui,ditolak',
        ];

        // Only add these validations if status is 'disetujui'
        if ($request->status === 'disetujui') {
            $rules['tanggal_masuk'] = 'required|date';
            $rules['durasi_sewa'] = 'required|integer';
            $rules['harga_sewa'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

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
            // Update data penyewa only if approved
            $penyewa->update([
                'tanggal_masuk' => $request->tanggal_masuk,
                'durasi_sewa' => $request->durasi_sewa,
                'tanggal_keluar' => Carbon::parse($request->tanggal_masuk)
                    ->addMonths($request->durasi_sewa),
                'status_penyewa' => 'aktif',
                'harga_sewa' => $request->harga_sewa
            ]);

            // Update unit status
            Unit_Kamar::where('id_unit', $penyewa->id_unit)
                ->update(['status' => 'dihuni']);

            // After successful verification
            $notificationController = app()->make(NotificationsController::class);
            $notificationController->sendNotification(
                $userId,
                'Verifikasi Akun',
                "Akun Anda telah diverifikasi. Selamat datang di Kos SidoRame12!",
                'tenant_verification',
                ['id_user' => $userId]
            );
        } else {
            // If rejected, delete penyewa data
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
        'nomor_wa' => 'required|string',
        'password_confirmation' => 'required|same:password',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();
    try {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'nomor_wa' => $request->nomor_wa
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Admin berhasil ditambahkan',
            'data' => $user
        ], 201);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Gagal menambahkan admin',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email dan password harus diisi'
                ], 422);
            }
    
            // Check if user exists first
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email yang Anda masukkan tidak terdaftar'
                ], 401);
            }
    
            // Check password before verification status
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password yang Anda masukkan salah'
                ], 401);
            }
    
            // Check verification status for regular users
            if ($user->role === 'user') {
                if ($user->status_verifikasi === 'pending') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Akun Anda masih dalam proses verifikasi admin'
                    ], 403);
                } else if ($user->status_verifikasi === 'ditolak') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Akun Anda telah ditolak oleh admin'
                    ], 403);
                }
            }
    
            // If all checks pass, create token and login
            $token = $user->createToken('auth-token')->plainTextToken;
    
            // Get penyewa data for regular users
            $penyewa = null;
            if ($user->role === 'user') {
                $penyewa = Penyewa::where('id_user', $user->id_user)->first();
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user,
                'penyewa' => $penyewa
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat login'
            ], 500);
        }
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

public function getProfile(Request $request)
{
    try {
        $user = $request->user();
        if (!$user) {
            throw new Exception('User tidak ditemukan');
        }
        
        // Jika user adalah admin
        if ($user->role === 'admin') {
            return response()->json([
                'status' => true,
                'data' => [
                    'id_user' => $user->id_user,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nomor_wa'=> $user->nomor_wa,
                    'role' => $user->role,
                ]
            ]);
        }
        
        // Jika user adalah penyewa
        $penyewa = $user->penyewa()
            ->with(['unit_kamar.kamar'])
            ->with(['pembayaran' => function($query) {
                $query->select('id_pembayaran', 'id_penyewa', 'tanggal_pembayaran', 'status_verifikasi')
                      ->where('status_verifikasi', 'verified')
                      ->orderBy('tanggal_pembayaran', 'desc')
                      ->limit(1);
            }])
            ->first();

        if (!$penyewa) {
            return response()->json([
                'status' => true,
                'data' => [
                    'id_user' => $user->id_user,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status_verifikasi' => $user->status_verifikasi
                ]
            ]);
        }
        
        // Get latest payment date
        $latestPayment = $penyewa->pembayaran->first();
        
        return response()->json([
            'status' => true,
            'data' => [
                'id_user' => $user->id_user,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status_verifikasi' => $user->status_verifikasi,
                'tanggal_masuk' => $penyewa->tanggal_masuk,
                'tanggal_keluar' => $penyewa->tanggal_keluar,
                'status_penyewa' => $penyewa->status_penyewa,
                'nomor_kamar' => $penyewa->unit_kamar->nomor_kamar ?? null,
                'tipe_kamar' => $penyewa->unit_kamar->kamar->tipe_kamar ?? null,
                'nomor_wa' => $penyewa->nomor_wa,
                'pembayaran_terakhir' => $latestPayment ? $latestPayment->tanggal_pembayaran : null
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal memuat profil',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getAdminList()
{
    try {
        // Mengambil semua admin kecuali yang dibuat melalui seeder
        $admins = User::where('role', 'admin')
            ->where('email', '!=', 'admin@gmail.com') // Asumsikan ini email admin seeder
            ->select('id_user', 'name', 'email', 'nomor_wa', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil daftar admin',
            'data' => $admins
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal mengambil daftar admin',
            'error' => $e->getMessage()
        ], 500);
    }
}
}