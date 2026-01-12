<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class QueryPresets
{
	public const PER_PAGE_PUBLIC_ROOMS = 6;
	public const PER_PAGE_CLIENT_ROOMS = 6;
	public const PER_PAGE_CLIENT_BOOKINGS = 5;
	public const PER_PAGE_ADMIN_ROOMS = 6;
	public const PER_PAGE_ADMIN_BOOKINGS = 5;
	public const PER_PAGE_ADMIN_USERS = 5;
	public const PER_PAGE_ADMIN_USER_BOOKINGS = 5;

	public static function publicRooms(Request $request, string $q): LengthAwarePaginator
	{
		$sort = $request->query('sort', 'name_asc');
		[$sortCol, $sortDir] = self::roomSort($sort);

		return Room::query()
			->public()
			->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
			->orderBy($sortCol, $sortDir)
			->paginate(self::PER_PAGE_PUBLIC_ROOMS)
			->withQueryString();
	}

	public static function clientRooms(Request $request, string $q, ?int $userId = null): LengthAwarePaginator
	{
		$sort = $request->query('sort', 'name_asc');
		$status = 'all'; // no filtro de status no cliente
		$savedOnly = $request->boolean('saved', false) || $sort === 'favorites';
		[$sortCol, $sortDir] = self::roomSort($sort);

		return Room::query()
			->active()
			->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
			->when($userId, fn ($query) => $query->withExists([
				'favoritedBy as is_favorite' => fn ($q2) => $q2->where('users.id', $userId),
			]))
			->when($savedOnly && $userId, fn ($query) => $query->whereHas('favoritedBy', fn ($q2) => $q2->where('users.id', $userId)))
			->orderBy($sortCol, $sortDir)
			->paginate(self::PER_PAGE_CLIENT_ROOMS)
			->withQueryString();
	}

	public static function clientBookings(Request $request, int $userId): LengthAwarePaginator
	{
		$startCol = Booking::startColumn();
		$filter = $request->query('filter', 'all');
		$now = now(config('app.timezone'));

		return Booking::query()
			->where('user_id', $userId)
			->when($filter === 'future', fn ($q) => $q->where($startCol, '>', $now))
			->when($filter === 'past', fn ($q) => $q->where($startCol, '<=', $now))
			->with('room')
			->orderByDesc($startCol)
			->paginate(self::PER_PAGE_CLIENT_BOOKINGS)
			->withQueryString();
	}

	public static function adminRooms(Request $request, string $q): LengthAwarePaginator
	{
		$sort = $request->query('sort', 'name_asc');
		$status = $request->query('status', 'all');
		[$sortCol, $sortDir] = self::roomSort($sort);

		return Room::query()
			->active()
			->when(in_array($status, ['available', 'unavailable', 'coming_soon'], true), fn ($query) => $query->where('status', $status))
			->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
			->with(['cancellationPolicies' => fn ($q2) => $q2->orderByDesc('is_active')->orderByDesc('id')])
			->orderBy($sortCol, $sortDir)
			->paginate(self::PER_PAGE_ADMIN_ROOMS)
			->withQueryString();
	}

	/**
	 * Returns a paginator only when $date is valid (YYYY-MM-DD). Otherwise returns an empty collection.
	 */
	public static function adminBookingsByDay(Request $request, string $date, string $q): LengthAwarePaginator|Collection
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return collect();
		}

		$startDay = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', config('app.timezone'));
		$endDay = $startDay->copy()->addDay();
		$startCol = Booking::startColumn();

		return Booking::query()
			->with(['room', 'user'])
			->where($startCol, '>=', $startDay)
			->where($startCol, '<', $endDay)
			->when($q !== '', function ($query) use ($q) {
				$query->whereHas('room', fn ($sq) => $sq->where('name', 'like', "%{$q}%"));
			})
			->orderBy($startCol)
			->paginate(self::PER_PAGE_ADMIN_BOOKINGS)
			->withQueryString();
	}

	public static function adminUsers(Request $request, string $q): LengthAwarePaginator
	{
		$sort = $request->query('sort', 'name_asc');
		$role = $request->query('role', 'all');
		$status = $request->query('status', 'all');
		[$sortCol, $sortDir] = self::userSort($sort);

		return User::query()
			->when($q !== '', function ($query) use ($q) {
				$query->where(function ($sq) use ($q) {
					$sq->where('name', 'like', "%{$q}%")
						->orWhere('email', 'like', "%{$q}%");
				});
			})
			->when(in_array($role, ['admin', 'user'], true), fn ($query) => $query->where('role', $role))
			->when(in_array($status, ['pending', 'active', 'blocked', 'deleted'], true), fn ($query) => $query->where('status', $status))
			->orderBy($sortCol, $sortDir)
			->paginate(self::PER_PAGE_ADMIN_USERS, ['*'], 'users_page')
			->withQueryString();
	}

	public static function adminUserBookings(Request $request, int $viewUserId): LengthAwarePaginator
	{
		$startCol = Booking::startColumn();

		return Booking::query()
			->where('user_id', $viewUserId)
			->with('room')
			->orderByDesc($startCol)
			->paginate(self::PER_PAGE_ADMIN_USER_BOOKINGS, ['*'], 'bookings_page')
			->withQueryString();
	}

	private static function roomSort(string $sort): array
	{
		$map = [
			'name_asc' => ['name', 'asc'],
			'name_desc' => ['name', 'desc'],
			'cap_asc' => ['capacity', 'asc'],
			'cap_desc' => ['capacity', 'desc'],
		];
		return $map[$sort] ?? ['name', 'asc'];
	}

	private static function userSort(string $sort): array
	{
		$map = [
			'name_asc' => ['name', 'asc'],
			'name_desc' => ['name', 'desc'],
		];
		return $map[$sort] ?? ['name', 'asc'];
	}
}
