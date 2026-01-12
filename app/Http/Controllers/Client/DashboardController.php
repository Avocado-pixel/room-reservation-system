<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Client dashboard controller.
 *
 * Displays the client's personalized dashboard with room availability
 * statistics and upcoming bookings.
 *
 * @category Client
 * @package  App\Http\Controllers\Client
 */
class DashboardController extends Controller
{
	/**
	 * Display the client dashboard.
	 */
	public function index(Request $request): View
	{
		$availableRoomsCount = Room::query()->active()->where('status', 'available')->count();
		$startCol = Booking::startColumn();

		$upcomingBookings = Booking::query()
			->where('user_id', $request->user()->id)
			->with('room')
			->where($startCol, '>=', now())
			->orderBy($startCol)
			->limit(5)
			->get();

		return view('client.dashboard', [
			'availableRoomsCount' => $availableRoomsCount,
			'upcomingBookings' => $upcomingBookings,
		]);
	}
}
