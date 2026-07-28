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

            $table->string('nama_antrean');
            $table->string('poli');
            $table->string('kategori_prioritas')->nullable();
            $table->string('nomor_antrean');
            $table->enum('status', ['waiting', 'called', 'done'])->default('waiting');
            $table->timestamp('waktu_panggil')->nullable();
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
