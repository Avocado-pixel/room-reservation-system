<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\QueryPresets;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays booking listings for administrators.
 *
 * Provides a filterable view of all bookings across the system,
 * with support for date-based and search query filtering.
 *
 * @category Admin
 * @package  App\Http\Controllers\Admin
 */
class BookingsController extends Controller
{
	/**
	 * Display a listing of bookings filtered by date and search query.
	 */
	public function index(Request $request): View
	{
		$date = trim((string) $request->query('date', ''));
		$q = trim((string) $request->query('q', ''));

		$bookings = collect();
		$bookings = QueryPresets::adminBookingsByDay($request, $date, $q);

		return view('admin.bookings.index', [
			'date' => $date,
			'q' => $q,
			'bookings' => $bookings,
		]);
	}
}
