<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Core\Domain\Users\UserRole;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((string) $request->user()?->role !== UserRole::Admin->value) {
            abort(403, 'Admin role required.');
        }

        return $next($request);
    }
}
