<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $startCol = Schema::hasColumn('bookings', 'start_date') ? 'start_date' : 'start_at';
            $endCol = Schema::hasColumn('bookings', 'end_date') ? 'end_date' : 'end_at';

            // Prevent exact duplicate time slots per room (exact match guard; overlapping still handled in app/service).
            $table->unique(['room_id', $startCol, $endCol], 'bookings_room_start_end_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique('bookings_room_start_end_unique');
            // Dropping check constraints is driver-specific; ignore if unsupported.
        });
    }
};
