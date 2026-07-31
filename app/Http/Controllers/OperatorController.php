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

    // 3. Get Semua Antrean Terurut
    // Urutan: status 'waiting' paling atas (terlama dulu / FIFO), lalu 'called'/'done' paling bawah
    public function getQueueList(Request $request)
    {
        // Ambil parameter limit (default: 10 data)
        $perPage = $request->query('per_page', 10);

        // Query antrean terurut: 'waiting' -> 'called' -> 'done'
        $queues = Antrean::orderByRaw("
        CASE 
            WHEN status = 'waiting' THEN 1 
            WHEN status = 'called' THEN 2 
            ELSE 3 
        END ASC
    ")
            ->orderBy('created_at', 'asc')
            ->paginate($perPage); // Gunakan paginate bawaan Laravel

        return response()->json([
            'success' => true,
            'data'    => $queues->items(), // Array data antrean
            'meta'    => [
                'current_page' => $queues->currentPage(),
                'last_page'    => $queues->lastPage(),
                'per_page'     => $queues->perPage(),
                'total'        => $queues->total(),
            ]
        ]);
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
