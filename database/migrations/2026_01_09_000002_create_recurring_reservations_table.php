<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->enum('recurrence_type', ['weekly', 'custom_days']);
            $table->json('days_of_week'); // array of weekday integers 0 (Sun) - 6 (Sat)
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64)->default(config('app.timezone', 'UTC'));
            $table->enum('status', ['active', 'canceled', 'ended'])->default('active');
            $table->string('share_token', 64)->nullable();
            $table->timestamps();

            $table->index(['room_id', 'start_date']);
            $table->index(['user_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_reservations');
    }
};
