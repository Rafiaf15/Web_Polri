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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day'); // Senin, Selasa, dll
            $table->date('date');
            $table->string('time'); // 08:00 - 16:00
            $table->json('activities'); // Array kegiatan dengan jam
            $table->enum('status', ['available', 'conflict'])->default('available'); // Status jadwal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
}; 