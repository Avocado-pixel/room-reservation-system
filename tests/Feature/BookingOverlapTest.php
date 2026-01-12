<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_bookings_are_blocked(): void
    {
        $service = new BookingService();
        $room = Room::factory()->create(['status' => 'available', 'record_status' => 'active']);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $start = Carbon::parse('2026-01-10 10:00', config('app.timezone'));
        $end = Carbon::parse('2026-01-10 11:00', config('app.timezone'));

        $service->createOneTimeBooking($userA, $room, $start, $end);

        $overlapStart = Carbon::parse('2026-01-10 10:30', config('app.timezone'));
        $overlapEnd = Carbon::parse('2026-01-10 11:30', config('app.timezone'));

        try {
            $service->createOneTimeBooking($userB, $room, $overlapStart, $overlapEnd);
            $this->fail('Expected overlap to raise a conflict.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Room already booked for this slot.', $e->getMessage());
        }

        $this->assertEquals(1, Booking::count());
    }
}
