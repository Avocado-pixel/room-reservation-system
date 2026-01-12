<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    /**
     * Create a new room.
     */
    public function create(array $data, ?UploadedFile $photo = null): Room
    {
        $photoPath = null;
        if ($photo) {
            $photoPath = $photo->store('rooms', 'public');
        }

        $equipment = $this->normalizeEquipment($data['equipment'] ?? null);

        return Room::create([
            'name' => $data['name'],
            'capacity' => (int) $data['capacity'],
            'description' => $data['description'] ?? null,
            'equipment' => $equipment,
            'status' => $data['status'],
            'usage_rules' => $data['usage_rules'] ?? null,
            'photo' => $photoPath,
            'record_status' => 'active',
        ]);
    }

    /**
     * Update an existing room.
     */
    public function update(Room $room, array $data, ?UploadedFile $photo = null): Room
    {
        if ($photo) {
            if ($room->photo) {
                Storage::disk('public')->delete($room->photo);
            }
            $room->photo = $photo->store('rooms', 'public');
        }

        $equipment = $this->normalizeEquipment($data['equipment'] ?? null);

        $room->fill([
            'name' => $data['name'],
            'capacity' => (int) $data['capacity'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
            'equipment' => $equipment,
            'usage_rules' => $data['usage_rules'] ?? null,
        ])->save();

        return $room;
    }

    /**
     * Soft delete a room.
     */
    public function delete(Room $room): bool
    {
        if ($room->photo) {
            Storage::disk('public')->delete($room->photo);
        }

        $room->record_status = 'deleted';
        return $room->save();
    }

    /**
     * Normalize equipment list from comma/newline separated string.
     */
    public function normalizeEquipment(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $parts = preg_split('/[\n,]+/', $raw) ?: [];
        $clean = collect($parts)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return count($clean) ? $clean : null;
    }
}
