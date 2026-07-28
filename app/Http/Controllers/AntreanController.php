<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AntreanController extends Controller
{
    public function index()
    {
        // Ambil antrean yang statusnya 'called' dan diurutkan berdasarkan waktu dipanggil terbaru
        $antrian = Antrean::where('status', 'called')
            ->whereDate('created_at', now()->today())
            ->orderBy('waktu_panggil', 'desc')
            ->get();

        return response()->json($antrian, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_antrean'       => 'required|string',
            'kategori_prioritas' => 'nullable|string',
            'poli'               => 'required|string',
            'prefix'             => 'required|string', // A, B, C, atau D
        ]);

        // 1. Cari antrean terakhir yang dibuat HARI INI sesuai dengan prefix-nya
        $lastQueueToday = Antrean::where('nomor_antrean', 'LIKE', $validated['prefix'] . '-%')
            ->whereDate('created_at', now()->today())
            ->orderBy('id', 'desc')
            ->first();

        // 2. Tentukan nomor urut berikutnya
        if ($lastQueueToday) {
            // Ambil angka setelah tanda "-" (misal: dari "A-05" diambil "05")
            $lastNumber = (int) explode('-', $lastQueueToday->nomor_antrean)[1];
            $nextNumber = $lastNumber + 1;
        } else {
            // Jika belum ada antrean di hari ini, mulai dari 1
            $nextNumber = 1;
        }

        // 3. Format nomor antrean (misal: "A-01", "A-02", dst)
        $formattedNumber = sprintf('%s-%02d', $validated['prefix'], $nextNumber);

        // 4. Simpan ke database
        $queue = Antrean::create([
            'nama_antrean'       => $validated['nama_antrean'],
            'kategori_prioritas' => $validated['kategori_prioritas'] ?? null,
            'poli'               => $validated['poli'],
            'nomor_antrean'      => $formattedNumber,
            'status'             => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil disimpan!',
            'data'    => $queue
        ], 201);
    }

    public function panggil($id): JsonResponse
    {
        // 1. Cari data antrean berdasarkan ID
        $antrian = Antrean::find($id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Data antrean tidak ditemukan.'
            ], 404);
        }

        // 2. Update waktu_panggil dengan timestamp saat ini
        $antrian->update([
            'waktu_panggil' => now(), // Menghasilkan format Y-m-d H:i:s
            'status' => 'called' // opsional: jika kamu juga punya kolom status
        ]);

        // 3. Kembalikan response JSON
        return response()->json([
            'success' => true,
            'message' => 'Antrean berhasil dipanggil.',
            'data'    => $antrian
        ], 200);
    }
}
