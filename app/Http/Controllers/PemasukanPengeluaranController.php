<?php

namespace App\Http\Controllers;

use App\Models\PemasukanPengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PemasukanPengeluaranController extends Controller
{
    // Mendapatkan semua transaksi
    public function getAll()
    {
        $transaksi = PemasukanPengeluaran::orderBy('tanggal', 'desc')->get();
        return response()->json($transaksi, 200);
    }

    // Membuat transaksi baru
    public function create(Request $request)
    {
        $request->validate([
            'jenis_transaksi' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('bukti_transaksi')) {
            $bukti = $request->file('bukti_transaksi');
            $path = $bukti->store('public/transaksi');
            $bukti_path = Storage::url($path);
            $request->merge(['bukti_transaksi' => $bukti_path]);
        }

        $transaksi = PemasukanPengeluaran::create($request->all());

        return response()->json($transaksi, 201);
    }

    // Mendapatkan rekap bulanan
    public function getRekapBulanan($bulan, $tahun)
    {
        $transaksi = PemasukanPengeluaran::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $totalPemasukan = $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');
        $saldoAkhir = $transaksi->last() ? $transaksi->last()->saldo : 0;

        return response()->json([
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_akhir' => $saldoAkhir,
            'detail_transaksi' => $transaksi
        ], 200);
    }

    // Mendapatkan rekap tahunan
    public function getRekapTahunan($tahun)
    {
        $transaksi = PemasukanPengeluaran::where('tahun', $tahun)
            ->get();

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

        return response()->json([
            'tahun' => $tahun,
            'rekap_bulanan' => $rekapBulanan,
            'total_pemasukan_tahunan' => $transaksi->where('jenis_transaksi', 'pemasukan')->sum('jumlah'),
            'total_pengeluaran_tahunan' => $transaksi->where('jenis_transaksi', 'pengeluaran')->sum('jumlah'),
            'saldo_akhir_tahun' => $transaksi->last() ? $transaksi->last()->saldo : 0
        ], 200);
    }
}