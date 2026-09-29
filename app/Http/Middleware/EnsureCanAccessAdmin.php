<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessAdmin
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

        if (! $user->canAccessAdmin()) {
            if ($user->canAccessApp()) {
                return redirect()->route('app.index')->with('error', 'You only have permission to access the Mobile App.');
            }

            abort(403, 'Unauthorized. You do not have permission to access the Admin Panel.');
        }

        return $next($request);
    }
}
