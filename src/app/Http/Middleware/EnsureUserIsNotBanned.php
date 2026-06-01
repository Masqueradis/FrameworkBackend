<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->user()?->isBanned()) {

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Your account is restricted. You cannot perform this action.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->back()->with('error_alert', 'Your account is restricted. You cannot post reviews.');
        }

        return $next($request);
    }
}
