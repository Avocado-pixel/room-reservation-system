<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Room;
use App\Models\Feedback;
use App\Models\RecurringReservation;

/**
 * User model representing authenticated system users.
 *
 * Supports both client and admin roles with full authentication
 * features including 2FA, email verification, and profile photos.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $tax_id
 * @property string|null $phone
 * @property string|null $address
 * @property string $role
 * @property string $status
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Booking> $bookings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Room> $favorites
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Feedback> $feedback
 *
 * @category Models
 * @package  App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use MustVerifyEmailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'tax_id',
        'phone',
        'phone_country',
        'address',
        'role',
        'status',
        'email_validation_token',
        'email_validation_expires_at',
        'email_verified_at',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'current_team_id',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_validation_expires_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        $role = strtolower((string) ($this->role ?? ''));
        return in_array($role, ['admin', 'administrator'], true);
    }

    /**
     * Check if the user is a regular user/client.
     */
    public function isUser(): bool
    {
        $role = strtolower((string) ($this->role ?? ''));
        return in_array($role, ['user', 'client'], true);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'favorites')->withTimestamps();
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function recurringReservations(): HasMany
    {
        return $this->hasMany(RecurringReservation::class);
    }
}
