<?php

use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureUserCanAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Console\Commands\ExpireLoyaltyCouponsCommand;
use App\Console\Commands\ProductionCheckCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        ExpireLoyaltyCouponsCommand::class,
        ProductionCheckCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('subscriptions:expire')->hourly();
        $schedule->command('loyalty:expire-coupons')->dailyAt('03:30');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/mercado-pago',
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserCanAccess::class,
            'active.subscription' => EnsureActiveSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
