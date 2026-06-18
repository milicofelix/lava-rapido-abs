<?php

namespace App\Http\Middleware;

use App\Support\Access\AccessControl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccess
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! AccessControl::allows($request->user(), $permission)) {
            abort(403);
        }

        return $next($request);
    }
}
