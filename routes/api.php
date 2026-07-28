<?php

use App\Http\Controllers\AntreanController;
use App\Http\Controllers\OperatorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/antrian', [AntreanController::class, 'store']);
Route::post('/operator/antrian/{id}/panggil', [AntrianController::class, 'panggil']);

Route::get('/antrian', [AntreanController::class, 'index']);

// Endpoint Auth Operator
Route::post('/operator/login', [OperatorController::class, 'login']);

// Endpoint Protected (Hanya Operator Terautentikasi)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/operator/logout', [OperatorController::class, 'logout']);
    Route::get('/operator/antrian', [OperatorController::class, 'getQueueList']);
    Route::post('/operator/antrian/{id}/panggil', [OperatorController::class, 'callQueue']);
});