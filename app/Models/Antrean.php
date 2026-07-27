<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrean extends Model
{
    use softDeletes;

    protected $fillable = [
        'poli',
        'prioritas',
        'nomor_antrean',
        'status',
        'waktu_ambil'
    ];
}
