<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        if (!$user->hasActiveSubscription()) {
            AccessLog::create([
                'user_id'    => $user->id,
                'camera_id'  => null,
                'event'      => 'subscription_expired',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta'       => ['url' => $request->path()],
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sua assinatura está vencida ou inativa.'], 403);
            }

            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
