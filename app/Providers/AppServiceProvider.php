<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\Rules\Password;
use App\Models\Booking;
use App\Models\Feedback;
use App\Models\Room;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\FeedbackPolicy;
use App\Policies\RoomPolicy;
use App\Policies\UserPolicy;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure password validation rules for the entire application
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()      // At least one uppercase and one lowercase letter
                ->numbers()        // At least one number
                ->symbols()        // At least one symbol (!@#$%^&*etc)
                ->uncompromised(); // Check against known breached passwords (haveibeenpwned.com)
        });

        // Register all policies for RBAC
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Rate limiting for heavy read operations (anti-DDoS)
        RateLimiter::for('heavy-reads', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Rate limiting for write operations
        RateLimiter::for('write-ops', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(20)->by($userId);
        });

        // Rate limiting for authentication attempts
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Note: Login/logout events are logged in FortifyServiceProvider to avoid duplicates

        Event::listen(Verified::class, function (Verified $event): void {
            $user = $event->user;
            if (!$user instanceof User) {
                return;
            }

            // After validation, mark the account as active.
			if (($user->status ?? null) === 'pending') {
				$user->forceFill(['status' => 'active'])->save();
            }
        });
    }
}
