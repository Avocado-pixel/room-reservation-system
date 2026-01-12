<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCancellationPolicyRequest;
use App\Models\CancellationPolicy;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles cancellation policy management for rooms.
 *
 * Administrators can define and manage policies that dictate
 * how far in advance bookings can be cancelled and any associated penalties.
 *
 * @category Admin
 * @package  App\Http\Controllers\Admin
 */
class CancellationPolicyController extends Controller
{
    /**
     * Store a new cancellation policy for a room.
     */
    public function store(StoreCancellationPolicyRequest $request, Room $room): RedirectResponse
    {
        $data = $request->validated();

        if (($data['penalty_type'] ?? 'none') === 'none') {
            $data['penalty_value'] = null;
        }

        if (!empty($data['is_active'])) {
            $room->cancellationPolicies()->update(['is_active' => false]);
        }

        $room->cancellationPolicies()->create($data);

        return back()->with('status', 'Cancellation policy saved.');
    }

    /**
     * Update an existing cancellation policy.
     */
    public function update(StoreCancellationPolicyRequest $request, CancellationPolicy $policy): RedirectResponse
    {
        $data = $request->validated();
        if (($data['penalty_type'] ?? 'none') === 'none') {
            $data['penalty_value'] = null;
        }

        if (!empty($data['is_active'])) {
            $policy->room->cancellationPolicies()->where('id', '!=', $policy->id)->update(['is_active' => false]);
        }

        $policy->update($data);

        return back()->with('status', 'Cancellation policy updated.');
    }

    /**
     * Remove a cancellation policy.
     */
    public function destroy(Request $request, CancellationPolicy $policy): RedirectResponse
    {
        $policy->delete();

        return back()->with('status', 'Cancellation policy removed.');
    }
}
