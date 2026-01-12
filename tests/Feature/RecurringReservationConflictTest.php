<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\RecurringReservation;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringReservationConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_reservation_rolls_back_on_conflict(): void
    {
        $service = new BookingService();
        $room = Room::factory()->create(['status' => 'available', 'record_status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);

        $slotStart = Carbon::parse('2026-01-10 10:00', config('app.timezone'));
        $slotEnd = Carbon::parse('2026-01-10 11:00', config('app.timezone'));
        $service->createOneTimeBooking($user, $room, $slotStart, $slotEnd);

        $startDate = Carbon::parse('2026-01-10', config('app.timezone'));
        $endDate = Carbon::parse('2026-01-17', config('app.timezone'));

        try {
            $service->createRecurringReservation(
                $user,
                $room,
                'weekly',
                [Carbon::SATURDAY],
                $startDate,
                $endDate,
                '10:00',
                '11:00'
            );

            $this->fail('Expected conflict when creating recurring reservation.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('One or more occurrences conflict with existing bookings.', $e->getMessage());
        }

        $this->assertEquals(1, Booking::count());
        $this->assertEquals(0, RecurringReservation::count());
    }
}
