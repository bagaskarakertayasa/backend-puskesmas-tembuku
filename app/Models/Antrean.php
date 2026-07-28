<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Antrean extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama_antrean',
        'kategori_prioritas',
        'poli',
        'nomor_antrean',
        'status',
        'waktu_panggil'
    ];
}
