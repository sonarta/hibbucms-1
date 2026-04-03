<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContentPreviewAllowed
{
    /**
     * Allow preview via signed URL (no login) or authenticated users with "view content".
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        if ($request->user() && $request->user()->can('view content')) {
            return $next($request);
        }

        abort(403);
    }
}
