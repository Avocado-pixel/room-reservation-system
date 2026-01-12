<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use App\Models\RecurringReservation;

/**
 * Booking model representing room reservations.
 *
 * Handles both single and recurring bookings with support for
 * multiple date column formats for schema compatibility.
 *
 * @property int $id
 * @property int $user_id
 * @property int $room_id
 * @property int|null $recurring_reservation_id
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string|null $share_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Room $room
 * @property-read RecurringReservation|null $recurringReservation
 *
 * @category Models
 * @package  App\Models
 */
class Booking extends Model
{
	protected $table = 'bookings';

	protected static ?string $startColumnCache = null;
	protected static ?string $endColumnCache = null;

	protected $fillable = [
		'user_id',
		'room_id',
		'recurring_reservation_id',
		'start_at',
		'end_at',
		'start_date',
		'end_date',
		'share_token',
	];

	protected function casts(): array
	{
		return [
			'start_at' => 'datetime',
			'end_at' => 'datetime',
			'start_date' => 'datetime',
			'end_date' => 'datetime',
		];
	}

	public static function startColumn(): string
	{
		if (self::$startColumnCache) {
			return self::$startColumnCache;
		}

		$table = (new self())->getTable();
		if (Schema::hasColumn($table, 'start_date')) {
			return self::$startColumnCache = 'start_date';
		}
		if (Schema::hasColumn($table, 'start_at')) {
			return self::$startColumnCache = 'start_at';
		}

		return self::$startColumnCache = 'start_date';
	}

	public static function endColumn(): string
	{
		if (self::$endColumnCache) {
			return self::$endColumnCache;
		}

		$table = (new self())->getTable();
		if (Schema::hasColumn($table, 'end_date')) {
			return self::$endColumnCache = 'end_date';
		}
		if (Schema::hasColumn($table, 'end_at')) {
			return self::$endColumnCache = 'end_at';
		}

		return self::$endColumnCache = 'end_date';
	}

	public function getStartDateAttribute($value)
	{
		$col = self::startColumn();
		$raw = $col === 'start_date' ? $value : ($this->attributes['start_at'] ?? null);
		return $raw ? $this->asDateTime($raw) : null;
	}

	public function getEndDateAttribute($value)
	{
		$col = self::endColumn();
		$raw = $col === 'end_date' ? $value : ($this->attributes['end_at'] ?? null);
		return $raw ? $this->asDateTime($raw) : null;
	}

	public function setStartDateAttribute($value): void
	{
		$col = self::startColumn();
		$this->attributes[$col] = $value;
		if ($col !== 'start_date') {
			unset($this->attributes['start_date']);
		}
	}

	public function setEndDateAttribute($value): void
	{
		$col = self::endColumn();
		$this->attributes[$col] = $value;
		if ($col !== 'end_date') {
			unset($this->attributes['end_date']);
		}
	}

	/**
	 * Get the room for the booking.
	 */
	public function room(): BelongsTo
	{
		return $this->belongsTo(Room::class, 'room_id');
	}

	/**
	 * Get the user for the booking.
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function recurringReservation(): BelongsTo
	{
		return $this->belongsTo(RecurringReservation::class);
	}
}
