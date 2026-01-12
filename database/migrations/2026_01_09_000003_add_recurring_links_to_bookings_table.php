<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('recurring_reservation_id')
                ->nullable()
                ->after('room_id')
                ->constrained('recurring_reservations')
                ->nullOnDelete();

            $table->index(['recurring_reservation_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['recurring_reservation_id', 'start_at']);
            $table->dropConstrainedForeignId('recurring_reservation_id');
        });
    }
};
