<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_conflict_is_detected_for_overlapping_booking(): void
    {
        $service = new BookingService();
        $room = Room::factory()->create();
        $user = User::factory()->create();

        $start = Carbon::parse('2026-01-10 10:00', config('app.timezone'));
        $end = Carbon::parse('2026-01-10 11:00', config('app.timezone'));

        Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $conflictStart = Carbon::parse('2026-01-10 10:30', config('app.timezone'));
        $conflictEnd = Carbon::parse('2026-01-10 11:30', config('app.timezone'));

        $this->assertFalse($service->isAvailable($room, $conflictStart, $conflictEnd));
    }

    public function test_occurrences_are_generated_within_limits(): void
    {
        $service = new BookingService();
        $startDate = Carbon::parse('2026-01-10', config('app.timezone'));
        $endDate = Carbon::parse('2026-01-20', config('app.timezone'));
        $startTime = Carbon::createFromFormat('H:i', '09:00', config('app.timezone'));
        $endTime = Carbon::createFromFormat('H:i', '10:00', config('app.timezone'));

        $occ = $service->buildOccurrences([1,3], $startDate, $endDate, $startTime, $endTime);

        $this->assertNotEmpty($occ);
        foreach ($occ as [$s, $e]) {
            $this->assertEquals('09:00', $s->format('H:i'));
            $this->assertEquals('10:00', $e->format('H:i'));
        }
    }
}
