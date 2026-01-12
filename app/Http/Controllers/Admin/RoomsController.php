<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use App\Services\RoomService;
use App\Support\QueryPresets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Handles room management for administrators.
 *
 * Provides CRUD operations for rooms including photo uploads,
 * status management, and soft deletion. All actions are audit-logged.
 *
 * @category Admin
 * @package  App\Http\Controllers\Admin
 */
class RoomsController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * Display a listing of all rooms.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Room::class);

        $q = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'name_asc');
        $status = $request->query('status', 'all');
        $rooms = QueryPresets::adminRooms($request, $q);

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'q' => $q,
            'sort' => $sort,
            'status' => $status,
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request): RedirectResponse
    {
        Gate::authorize('create', Room::class);

        $validated = $request->validated();
        $photo = $request->hasFile('photo') ? $request->file('photo') : null;

        $room = $this->roomService->create($validated, $photo);

        $this->auditLogger->log('room.created', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'subject_type' => Room::class,
            'subject_id' => $room->id,
            'after' => $room->only(['name', 'capacity', 'status']),
        ]);

        return redirect()->route('admin.rooms.index')->with('status', 'Room created successfully.');
    }

    /**
     * Update the specified room.
     */
    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        Gate::authorize('update', $room);
        abort_if($room->record_status === 'deleted', 404);

        $validated = $request->validated();
        $before = $room->only(['name', 'capacity', 'status', 'description', 'equipment', 'usage_rules']);
        $photo = $request->hasFile('photo') ? $request->file('photo') : null;

        $this->roomService->update($room, $validated, $photo);

        $this->auditLogger->log('room.updated', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'subject_type' => Room::class,
            'subject_id' => $room->id,
            'before' => $before,
            'after' => $room->only(['name', 'capacity', 'status', 'description', 'equipment', 'usage_rules']),
        ]);

        return redirect()->route('admin.rooms.index')->with('status', 'Room updated successfully.');
    }

    /**
     * Soft-delete the specified room.
     */
    public function destroy(Request $request, Room $room): RedirectResponse
    {
        Gate::authorize('delete', $room);
        abort_if($room->record_status === 'deleted', 404);

        $this->roomService->delete($room);

        $this->auditLogger->log('room.deleted', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'subject_type' => Room::class,
            'subject_id' => $room->id,
            'before' => ['record_status' => 'active'],
            'after' => ['record_status' => 'deleted'],
        ]);

        return redirect()->route('admin.rooms.index')->with('status', 'Room deleted successfully.');
    }
}
