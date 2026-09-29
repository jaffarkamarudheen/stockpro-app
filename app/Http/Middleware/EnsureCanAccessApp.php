<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessApp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        if (! $user->canAccessApp()) {
            if ($user->canAccessAdmin()) {
                return redirect()->route('admin.dashboard')->with('error', 'App permission is disabled for your account.');
            }

            abort(403, 'Unauthorized. You do not have permission to access the Mobile App.');
        }

        return $next($request);
    }
}
