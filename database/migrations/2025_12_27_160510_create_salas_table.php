<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedInteger('capacidade')->default(1);

            // Operational status (public-facing)
            $table->enum('estado', ['available', 'unavailable', 'coming_soon'])->default('available');

            // Photo (file) stored in storage/public/rooms
            $table->string('foto')->nullable();

            // Soft-delete style record status (legacy-compatible)
            $table->enum('estado_registo', ['active', 'blocked', 'deleted'])->default('active');

            $table->timestamps();

            $table->index(['estado_registo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};
