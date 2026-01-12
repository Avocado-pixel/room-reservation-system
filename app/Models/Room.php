<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RecurringReservation;
use App\Models\Feedback;
use App\Models\CancellationPolicy;
use App\Models\User;

/**
 * Room model representing bookable spaces.
 *
 * Supports soft-deletion via record_status, equipment lists,
 * usage rules, photos, and associated cancellation policies.
 *
 * @property int $id
 * @property string $name
 * @property int $capacity
 * @property string|null $description
 * @property array|null $equipment
 * @property string $status
 * @property string|null $usage_rules
 * @property string|null $photo
 * @property string $record_status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Booking> $bookings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Feedback> $feedback
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CancellationPolicy> $cancellationPolicies
 *
 * @category Models
 * @package  App\Models
 */
class Room extends Model
{
	use HasFactory;

	protected $table = 'rooms';

	protected $fillable = [
		'name',
		'capacity',
		'description',
		'equipment',
		'status',
		'usage_rules',
		'photo',
		'record_status',
	];

	protected function casts(): array
	{
		return [
			'capacity' => 'integer',
			'equipment' => 'array',
		];
	}

	/**
	 * Get the bookings for the room.
	 */
	public function bookings(): HasMany
	{
		return $this->hasMany(Booking::class, 'room_id');
	}

	public function recurringReservations(): HasMany
	{
		return $this->hasMany(RecurringReservation::class);
	}

	public function feedback(): HasMany
	{
		return $this->hasMany(Feedback::class);
	}

	public function cancellationPolicies(): HasMany
	{
		return $this->hasMany(CancellationPolicy::class);
	}

	public function favoritedBy(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
	}

	/**
	 * Scope a query to only include active records.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder<Room> $query
	 * @return \Illuminate\Database\Eloquent\Builder<Room>
	 */
	public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->where('record_status', '!=', 'deleted');
	}

	/**
	 * Scope a query to only include public rooms (available).
	 *
	 * @param \Illuminate\Database\Eloquent\Builder<Room> $query
	 * @return \Illuminate\Database\Eloquent\Builder<Room>
	 */
	public function scopePublic(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->active()->where('status', 'available');
	}
}
