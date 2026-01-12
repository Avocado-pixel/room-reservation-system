<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\RoomsController as PublicRoomsController;
use App\Http\Controllers\Public\AccountValidationController;
use App\Http\Controllers\Public\EmailVerificationController;
use App\Http\Controllers\Client\RoomsController as ClientRoomsController;
use App\Http\Controllers\Client\BookingsController as ClientBookingsController;
use App\Http\Controllers\Client\FavoritesController as ClientFavoritesController;
use App\Http\Controllers\Client\FeedbackController as ClientFeedbackController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoomsController as AdminRoomsController;
use App\Http\Controllers\Admin\BookingsController as AdminBookingsController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\CancellationPolicyController as AdminCancellationPolicyController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// SEO Routes (must be first for proper matching)
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/', fn () => redirect()->route('rooms.public.index'));

Route::get('/rooms', [PublicRoomsController::class, 'index'])
    ->middleware('throttle:heavy-reads')
    ->name('rooms.public.index');

// Public policy/terms pages with Markdown rendering
Route::get('/privacy-policy', function () {
    $policy = Str::markdown(File::get(resource_path('markdown/policy.md')));
    return view('policy', compact('policy'));
})->name('policy.show');

Route::get('/terms-of-service', function () {
    $terms = Str::markdown(File::get(resource_path('markdown/terms.md')));
    return view('terms', compact('terms'));
})->name('terms.show');

Route::get('/validate-account', [AccountValidationController::class, 'show'])->name('validate-account.show');
Route::post('/validate-account', [AccountValidationController::class, 'store'])->name('validate-account.store');
Route::post('/validate-account/resend', [AccountValidationController::class, 'resend'])->name('validate-account.resend');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'account.active',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user && $user->isUser()) {
            return redirect()->route('client.rooms.index');
        }

        abort(403, 'Invalid profile.');
    })->name('dashboard');

    Route::prefix('client')->name('client.')->middleware('user')->group(function () {
        Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('/rooms', [ClientRoomsController::class, 'index'])
            ->middleware('throttle:heavy-reads')
            ->name('rooms.index');
        Route::get('/rooms/{room}/book', [ClientBookingsController::class, 'create'])->name('bookings.create');
        Route::get('/rooms/{room}/book/availability', [ClientBookingsController::class, 'availability'])
            ->middleware('throttle:heavy-reads')
            ->name('bookings.availability');
        Route::post('/rooms/{room}/book', [ClientBookingsController::class, 'store'])->name('bookings.store');
        Route::post('/rooms/{room}/book/recurring', [ClientBookingsController::class, 'storeRecurring'])->middleware('throttle:20,1')->name('bookings.storeRecurring');
        Route::get('/bookings', [ClientBookingsController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}/edit', [ClientBookingsController::class, 'edit'])->name('bookings.edit');
        Route::get('/bookings/{booking}/ics', [ClientBookingsController::class, 'exportIcs'])->name('bookings.export.ics');
        Route::get('/bookings/{booking}/gcal', [ClientBookingsController::class, 'exportGoogle'])->name('bookings.export.gcal');
        Route::get('/bookings/{booking}/pdf', [ClientBookingsController::class, 'exportPdf'])->name('bookings.export.pdf');
        Route::put('/bookings/{booking}', [ClientBookingsController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [ClientBookingsController::class, 'destroy'])->name('bookings.destroy');

        Route::get('/favorites', [ClientFavoritesController::class, 'index'])->name('favorites.index');
        Route::post('/rooms/{room}/favorite', [ClientFavoritesController::class, 'store'])->middleware('throttle:30,1')->name('favorites.store');
        Route::delete('/rooms/{room}/favorite', [ClientFavoritesController::class, 'destroy'])->name('favorites.destroy');

        Route::post('/rooms/{room}/feedback', [ClientFeedbackController::class, 'store'])->middleware('throttle:10,1')->name('feedback.store');
        Route::delete('/feedback/{feedback}', [ClientFeedbackController::class, 'destroy'])->name('feedback.destroy');
    });

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/rooms', [AdminRoomsController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [AdminRoomsController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [AdminRoomsController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [AdminRoomsController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/bookings', [AdminBookingsController::class, 'index'])->name('bookings.index');

        Route::post('/rooms/{room}/cancellation-policies', [AdminCancellationPolicyController::class, 'store'])->name('cancellation-policies.store');
        Route::put('/cancellation-policies/{policy}', [AdminCancellationPolicyController::class, 'update'])->name('cancellation-policies.update');
        Route::delete('/cancellation-policies/{policy}', [AdminCancellationPolicyController::class, 'destroy'])->name('cancellation-policies.destroy');

        Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback/{feedback}/status', [AdminFeedbackController::class, 'updateStatus'])->name('feedback.status');

        Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/status', [AdminUsersController::class, 'setStatus'])->name('users.status');
        Route::post('/users/{user}/role', [AdminUsersController::class, 'setRole'])->name('users.role');
    });
});
