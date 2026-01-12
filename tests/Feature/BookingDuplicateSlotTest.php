<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDuplicateSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_duplicate_slot_is_rejected(): void
    {
        $service = new BookingService();
        $room = Room::factory()->create(['status' => 'available', 'record_status' => 'active']);
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        $start = Carbon::parse('2026-01-10 14:00', config('app.timezone'));
        $end = Carbon::parse('2026-01-10 15:00', config('app.timezone'));

        $service->createOneTimeBooking($userA, $room, $start, $end);

        try {
            $service->createOneTimeBooking($userB, $room, $start, $end);
            $this->fail('Expected duplicate slot to be rejected.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Room already booked for this slot.', $e->getMessage());
        }

        $this->assertEquals(1, Booking::count());
    }
}
