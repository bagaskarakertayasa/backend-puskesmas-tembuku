<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorController extends Controller
{
    // 1. Auth Operator Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */ //
            $user = Auth::user();
            $token = $user->createToken('operator-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login Berhasil',
                'token'   => $token,
                'user'    => $user
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah'
        ], 401);
    }

    // 2. Auth Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout Berhasil']);
    }

    // Urutan: status 'waiting' paling atas (terlama dulu / FIFO), lalu 'called'/'done' paling bawah
    // 3. Get Semua Antrean Terurut & Filter Hari Ini + Pencarian
    public function getQueueList(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search  = $request->query('search');

        $query = Antrean::whereDate('created_at', now()->today());

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_antrean', 'LIKE', "%{$search}%")
                  ->orWhere('nama_antrean', 'LIKE', "%{$search}%")
                  ->orWhere('kategori_prioritas', 'LIKE', "%{$search}%")
                  ->orWhere('poli', 'LIKE', "%{$search}%");
            });
        }

        // Pengurutan: 
        // 1. Status 'waiting'
        // 2. Kategori Prioritas diutamakan (IS NOT NULL = 1, NULL = 0 -> DESC)
        // 3. Waktu pembuatan (FIFO)
        $queues = $query->orderByRaw("
            CASE 
                WHEN status = 'waiting' THEN 1 
                WHEN status = 'called' THEN 2 
                ELSE 3 
            END ASC
        ")
        ->orderByRaw("CASE WHEN kategori_prioritas IS NOT NULL THEN 1 ELSE 2 END ASC")
        ->orderBy('created_at', 'asc')
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $queues->items(),
            'meta'    => [
                'current_page' => $queues->currentPage(),
                'last_page'    => $queues->lastPage(),
                'per_page'     => $queues->perPage(),
                'total'        => $queues->total(),
            ]
        ]);
    }

    // Helper untuk menghitung statistik total pasien per kategori
// Helper untuk menghitung statistik total pasien per kategori KHUSUS HARI INI
private function getCategoryRecap()
{
    return [
        'bpjs'      => Antrean::where('nama_antrean', 'PASIEN BPJS')->whereDate('created_at', now()->today())->count(),
        'umum'      => Antrean::where('nama_antrean', 'PASIEN UMUM DAN SURAT KETERANGAN')->whereDate('created_at', now()->today())->count(),
        'online'    => Antrean::where('nama_antrean', 'PENDAFTARAN ONLINE')->whereDate('created_at', now()->today())->count(),
        'prioritas' => Antrean::where('nama_antrean', 'PASIEN PRIORITAS')->whereDate('created_at', now()->today())->count(),
        'tamu'      => Antrean::where('nama_antrean', 'TAMU')->whereDate('created_at', now()->today())->count(),
    ];
}

// Export CSV / Excel
public function exportCsv()
{
    $recap = $this->getCategoryRecap();

    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=rekap_antrean_puskesmas_hari_ini.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columns = [
        'PASIEN BPJS',
        'PASIEN UMUM DAN SURAT KETERANGAN',
        'PENDAFTARAN ONLINE',
        'PASIEN PRIORITAS',
        'TAMU'
    ];

    $data = [
        "{$recap['bpjs']} pasien",
        "{$recap['umum']} pasien",
        "{$recap['online']} pasien",
        "{$recap['prioritas']} pasien",
        "{$recap['tamu']} pasien",
    ];

    $callback = function() use ($columns, $data) {
        $file = fopen('php://output', 'w');
        // UTF-8 BOM agar Excel dapat membaca format UTF-8 dengan baik
        fputs($file, "\xEF\xBB\xBF");
        
        // Menggunakan delimiter titik koma (;) agar otomatis terpisah kolom di Excel regional Indonesia
        fputcsv($file, $columns, ';');
        fputcsv($file, $data, ';');
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

// Export PDF
public function exportPdf()
{
    $recap = $this->getCategoryRecap();
    $tanggal = now()->isoFormat('D MMMM YYYY');
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Rekap Total Antrean Puskesmas Hari Ini</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            h2 { text-align: center; margin-bottom: 5px; }
            p.date { text-align: center; font-size: 14px; color: #666; margin-bottom: 25px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #333; padding: 12px 8px; text-align: center; font-size: 13px; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body onload='window.print()'>
        <h2>REKAP TOTAL PASIEN UPTD PUSKESMAS TEMBUKU II</h2>
        <p class='date'>Tanggal: {$tanggal}</p>
        <table>
            <thead>
                <tr>
                    <th>PASIEN BPJS</th>
                    <th>PASIEN UMUM & SURAT KETERANGAN</th>
                    <th>PENDAFTARAN ONLINE</th>
                    <th>PASIEN PRIORITAS</th>
                    <th>TAMU</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$recap['bpjs']} pasien</td>
                    <td>{$recap['umum']} pasien</td>
                    <td>{$recap['online']} pasien</td>
                    <td>{$recap['prioritas']} pasien</td>
                    <td>{$recap['tamu']} pasien</td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    ";

    return response($html, 200, ['Content-Type' => 'text/html']);
}

    // 4. Panggil Antrean
    public function callQueue($id)
    {
        $queue = Antrean::find($id);

        if (!$queue) {
            return response()->json(['message' => 'Antrean tidak ditemukan'], 404);
        }

        $queue->update([
            'status'        => 'called',
            'waktu_panggil' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Memanggil antrean {$queue->nomor_antrean}",
            'data'    => $queue
        ]);
    }

    public function selesai($id): JsonResponse
    {
        $antrian = Antrean::find($id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Data antrean tidak ditemukan.'
            ], 404);
        }

        $antrian->update([
            'status' => 'done'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Antrean telah diselesaikan.',
            'data'    => $antrian
        ], 200);
    }
}
