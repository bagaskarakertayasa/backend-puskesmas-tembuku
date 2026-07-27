<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('antreans', function (Blueprint $table) {
            $table->id();

            $table->enum('poli', ['umum', 'kia', 'anak', 'gigi', 'imunisasi', 'ugd', 'kesehatan', 'surat', 'lain'])->default('umum');
            $table->enum('prioritas', ['hamil', 'lansia', 'anak', 'disabilitas', 'gawat'])
                ->nullable()
                ->default(null);
            $table->string('nomor_antrean');
            $table->enum('status', ['menunggu', 'dipanggil', 'selesai'])->default('menunggu');
            $table->timestamp('waktu_ambil');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antreans');
    }
};
