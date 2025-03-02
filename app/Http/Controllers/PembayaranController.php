<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\metode_pembayaran;
use App\Models\Penyewa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Pemasukan_Pengeluaran;
use App\Models\User;
use App\Models\Notification;

class PembayaranController extends Controller
{
    // Mendapatkan semua data pembayaran
    public function getAll()
    {
        try {
            $pembayaran = Pembayaran::with([
                'penyewa.user',
                'penyewa.unit_kamar',
                'metodePembayaran',
                'pesananMakanan.katalogMakanan'  // Add this relation
            ])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'data' => $pembayaran
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data pembayaran'
            ], 500);
        }
    }

    // Mendapatkan detail pembayaran berdasarkan ID
    public function getById($id_pembayaran)
    {
        $pembayaran = Pembayaran::with('penyewa')
            ->where('id_pembayaran', $id_pembayaran)
            ->firstOrFail();
        return response()->json($pembayaran, 200);
    }

    // Membuat data pembayaran baru
    public function create(Request $request)
    {
        $request->validate([
            'id_penyewa' => 'required|exists:penyewa,id_penyewa',
            'tanggal_pembayaran' => 'required|date',
            'jumlah_pembayaran' => 'required|numeric',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'status_verifikasi' => 'required|in:pending,verified,rejected',
            'keterangan' => 'nullable|string'
        ]);

        // Upload bukti pembayaran
        if ($request->hasFile('bukti_pembayaran')) {
            $bukti = $request->file('bukti_pembayaran');
            $path = $bukti->store('public/pembayaran');
            $bukti_path = Storage::url($path);
        }

        $pembayaran = Pembayaran::create([
            'id_penyewa' => $request->id_penyewa,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'jumlah_pembayaran' => $request->jumlah_pembayaran,
            'bukti_pembayaran' => $bukti_path,
            'status_verifikasi' => $request->status_verifikasi,
            'keterangan' => $request->keterangan
        ]);

        // Setelah pembayaran berhasil dibuat
        // Kirim notifikasi ke semua admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $notificationController = app()->make(NotificationsController::class);
            $notificationController->sendNotification(
                $admin->id_user,
                'Pembayaran Baru',
                "Pembayaran baru dari {$pembayaran->penyewa->user->name} perlu diverifikasi",
                'payment_verification',
                ['id_pembayaran' => $pembayaran->id_pembayaran]
            );
        }

        return response()->json($pembayaran, 201);
    }

    // Mengupdate data pembayaran
    public function update(Request $request, $id_pembayaran)
    {
        $pembayaran = Pembayaran::findOrFail($id_pembayaran);

        $request->validate([
            'id_penyewa' => 'sometimes|required|exists:penyewa,id_penyewa',
            'tanggal_pembayaran' => 'sometimes|required|date',
            'jumlah_pembayaran' => 'sometimes|required|numeric',
            'bukti_pembayaran' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
            'status_verifikasi' => 'sometimes|required|in:pending,verified,rejected',
            'keterangan' => 'nullable|string'
        ]);

        // Update bukti pembayaran jika ada
        if ($request->hasFile('bukti_pembayaran')) {
            // Hapus bukti lama
            Storage::delete(str_replace('/storage', 'public', $pembayaran->bukti_pembayaran));
            
            // Upload bukti baru
            $bukti = $request->file('bukti_pembayaran');
            $path = $bukti->store('public/pembayaran');
            $bukti_path = Storage::url($path);
            $request->merge(['bukti_pembayaran' => $bukti_path]);
        }

        $pembayaran->update($request->all());

        return response()->json($pembayaran, 200);
    }

    // Menghapus data pembayaran
    public function delete($id_pembayaran)
    {
        $pembayaran = Pembayaran::findOrFail($id_pembayaran);
        
        // Hapus bukti pembayaran dari storage
        Storage::delete(str_replace('/storage', 'public', $pembayaran->bukti_pembayaran));
        
        $pembayaran->delete();

        return response()->json(null, 204);
    }

    // Verifikasi pembayaran
 

    public function verifikasi(Request $request, $id_pembayaran)
    {
        try {
            DB::beginTransaction();
            
            $pembayaran = Pembayaran::with([
                'penyewa.unit_kamar', 
                'metodePembayaran',
                'pesananMakanan.katalogMakanan' // Add relation to get order details
            ])->findOrFail($id_pembayaran);
            
            $pembayaran->status_verifikasi = $request->status_verifikasi;
            // Don't override the original keterangan
            // $pembayaran->keterangan = $request->keterangan;
            $pembayaran->save();
    
            if ($request->status_verifikasi === 'verified') {
                $lastTransaction = Pemasukan_Pengeluaran::latest('id_transaksi')->first();
                $lastSaldo = $lastTransaction ? $lastTransaction->saldo : 0;
                $newSaldo = $lastSaldo + $pembayaran->jumlah_pembayaran;
    
                // Use original keterangan for kategori
                $kategori = $pembayaran->keterangan == 'Order Menu' ? 'Order Menu' : 'Pembayaran Sewa';
                
                // Create transaction record with proper category
                Pemasukan_Pengeluaran::create([
                    'jenis_transaksi' => 'pemasukan',
                    'kategori' => $kategori,
                    'jumlah' => $pembayaran->jumlah_pembayaran,
                    'tanggal' => $pembayaran->tanggal_pembayaran,
                    'keterangan' => $pembayaran->keterangan == 'Order Menu' 
                        ? $this->generateOrderDetails($pembayaran->pesananMakanan)
                        : "Pembayaran sewa kamar {$pembayaran->penyewa->unit_kamar->nomor_kamar}",
                    'id_pembayaran' => $id_pembayaran,
                    'id_penyewa' => $pembayaran->id_penyewa,
                    'saldo' => $newSaldo
                ]);

                // After successful verification
                $notificationController = app()->make(NotificationsController::class);
                $notificationController->sendNotification(
                    $pembayaran->penyewa->user->id_user,
                    'Pembayaran Diverifikasi',
                    "Pembayaran untuk kamar {$pembayaran->penyewa->unit_kamar->nomor_kamar} telah diverifikasi",
                    'payment_verification',
                    ['id_pembayaran' => $pembayaran->id_pembayaran]
                );
            }
    
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Pembayaran berhasil diverifikasi']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal memverifikasi: ' . $e->getMessage()]);
        }
    }
    
    private function generateOrderDetails($pesananMakanan) {
        $details = [];
        foreach ($pesananMakanan as $pesanan) {
            $details[] = "{$pesanan->jumlah}x {$pesanan->katalogMakanan->nama_makanan}";
        }
        return "Pembayaran Order Menu: " . implode(", ", $details);
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id_metode',
                'bukti_pembayaran' => 'required|image|max:2048',
                'jumlah_pembayaran' => 'required|numeric',
                'keterangan' => 'required|string', // Add validation for keterangan
            ]);

            // Get penyewa ID from authenticated user
            $user = auth()->user();
            $penyewa = Penyewa::where('id_user', $user->id_user)->first();
            
            if (!$penyewa) {
                throw new \Exception('Data penyewa tidak ditemukan');
            }

            // Save payment proof file
            $buktiPath = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');

            // Create new payment record
            $pembayaran = Pembayaran::create([
                'id_user' => $user->id_user,
                'id_metode' => $request->metode_pembayaran_id,
                'id_penyewa' => $penyewa->id_penyewa,
                'jumlah_pembayaran' => $request->jumlah_pembayaran,
                'bukti_pembayaran' => $buktiPath,
                'status_verifikasi' => 'pending',
                'tanggal_pembayaran' => now(),
                'keterangan' => $request->keterangan, // Save keterangan
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Bukti pembayaran berhasil diupload',
                'data' => $pembayaran
            ]);
        } catch (\Exception $e) {
            \Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal upload bukti pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function histori(Request $request)
    {
        try {
            $year = $request->query('year', date('Y'));
            
            $historiPembayaran = Pembayaran::with(['metodePembayaran'])
                ->where('id_user', auth()->id())
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($pembayaran) {
                    return [
                        'id' => $pembayaran->id_pembayaran,
                        'tanggal_pembayaran' => $pembayaran->tanggal_pembayaran,
                        'jumlah_pembayaran' => $pembayaran->jumlah_pembayaran,
                        'status' => $pembayaran->status_verifikasi,
                        'metode_pembayaran' => [
                            'nama_metode' => $pembayaran->metodePembayaran->nama,
                            'id_metode' => $pembayaran->metodePembayaran->id_metode
                        ],
                        'keterangan' => $pembayaran->keterangan
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Data histori pembayaran berhasil diambil',
                'data' => $historiPembayaran
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data histori pembayaran'
            ], 500);
        }
    }
}
