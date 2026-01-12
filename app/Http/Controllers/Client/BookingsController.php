<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\StoreRecurringBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\BookingExportService;
use App\Support\QueryPresets;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Services\Audit\AuditLogger;

/**
 * Handles all booking operations for authenticated clients.
 *
 * Provides functionality for viewing, creating, editing, and canceling
 * bookings, as well as recurring reservations and calendar exports.
 *
 * @category Client
 * @package  App\Http\Controllers\Client
 */
class BookingsController extends Controller
{
	private const SLOT_MINUTES = 30;
	private const DAY_START_HOUR = 8;
	private const DAY_END_HOUR = 20;

	public function __construct(
		private readonly BookingService $bookingService,
		private readonly BookingExportService $exportService,
	) {
	}

	/**
	 * Display a listing of the client's bookings.
	 */
	public function index(Request $request): View
	{
		$filter = $request->query('filter', 'all');
		$bookings = QueryPresets::clientBookings($request, (int) $request->user()->id);

		return view('client.bookings.index', [
			'bookings' => $bookings,
			'filter' => $filter,
		]);
	}

	/**
	 * Show the booking creation form for a room.
	 */
	public function create(Request $request, Room $room): View
	{
		abort_if($room->record_status === 'deleted', 404);
		abort_if($room->status !== 'available', 403, 'Room unavailable.');

		return view('client.bookings.create', ['room' => $room]);
	}

	public function availability(Request $request, Room $room): JsonResponse
	{
		abort_if($room->record_status === 'deleted', 404);
		abort_if($room->status !== 'available', 403, 'Room unavailable.');

		$validated = $request->validate([
			'date' => ['required', 'date_format:Y-m-d'],
			'duration' => ['required', 'integer', 'min:30', 'max:120'],
			'exclude_booking_id' => ['nullable', 'integer', 'min:1'],
		]);

		$date = $validated['date'];
		$duration = (int) $validated['duration'];
		$excludeBookingId = isset($validated['exclude_booking_id']) ? (int) $validated['exclude_booking_id'] : null;

		if ($duration % self::SLOT_MINUTES !== 0) {
			return response()->json([
				'message' => 'Duration must be a multiple of 30 minutes.',
			], 422);
		}
		if ($duration > 120) {
			return response()->json([
				'message' => 'Maximum booking duration is 120 minutes.',
			], 422);
		}

		$tz = config('app.timezone');
		$startDay = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $tz);
		$endDay = $startDay->copy()->addDay();

		$dayStart = $startDay->copy()->setTime(self::DAY_START_HOUR, 0, 0);
		$dayEnd = $startDay->copy()->setTime(self::DAY_END_HOUR, 0, 0);

		$minStart = $dayStart->copy();
		$now = now($tz);
		if ($now->isSameDay($startDay)) {
			$minStart = $this->roundUpToSlot($now, self::SLOT_MINUTES)->max($dayStart);
		}
		if ($startDay->isBefore($now->copy()->startOfDay())) {
			return response()->json([
				'date' => $date,
				'duration' => $duration,
				'timezone' => $tz,
				'window' => ['start' => sprintf('%02d:00', self::DAY_START_HOUR), 'end' => sprintf('%02d:00', self::DAY_END_HOUR)],
				'slots' => [],
			]);
		}

		$startCol = Booking::startColumn();
		$endCol = Booking::endColumn();
		$excludeId = null;
		if ($excludeBookingId) {
			$exclude = Booking::query()
				->whereKey($excludeBookingId)
				->where('room_id', $room->id)
				->where('user_id', $request->user()->id)
				->first(['id']);
			$excludeId = $exclude?->id;
		}

		$bookings = Booking::query()
			->where('room_id', $room->id)
			->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
			->where($startCol, '<', $endDay)
			->where($endCol, '>', $startDay)
			->get([$startCol, $endCol]);

		$intervals = $bookings->map(function (Booking $b) use ($startCol, $endCol) {
			$start = $b->getAttribute($startCol);
			$end = $b->getAttribute($endCol);
			$startTs = $start instanceof Carbon ? $start->getTimestamp() : Carbon::parse($start)->getTimestamp();
			$endTs = $end instanceof Carbon ? $end->getTimestamp() : Carbon::parse($end)->getTimestamp();
			return [$startTs, $endTs];
		})->values();

