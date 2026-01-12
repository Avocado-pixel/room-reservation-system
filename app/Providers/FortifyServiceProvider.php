<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\Audit\AuditLogger;

class FortifyServiceProvider extends ServiceProvider
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
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        Event::listen(Verified::class, function (Verified $event): void {
            $user = $event->user;

            if (!$user instanceof User) {
                return;
            }

            if (($user->status ?? 'pending') === 'active') {
                return;
            }

            $user->forceFill([
                'status' => 'active',
                'email_validation_token' => null,
                'email_validation_expires_at' => null,
            ])->save();
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::authenticateUsing(function (Request $request) {
            $email = (string) $request->input(Fortify::username());
            $user = User::query()->where('email', $email)->first();

            if (!$user) {
                app(AuditLogger::class)->log('login.failed', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => null,
                ]);
                return null;
            }

            if (($user->status ?? 'pending') === 'pending') {
                app(AuditLogger::class)->log('login.failed.pending', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ]);
                throw ValidationException::withMessages([
                    Fortify::username() => ['Your account has not been validated yet. Please validate your email.'],
                ]);
            }

            if (($user->status ?? null) === 'blocked') {
                app(AuditLogger::class)->log('login.failed.blocked', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ]);
                throw ValidationException::withMessages([
                    Fortify::username() => ['Your account has been blocked. Contact an administrator.'],
                ]);
            }

            if (($user->status ?? null) === 'deleted') {
                app(AuditLogger::class)->log('login.failed.deleted', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ]);
                throw ValidationException::withMessages([
                    Fortify::username() => ['Invalid credentials.'],
                ]);
            }

            if (($user->status ?? null) !== 'active') {
                app(AuditLogger::class)->log('login.failed.inactive', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ]);
                throw ValidationException::withMessages([
                    Fortify::username() => ['Account is not active.'],
                ]);
            }

            if (!Hash::check((string) $request->input('password', ''), (string) $user->password)) {
                app(AuditLogger::class)->log('login.failed.bad_password', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ]);
                return null;
            }

            app(AuditLogger::class)->log('login.success', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'subject_type' => User::class,
                'subject_id' => $user->id,
            ]);
            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
