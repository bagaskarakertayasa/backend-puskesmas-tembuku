<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use Illuminate\Http\Request;

class AntreanController extends Controller
{
    public function index()
    {
        $antrian = Antrean::orderBy('created_at', 'desc')->get();

        return response()->json($antrian, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_antrean'       => 'required|string',
            'kategori_prioritas' => 'nullable|string',
            'poli'               => 'required|string',
            'nomor_antrean'      => 'required|string',
        ]);

        $queue = Antrean::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil disimpan!',
            'data'    => $queue
        ], 201);
    }
}
