<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan_Pengeluaran;
use App\Models\Pembayaran;
use App\Models\metode_pembayaran;
use App\Models\Penyewa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class PemasukanPengeluaranController extends Controller
{
    // Mendapatkan semua transaksi
    public function getAll()
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::with([
                'penyewa.user',
                'penyewa.unit_kamar',
                'pembayaran.metodePembayaran'
            ])
            ->orderBy('tanggal', 'desc')
            ->get()
            ->map(function ($item) {
                // Dapatkan data pembayaran jika ada
                $pembayaranData = $item->pembayaran;
                $metodePembayaranData = null;

                // Set metode pembayaran berdasarkan kondisi
                if ($pembayaranData && $pembayaranData->metodePembayaran) {
                    $metodePembayaranData = [
                        'nama_metode' => $pembayaranData->metodePembayaran->nama,
                        'id_metode' => $pembayaranData->metodePembayaran->id_metode
                    ];
                } elseif ($item->kategori == 'Pembayaran Sewa' && !$pembayaranData) {
                    // Jika kategori Pembayaran Sewa tapi tidak ada id_pembayaran
                    $metodePembayaranData = [
                        'nama_metode' => 'Pembayaran Awal',
                        'id_metode' => null
                    ];
                }

                return [
                    'id_transaksi' => $item->id_transaksi,
                    'id_pembayaran' => $pembayaranData ? $pembayaranData->id_pembayaran : null,
                    'jenis_transaksi' => $item->jenis_transaksi,
                    'kategori' => $item->kategori,
                    'tanggal' => $item->tanggal,
                    'tanggal_pembayaran' => $pembayaranData ? $pembayaranData->tanggal_pembayaran : null,
                    'jumlah' => $item->jumlah,
                    'keterangan' => $item->keterangan,
                    'status_verifikasi' => $pembayaranData ? $pembayaranData->status_verifikasi : null,
                    'penyewa' => $item->penyewa ? [
                        'user' => [
                            'name' => $item->penyewa->user->name
                        ],
                        'unit_kamar' => [
                            'nomor_kamar' => $item->penyewa->unit_kamar->nomor_kamar
                        ]
                    ] : null,
                    'metode_pembayaran' => $metodePembayaranData
                ];
            });

            return response()->json([
                'message' => 'Berhasil mendapatkan data transaksi',
                'data' => $transaksi
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data transaksi',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    // Mendapatkan transaksi by ID
    public function getById($id)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::findOrFail($id);
            return response()->json([
                'message' => 'Berhasil mendapatkan data transaksi',
                'data' => $transaksi
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan data transaksi',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // Membuat transaksi baru
    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'jenis_transaksi' => 'required|in:pemasukan,pengeluaran',
                'kategori' => 'required|string',
                'tanggal' => 'required|date',
                'jumlah' => 'required|numeric',
                'keterangan' => 'nullable|string',
                'id_penyewa' => 'required_if:is_from_register,true|exists:penyewa,id_penyewa',
                'is_from_register' => 'boolean',
                'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Tambahkan bulan dan tahun dari tanggal
            $tanggal = date('Y-m-d', strtotime($validated['tanggal']));
            $validated['bulan'] = date('n', strtotime($tanggal));
            $validated['tahun'] = date('Y', strtotime($tanggal));

            // Hanya tambahkan ID Penyewa ke keterangan jika bukan dari admin (dari register)
            if (isset($validated['is_from_register']) && $validated['is_from_register'] && isset($validated['id_penyewa'])) {
                $validated['keterangan'] = ($validated['keterangan'] ?? '');
            }

            // Hitung saldo
            $saldoSebelumnya = Pemasukan_Pengeluaran::latest()->value('saldo') ?? 0;
            $validated['saldo'] = $validated['jenis_transaksi'] === 'pemasukan' 
                ? $saldoSebelumnya + $validated['jumlah']
                : $saldoSebelumnya - $validated['jumlah'];

            // Hapus is_from_register dari validated data karena tidak ada di database
            unset($validated['is_from_register']);

            $transaksi = Pemasukan_Pengeluaran::create($validated);

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'data' => $transaksi
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat transaksi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Update transaksi
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $transaksi = Pemasukan_Pengeluaran::findOrFail($id);
            
            // Simpan bukti_transaksi yang ada sebelumnya
            $existingBuktiTransaksi = $transaksi->bukti_transaksi;

            $validated = $request->validate([
                'jenis_transaksi' => 'sometimes|required|in:pemasukan,pengeluaran',
                'kategori' => 'sometimes|required|string',
                'tanggal' => 'sometimes|required|date',
                'jumlah' => 'sometimes|required|numeric',
                'keterangan' => 'nullable|string',
                'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg|max:10000'
            ]);

            // Upload bukti transaksi baru jika ada
            if ($request->hasFile('bukti_transaksi')) {
                // Hapus bukti transaksi lama
                if ($transaksi->bukti_transaksi) {
                    Storage::delete(str_replace('storage/', 'public/', $transaksi->bukti_transaksi));
                }
                
                $filePath = $request->file('bukti_transaksi')->store('bukti-transaksi', 'public');
                $validated['bukti_transaksi'] = 'storage/' . $filePath;
            } else {
                // Jika tidak ada file baru diupload, pertahankan bukti transaksi yang lama
                $validated['bukti_transaksi'] = $existingBuktiTransaksi;
            }
            

            // Update bulan dan tahun hanya jika tanggal diupdate
            if (isset($validated['tanggal'])) {
                $tanggal = date('Y-m-d', strtotime($validated['tanggal']));
                $validated['bulan'] = date('n', strtotime($tanggal));
                $validated['tahun'] = date('Y', strtotime($tanggal));
            }

            // Update transaksi
            $transaksi->update($validated);

            // Recalculate saldo for all subsequent transactions
            $subsequentTransaksi = Pemasukan_Pengeluaran::where('id_transaksi', '>=', $id)
                ->orderBy('tanggal', 'asc')
                ->orderBy('id_transaksi', 'asc')
                ->get();

            $saldo = $transaksi->id_transaksi === 1 ? 0 : 
                Pemasukan_Pengeluaran::where('id_transaksi', '<', $id)
                    ->latest('id_transaksi')
                    ->value('saldo');

            foreach ($subsequentTransaksi as $trans) {
                $saldo = $trans->jenis_transaksi === 'pemasukan' 
                    ? $saldo + $trans->jumlah 
                    : $saldo - $trans->jumlah;
                $trans->update(['saldo' => $saldo]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil diupdate',
                'data' => $transaksi
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengupdate transaksi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Delete transaksi
    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $transaksi = Pemasukan_Pengeluaran::findOrFail($id);

            // Hapus bukti transaksi jika ada
            if ($transaksi->bukti_transaksi) {
                Storage::delete(str_replace('storage/', 'public/', $transaksi->bukti_transaksi));
            }

            // Recalculate saldo for subsequent transactions
            $subsequentTransaksi = Pemasukan_Pengeluaran::where('id_transaksi', '>', $id)
                ->orderBy('tanggal', 'asc')
                ->orderBy('id_transaksi', 'asc')
                ->get();

            $saldo = $id === 1 ? 0 : Pemasukan_Pengeluaran::where('id_transaksi', '<', $id)->latest()->value('saldo');

            foreach ($subsequentTransaksi as $trans) {
                $saldo = $trans->jenis_transaksi === 'pemasukan' 
                    ? $saldo + $trans->jumlah 
                    : $saldo - $trans->jumlah;
                $trans->update(['saldo' => $saldo]);
            }

            $transaksi->delete();

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil dihapus'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus transaksi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Mendapatkan rekap harian
    public function getRekapHarian($tanggal)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::whereDate('tanggal', $tanggal)->get();

            $totalPemasukan = $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
            $totalPengeluaran = $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');
            $saldoAkhir = $transaksi->last() ? $transaksi->last()->saldo : 0;

            return response()->json([
                'message' => 'Berhasil mendapatkan rekap harian',
                'data' => [
                    'tanggal' => $tanggal,
                    'total_pemasukan' => $totalPemasukan,
                    'total_pengeluaran' => $totalPengeluaran,
                    'saldo_akhir' => $saldoAkhir,
                    'detail_transaksi' => $transaksi
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan rekap harian',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Mendapatkan rekap bulanan
    public function getRekapBulanan($bulan, $tahun)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            $totalPemasukan = $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
            $totalPengeluaran = $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');
            $saldoAkhir = $transaksi->last() ? $transaksi->last()->saldo : 0;

            // Rekap per kategori
            $rekapKategori = [];
            foreach ($transaksi->groupBy('kategori') as $kategori => $trans) {
                $rekapKategori[] = [
                    'kategori' => $kategori,
                    'total_pemasukan' => $trans->where('jenis_transaksi', 'pemasukan')->sum('jumlah'),
                    'total_pengeluaran' => $trans->where('jenis_transaksi', 'pengeluaran')->sum('jumlah')
                ];
            }

            return response()->json([
                'message' => 'Berhasil mendapatkan rekap bulanan',
                'data' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'total_pemasukan' => $totalPemasukan,
                    'total_pengeluaran' => $totalPengeluaran,
                    'saldo_akhir' => $saldoAkhir,
                    'rekap_kategori' => $rekapKategori,
                    'detail_transaksi' => $transaksi
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan rekap bulanan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Mendapatkan rekap tahunan
    public function getRekapTahunan($tahun)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::where('tahun', $tahun)->get();

            $rekapBulanan = [];
            for ($i = 1; $i <= 12; $i++) {
                $transaksiBulan = $transaksi->where('bulan', $i);
                $rekapBulanan[] = [
                    'bulan' => $i,
                    'total_pemasukan' => $transaksiBulan->where('jenis_transaksi', 'pemasukan')->sum('jumlah'),
                    'total_pengeluaran' => $transaksiBulan->where('jenis_transaksi', 'pengeluaran')->sum('jumlah'),
                    'saldo_akhir' => $transaksiBulan->last() ? $transaksiBulan->last()->saldo : 0
                ];
            }

            // Rekap per kategori tahunan
            $rekapKategori = [];
            foreach ($transaksi->groupBy('kategori') as $kategori => $trans) {
                $rekapKategori[] = [
                    'kategori' => $kategori,
                    'total_pemasukan' => $trans->where('jenis_transaksi', 'pemasukan')->sum('jumlah'),
                    'total_pengeluaran' => $trans->where('jenis_transaksi', 'pengeluaran')->sum('jumlah')
                ];
            }

            return response()->json([
                'message' => 'Berhasil mendapatkan rekap tahunan',
                'data' => [
                    'tahun' => $tahun,
                    'total_pemasukan_tahunan' => $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah'),
                    'total_pengeluaran_tahunan' => $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah'),
                    'saldo_akhir_tahun' => $transaksi->last() ? $transaksi->last()->saldo : 0,
                    'rekap_bulanan' => $rekapBulanan,
                    'rekap_kategori' => $rekapKategori
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan rekap tahunan',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getRiwayatTransaksiPenyewa($idPenyewa)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::where('id_penyewa', $idPenyewa)
                ->where('kategori', 'Pembayaran Sewa')
                ->orderBy('tanggal', 'desc')
                ->get();

            return response()->json([
                'message' => 'Berhasil mendapatkan riwayat transaksi',
                'data' => $transaksi
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mendapatkan riwayat transaksi',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getTransaksiByDate($date)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::whereDate('tanggal', $date)
                ->orderBy('tanggal', 'desc')
                ->get();

            $totalPemasukan = $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
            $totalPengeluaran = $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');

            return response()->json([
                'status' => true,
                'data' => [
                    'transaksi' => $transaksi,
                    'total_pemasukan' => $totalPemasukan,
                    'total_pengeluaran' => $totalPengeluaran
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTransaksiByJenis($jenis)
    {
        try {
            $transaksi = Pemasukan_Pengeluaran::where('jenis_transaksi', $jenis)
                ->orderBy('tanggal', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $transaksi
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}