		$slots = [];
		$candidate = $minStart->copy();
		while ($candidate->copy()->addMinutes($duration)->lte($dayEnd)) {
			$end = $candidate->copy()->addMinutes($duration);

			$cStart = $candidate->getTimestamp();
			$cEnd = $end->getTimestamp();
			$conflict = false;
			foreach ($intervals as [$bStart, $bEnd]) {
				// overlap if start < otherEnd && end > otherStart
				if ($cStart < $bEnd && $cEnd > $bStart) {
					$conflict = true;
					break;
				}
			}

			if (!$conflict) {
				$slots[] = $candidate->format('H:i');
			}

			$candidate->addMinutes(self::SLOT_MINUTES);
		}

		return response()->json([
			'date' => $date,
			'duration' => $duration,
			'timezone' => $tz,
			'window' => ['start' => sprintf('%02d:00', self::DAY_START_HOUR), 'end' => sprintf('%02d:00', self::DAY_END_HOUR)],
			'slots' => $slots,
		]);
	}

	/**
	 * Store a new booking for a room.
	 */
	public function store(StoreBookingRequest $request, Room $room): RedirectResponse
	{
		abort_if($room->record_status === 'deleted', 404);
		abort_if($room->status !== 'available', 403, 'Room unavailable.');

		$validated = $request->validated();
		$date = $validated['date'];
		$time = $validated['time'];
		$duration = (int) $validated['duration'];

		$start = Carbon::createFromFormat('Y-m-d H:i', "$date $time", config('app.timezone'));
		if (!$start) {
			return back()->withErrors(['date' => 'Invalid date/time.'])->withInput();
		}

		$end = $start->copy()->addMinutes($duration);
		if (!$this->bookingService->isDurationValid($start, $end)) {
			return back()->withErrors(['duration' => 'Duration must be in 30-minute blocks and within allowed limits.'])->withInput();
		}
		if (!$this->bookingService->isWithinWorkingHours($start, $end)) {
			return back()->withErrors(['time' => 'Selected time is outside working hours.'])->withInput();
		}
		if ($start->isPast()) {
			return back()->withErrors(['date' => 'You cannot book a past date/time.'])->withInput();
		}

		if (!$this->bookingService->isAvailable($room, $start, $end)) {
			return back()->withErrors(['time' => 'This room is already booked for that time. Choose another slot.'])->withInput();
		}

		$booking = $this->bookingService->createOneTimeBooking($request->user(), $room, $start, $end);

		app(AuditLogger::class)->log('booking.created', [
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'after' => [
				'room_id' => $booking->room_id,
				'client_id' => $booking->user_id,
				'start_date' => $booking->start_date,
				'end_date' => $booking->end_date,
			],
		]);

		return redirect()->route('client.bookings.index')->with('status', 'Booking created successfully.');
	}

	/**
	 * Store recurring bookings for a room.
	 */
	public function storeRecurring(StoreRecurringBookingRequest $request, Room $room): RedirectResponse
	{
		abort_if($room->record_status === 'deleted', 404);
		abort_if($room->status !== 'available', 403, 'Room unavailable.');

		$data = $request->validated();
		$timezone = config('app.timezone');

		$startDate = Carbon::createFromFormat('Y-m-d', $data['start_date'], $timezone);
		$endDate = Carbon::createFromFormat('Y-m-d', $data['end_date'], $timezone);
		if (!$startDate || !$endDate) {
			return back()->withErrors(['start_date' => 'Invalid dates provided.'])->withInput();
		}
		if ($startDate->isPast()) {
			return back()->withErrors(['start_date' => 'You cannot start a recurring booking in the past.'])->withInput();
		}

		$startTime = Carbon::createFromFormat('H:i', $data['start_time'], $timezone);
		$duration = (int) ($data['duration'] ?? 0);
		if (!$startTime || $duration <= 0) {
			return back()->withErrors(['start_time' => 'Invalid time or duration.'])->withInput();
		}

		$firstStart = Carbon::create(
			$startDate->year,
			$startDate->month,
			$startDate->day,
			(int) $startTime->format('H'),
			(int) $startTime->format('i'),
			0,
			$timezone
		);

		// Prevent booking earlier than the current moment on the first day.
		if ($firstStart->isPast()) {
			return back()->withErrors(['start_time' => 'Choose a start time that is not in the past.'])->withInput();
		}

		$endTimeObj = $startTime->copy()->addMinutes($duration);
		$data['end_time'] = $endTimeObj->format('H:i');

		$daysOfWeek = array_map('intval', $data['days_of_week']);
		$recurrenceType = $data['recurrence_type'];

		try {
			$recurring = $this->bookingService->createRecurringReservation(
				$request->user(),
				$room,
				$recurrenceType,
				$daysOfWeek,
				$startDate,
				$endDate,
				$data['start_time'],
				$data['end_time']
			);

			app(AuditLogger::class)->log('booking.recurring_created', [
				'user_id' => $request->user()?->id,
				'ip' => $request->ip(),
				'user_agent' => $request->userAgent(),
				'subject_type' => \App\Models\RecurringReservation::class,
				'subject_id' => $recurring->id,
				'after' => [
					'room_id' => $recurring->room_id,
					'client_id' => $recurring->user_id,
					'pattern' => $recurrenceType,
					'days_of_week' => $recurring->days_of_week,
					'start_date' => $recurring->start_date,
					'end_date' => $recurring->end_date,
				],
			]);
		} catch (\Throwable $e) {
			return back()->withErrors(['recurring' => $e->getMessage()])->withInput();
		}

		return redirect()->route('client.bookings.index')->with('status', 'Recurring bookings created successfully.');
	}

	/**
	 * Show the edit form for an existing booking.
	 */
	public function edit(Request $request, Booking $booking): View
	{
		abort_if($booking->user_id !== $request->user()->id, 403);
		$booking->loadMissing('room');
		abort_if(!$booking->room, 404);

		$tz = config('app.timezone');
		$now = now($tz);
		$start = $booking->start_date;
		$end = $booking->end_date;
		abort_if(!$start || !$end, 404);
		abort_if($start->lte($now), 403, 'You can only edit future bookings.');

		$duration = max(30, (int) $start->diffInMinutes($end));

		return view('client.bookings.edit', [
			'booking' => $booking,
			'room' => $booking->room,
			'initialDate' => $start->format('Y-m-d'),
			'initialTime' => $start->format('H:i'),
			'initialDuration' => $duration,
		]);
	}

	/**
	 * Export booking as ICS calendar file.
	 */
	public function exportIcs(Request $request, Booking $booking): Response
	{
		abort_if($booking->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
		abort_if(!$booking->room, 404);

		try {
			$ics = $this->exportService->generateIcs($booking);
		} catch (\InvalidArgumentException $e) {
			abort(404, $e->getMessage());
		}

		app(AuditLogger::class)->log('booking.export.ics', [
			'user_id' => $request->user()->id,
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'after' => [
				'room_id' => $booking->room_id,
				'room_name' => $booking->room->name ?? null,
				'format' => 'ics',
			],
		]);

		return response($ics, 200, [
			'Content-Type' => 'text/calendar; charset=utf-8',
			'Content-Disposition' => 'attachment; filename="booking-' . $booking->id . '.ics"',
		]);
	}

	/**
	 * Redirect to Google Calendar with booking details.
	 */
	public function exportGoogle(Request $request, Booking $booking): RedirectResponse
	{
		abort_if($booking->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
		abort_if(!$booking->room, 404);

		try {
			$url = $this->exportService->buildGoogleCalendarUrl($booking);
		} catch (\InvalidArgumentException $e) {
			abort(404, $e->getMessage());
		}

		app(AuditLogger::class)->log('booking.export.google', [
			'user_id' => $request->user()->id,
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'after' => [
				'room_id' => $booking->room_id,
				'room_name' => $booking->room->name ?? null,
				'format' => 'google_calendar',
			],
		]);

		return redirect()->away($url);
	}

	/**
	 * Export booking as PDF document.
	 */
	public function exportPdf(Request $request, Booking $booking): Response
	{
		abort_if($booking->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
		abort_if(!$booking->room, 404);

		try {
			$pdfOutput = $this->exportService->generatePdf($booking);
		} catch (\InvalidArgumentException $e) {
			abort(404, $e->getMessage());
		}

		app(AuditLogger::class)->log('booking.export.pdf', [
			'user_id' => $request->user()->id,
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'after' => [
				'room_id' => $booking->room_id,
				'room_name' => $booking->room->name ?? null,
				'format' => 'pdf',
			],
		]);

		return response($pdfOutput, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="booking-' . $booking->id . '.pdf"',
		]);
	}

	/**
	 * Update an existing booking.
	 */
	public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
	{
		abort_if($booking->user_id !== $request->user()->id, 403);
		$booking->loadMissing('room');
		abort_if(!$booking->room, 404);

		$tz = config('app.timezone');
		$now = now($tz);
		$existingStart = $booking->start_date;
		abort_if(!$existingStart, 404);
		abort_if($existingStart->lte($now), 403, 'You can only edit future bookings.');

		$validated = $request->validated();

		$date = $validated['date'];
		$time = $validated['time'];
		$duration = (int) $validated['duration'];

		$start = Carbon::createFromFormat('Y-m-d H:i', "$date $time", $tz);
		if (!$start) {
			return back()->withErrors(['date' => 'Invalid date/time.'])->withInput();
		}
		if ($start->lte($now)) {
			return back()->withErrors(['date' => 'You cannot book a past date/time.'])->withInput();
		}

		$end = $start->copy()->addMinutes($duration);
		if (!$this->bookingService->isDurationValid($start, $end)) {
			return back()->withErrors(['duration' => 'Duration must be in 30-minute blocks and within allowed limits.'])->withInput();
		}
		if (!$this->bookingService->isWithinWorkingHours($start, $end)) {
			return back()->withErrors(['time' => 'Selected time is outside working hours.'])->withInput();
		}

		if (!$this->bookingService->isAvailable($booking->room, $start, $end, $booking->id)) {
			return back()->withErrors(['time' => 'This room is already booked for that time. Choose another slot.'])->withInput();
		}

		$before = $booking->only(['start_date', 'end_date']);

		$booking->update([
			'start_date' => $start,
			'end_date' => $end,
		]);

		app(AuditLogger::class)->log('booking.updated', [
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'before' => $before,
			'after' => $booking->only(['start_date', 'end_date']),
		]);

		return redirect()->route('client.bookings.index')->with('status', 'Booking updated successfully.');
	}

	/**
	 * Cancel a booking.
	 */
	public function destroy(Request $request, Booking $booking): RedirectResponse
	{
		abort_if($booking->user_id !== $request->user()->id, 403);
		$booking->loadMissing('room.cancellationPolicies');

		$tz = config('app.timezone');
		$now = now($tz);
		$start = $booking->start_date;
		abort_if(!$start, 404);
		abort_if($start->lte($now), 403, 'You can only cancel future bookings.');

		$policy = $booking->room?->cancellationPolicies()->where('is_active', true)->latest()->first();
		if ($policy) {
			$hoursUntilStart = ($start->getTimestamp() - $now->getTimestamp()) / 3600;
			if ($hoursUntilStart < $policy->cancel_before_hours) {
				return back()->withErrors([
					'booking' => sprintf(
						'Cancellation window closed. Must cancel %d hours before start.',
						$policy->cancel_before_hours
					),
				]);
			}
		}

		app(AuditLogger::class)->log('booking.deleted', [
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'subject_type' => Booking::class,
			'subject_id' => $booking->id,
			'before' => $booking->only(['start_date', 'end_date', 'room_id']),
			'after' => ['deleted' => true],
		]);

		$booking->delete();

		return redirect()->route('client.bookings.index')->with('status', 'Booking canceled successfully.');
	}

	private function roundUpToSlot(Carbon $dt, int $slotMinutes): Carbon
	{
		$rounded = $dt->copy()->second(0);
		$minute = (int) $rounded->format('i');
		$remainder = $minute % $slotMinutes;
		if ($remainder === 0) {
			return $rounded;
		}
		return $rounded->addMinutes($slotMinutes - $remainder);
	}
}
