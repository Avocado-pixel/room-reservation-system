<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sala_id')->constrained('salas')->cascadeOnDelete();

            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');

            $table->string('token_partilha', 255)->nullable();

            $table->timestamps();

            $table->index(['sala_id', 'data_inicio']);
            $table->index(['user_id', 'data_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
