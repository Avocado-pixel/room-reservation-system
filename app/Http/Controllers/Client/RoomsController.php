<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\QueryPresets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays available rooms for authenticated clients.
 *
 * Provides a filterable, sortable listing of all available rooms
 * with support for favorites and search queries.
 *
 * @category Client
 * @package  App\Http\Controllers\Client
 */
class RoomsController extends Controller
{
	/**
	 * Display a listing of available rooms.
	 */
	public function index(Request $request): View
	{
		$q = trim((string) $request->query('q', ''));
		$sort = $request->query('sort', 'name_asc');
		$status = 'all';
		$saved = $request->boolean('saved', false) || $sort === 'favorites';
		$userId = $request->user()?->id;
		$rooms = QueryPresets::clientRooms($request, $q, $userId);

		return view('client.rooms.index', [
			'rooms' => $rooms,
			'q' => $q,
			'sort' => $sort,
			'status' => $status,
			'saved' => $saved,
		]);
	}
}
