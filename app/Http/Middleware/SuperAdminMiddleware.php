<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowed = array_filter(explode(',', env('SUPERADMIN_EMAILS', '')));

        if (! auth()->check() || ! in_array(auth()->user()->email, $allowed)) {
            abort(403, 'Acesso restrito ao super-administrador.');
        }

        return $next($request);
    }
}
