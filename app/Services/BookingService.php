<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RecurringReservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    private const SLOT_MINUTES = 30;
    private const MAX_OCCURRENCES = 90;
    private const MAX_DURATION_MINUTES = 120;
    private const DAY_START_HOUR = 8;
    private const DAY_END_HOUR = 20;

    public function isWithinWorkingHours(Carbon $start, Carbon $end): bool
    {
        $dayStart = $start->copy()->startOfDay()->setTime(self::DAY_START_HOUR, 0, 0);
        $dayEnd = $start->copy()->startOfDay()->setTime(self::DAY_END_HOUR, 0, 0);
        return $start->greaterThanOrEqualTo($dayStart) && $end->lessThanOrEqualTo($dayEnd);
    }

    public function isDurationValid(Carbon $start, Carbon $end): bool
    {
        $duration = $start->diffInMinutes($end);
        if ($duration <= 0 || $duration > self::MAX_DURATION_MINUTES) {
            return false;
        }
        return $duration % self::SLOT_MINUTES === 0;
    }

    public function isAvailable(Room $room, Carbon $start, Carbon $end, ?int $ignoreBookingId = null): bool
    {
        $startCol = Booking::startColumn();
        $endCol = Booking::endColumn();

        $conflict = Booking::query()
            ->where('room_id', $room->id)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where($startCol, '<', $end)
            ->where($endCol, '>', $start)
            ->exists();

        return !$conflict;
    }

    public function createOneTimeBooking(User $user, Room $room, Carbon $start, Carbon $end, ?int $recurringReservationId = null): Booking
    {
        return DB::transaction(function () use ($user, $room, $start, $end, $recurringReservationId): Booking {
            // Lock potentially conflicting rows to avoid race conditions.
            $startCol = Booking::startColumn();
            $endCol = Booking::endColumn();

            Booking::query()
                ->where('room_id', $room->id)
                ->where($startCol, '<', $end)
                ->where($endCol, '>', $start)
                ->lockForUpdate()
                ->get('id');

            if (!$this->isAvailable($room, $start, $end, null)) {
                throw new \RuntimeException('Room already booked for this slot.');
            }

            return Booking::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'recurring_reservation_id' => $recurringReservationId,
                'start_date' => $start,
                'end_date' => $end,
                'share_token' => Str::random(16),
            ]);
        });
    }

    public function createRecurringReservation(User $user, Room $room, string $recurrenceType, array $daysOfWeek, Carbon $startDate, Carbon $endDate, string $startTime, string $endTime): RecurringReservation
    {
        $timezone = config('app.timezone');
        $startTimeObj = Carbon::createFromFormat('H:i', $startTime, $timezone);
        $endTimeObj = Carbon::createFromFormat('H:i', $endTime, $timezone);

        $occurrences = $this->buildOccurrences($daysOfWeek, $startDate, $endDate, $startTimeObj, $endTimeObj);

        if (count($occurrences) === 0) {
            throw new \InvalidArgumentException('No occurrences generated for the selected pattern.');
        }

        DB::beginTransaction();
        try {
            $recurring = RecurringReservation::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'recurrence_type' => $recurrenceType,
                'days_of_week' => array_values($daysOfWeek),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $timezone,
                'status' => 'active',
                'share_token' => Str::random(20),
            ]);

            foreach ($occurrences as [$start, $end]) {
                if (!$this->isDurationValid($start, $end) || !$this->isWithinWorkingHours($start, $end)) {
                    throw new \RuntimeException('Occurrence outside allowed duration or working hours.');
                }
                if (!$this->isAvailable($room, $start, $end)) {
                    throw new \RuntimeException('One or more occurrences conflict with existing bookings.');
                }

                $this->createOneTimeBooking($user, $room, $start, $end, $recurring->id);
            }

            DB::commit();
            return $recurring;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function buildOccurrences(array $daysOfWeek, Carbon $startDate, Carbon $endDate, Carbon $startTime, Carbon $endTime): array
    {
        $days = collect($daysOfWeek)
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 0 && $d <= 6)
            ->unique()
            ->values();

        $occurrences = [];
        $cursor = $startDate->copy();
        $limit = self::MAX_OCCURRENCES;

        while ($cursor->lte($endDate)) {
            if (!$days->contains($cursor->dayOfWeek)) {
                $cursor->addDay();
                continue;
            }

            $start = Carbon::create(
                $cursor->year,
                $cursor->month,
                $cursor->day,
                (int) $startTime->format('H'),
                (int) $startTime->format('i'),
                0,
                $startTime->getTimezone()
            );

            $end = Carbon::create(
                $cursor->year,
                $cursor->month,
                $cursor->day,
                (int) $endTime->format('H'),
                (int) $endTime->format('i'),
                0,
                $endTime->getTimezone()
            );

            $occurrences[] = [$start, $end];
            if (count($occurrences) >= $limit) {
                break;
            }

            $cursor->addDay();
        }

        return $occurrences;
    }
}
