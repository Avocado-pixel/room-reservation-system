<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\QueryPresets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public rooms listing controller.
 *
 * Displays available rooms to unauthenticated visitors
 * with search and sorting capabilities.
 *
 * @category Public
 * @package  App\Http\Controllers\Public
 */
class RoomsController extends Controller
{
	/**
	 * Display a listing of available public rooms.
	 */
	public function index(Request $request): View
	{
		$q = trim((string) $request->query('q', ''));
		$sort = $request->query('sort', 'name_asc');
		$rooms = QueryPresets::publicRooms($request, $q);

		return view('public.rooms.index', [
			'rooms' => $rooms,
			'q' => $q,
			'sort' => $sort,
		]);
	}
}
