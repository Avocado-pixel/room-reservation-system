<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles client feedback submission for rooms.
 *
 * Allows authenticated clients to submit ratings and comments
 * for rooms they have booked. Feedback is subject to moderation.
 *
 * @category Client
 * @package  App\Http\Controllers\Client
 */
class FeedbackController extends Controller
{
    /**
     * Store or update feedback for a room.
     */
    public function store(StoreFeedbackRequest $request, Room $room): RedirectResponse
    {
        abort_if($room->record_status === 'deleted', 404);

        $data = $request->validated();
        $comment = $data['comment'] ?? null;
        if ($comment !== null) {
            $comment = trim(strip_tags($comment));
        }

        Feedback::updateOrCreate(
            ['user_id' => $request->user()->id, 'room_id' => $room->id],
            [
                'rating' => $data['rating'],
                'comment' => $comment,
                'status' => 'pending',
                'is_flagged' => false,
            ]
        );

        return back()->with('status', 'Feedback submitted. Pending moderation.');
    }

    /**
     * Remove client's own feedback.
     */
    public function destroy(Request $request, Feedback $feedback): RedirectResponse
    {
        abort_if($feedback->user_id !== $request->user()->id, 403);
        $feedback->delete();

        return back()->with('status', 'Feedback removed.');
    }
}
