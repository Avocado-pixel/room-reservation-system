<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    protected $fillable = [
        'room_id',
        'name',
        'description',
        'cancel_before_hours',
        'penalty_type',
        'penalty_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cancel_before_hours' => 'integer',
            'penalty_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
