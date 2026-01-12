<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $feedback = Feedback::query()
            ->with(['user', 'room'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'status' => $status,
        ]);
    }

    public function updateStatus(Request $request, Feedback $feedback)
    {
        $data = $request->validate([
            'status' => ['required', 'in:visible,hidden,pending'],
            'is_flagged' => ['sometimes', 'boolean'],
        ]);

        $feedback->status = $data['status'];
        if (array_key_exists('is_flagged', $data)) {
            $feedback->is_flagged = (bool) $data['is_flagged'];
        }
        $feedback->save();

        return back()->with('status', 'Feedback status updated.');
    }
}
