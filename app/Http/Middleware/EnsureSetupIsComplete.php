<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\SetupController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every visitor to the setup screen until the owner account exists,
 * so a fresh install never shows a login form nobody can use.
 */
final class EnsureSetupIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SetupController::completed()) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
