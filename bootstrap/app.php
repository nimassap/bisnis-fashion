<?php

use App\Http\Middleware\EnsureSetupIsComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'setup.complete' => EnsureSetupIsComplete::class,
        ]);

        // Behind a tunnel or reverse proxy the request reaches PHP as plain
        // HTTP; trusting the proxy's X-Forwarded-* headers is what makes
        // generated links (CSS, JS, Livewire) https again. Only the proxies
        // listed are trusted: a local tunnel by default.
        $middleware->trustProxies(at: array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))));

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
