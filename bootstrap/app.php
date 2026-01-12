<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureUser;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\CheckIpBlacklist;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\AdvancedThrottle;
use App\Http\Middleware\LogPublicTraffic;
use App\Http\Middleware\GlobalRateLimit;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - applied to all requests
        $middleware->append(CheckIpBlacklist::class);
        $middleware->append(SanitizeInput::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(GlobalRateLimit::class);  // Global rate limiting
        $middleware->append(LogPublicTraffic::class);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'account.active' => EnsureActiveAccount::class,
            'user' => EnsureUser::class,
            'throttle' => ThrottleRequests::class,
            'throttle.advanced' => AdvancedThrottle::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
