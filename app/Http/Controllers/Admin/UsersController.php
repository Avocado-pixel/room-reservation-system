<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Support\QueryPresets;
use Illuminate\Http\Request;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Gate;

class UsersController extends Controller
{
	public function index(Request $request)
	{
		Gate::authorize('viewAny', User::class);

		$q = trim((string) $request->query('q', ''));
		$sort = $request->query('sort', 'name_asc');
		$role = $request->query('role', 'all');
		$status = $request->query('status', 'all');
		$viewUserId = (int) $request->query('view', 0);
		$users = QueryPresets::adminUsers($request, $q);

		$bookings = null;
		$viewUser = null;
		if ($viewUserId > 0) {
			$viewUser = User::query()->whereKey($viewUserId)->first();
			$bookings = QueryPresets::adminUserBookings($request, $viewUserId);
		}

		return view('admin.users.index', [
			'users' => $users,
			'q' => $q,
			'sort' => $sort,
			'role' => $role,
			'status' => $status,
			'viewUserId' => $viewUserId,
			'viewUser' => $viewUser,
			'bookings' => $bookings,
		]);
	}

	public function setStatus(Request $request, User $user)
	{
		Gate::authorize('updateStatus', $user);

		$validated = $request->validate([
			'status' => ['required', 'in:active,blocked'],
		]);

		if (($user->role ?? null) === 'admin') {
			return back()->withErrors(['status' => 'You cannot block/unblock administrator accounts.']);
		}

		$before = $user->only(['status']);
		$user->status = $validated['status'];
		$user->save();

		app(AuditLogger::class)->log('user.status_changed', [
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'subject_type' => User::class,
			'subject_id' => $user->id,
			'before' => $before,
			'after' => $user->only(['status']),
		]);

		return back()->with('status', 'Status updated successfully.');
	}

	public function setRole(Request $request, User $user)
	{
		Gate::authorize('updateRole', $user);

		$validated = $request->validate([
			'role' => ['required', 'in:admin,user'],
		]);

		$admin = $request->user();

		// Rule: an admin cannot remove their own admin role.
		if ((int) $admin->id === (int) $user->id && ($admin->role ?? null) === 'admin' && $validated['role'] !== 'admin') {
			return back()->withErrors(['role' => 'You cannot change your own administrator role.']);
		}

		// Rule: do not remove the last administrator.
		if (($user->role ?? null) === 'admin' && $validated['role'] !== 'admin') {
			$totalAdmins = (int) User::query()->where('role', 'admin')->count();
			if ($totalAdmins <= 1) {
				return back()->withErrors(['role' => 'You cannot remove the last administrator.']);
			}
		}

		$before = $user->only(['role']);
		$user->role = $validated['role'];
		$user->save();

		app(AuditLogger::class)->log('user.role_changed', [
			'user_id' => $request->user()?->id,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			'subject_type' => User::class,
			'subject_id' => $user->id,
			'before' => $before,
			'after' => $user->only(['role']),
		]);

		return back()->with('status', 'Role updated successfully.');
	}
}
