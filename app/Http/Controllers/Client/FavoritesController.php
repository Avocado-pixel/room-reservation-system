<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages client favorite rooms.
 *
 * Allows clients to save rooms to their favorites list
 * for quick access and browsing.
 *
 * @category Client
 * @package  App\Http\Controllers\Client
 */
class FavoritesController extends Controller
{
    /**
     * Display the client's favorite rooms.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $favorites = $user->favorites()->where('record_status', '!=', 'deleted')->paginate(12);

        return view('client.favorites.index', [
            'rooms' => $favorites,
        ]);
    }

    /**
     * Add a room to favorites.
     */
    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_if($room->record_status === 'deleted', 404);
        abort_if(!$request->user(), 401);

        $request->user()->favorites()->syncWithoutDetaching([$room->id]);

        return back()->with('status', 'Room added to favorites.');
    }

    /**
     * Remove a room from favorites.
     */
    public function destroy(Request $request, Room $room): RedirectResponse
    {
        abort_if(!$request->user(), 401);
        $request->user()->favorites()->detach($room->id);

        return back()->with('status', 'Room removed from favorites.');
    }
}
